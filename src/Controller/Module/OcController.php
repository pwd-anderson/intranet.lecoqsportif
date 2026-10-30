<?php

namespace App\Controller\Module;

use App\Entity\Oc\Offer;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use App\Repository\Oc\OfferRepository;
use App\Security\OfferVoter;
use App\Service\Oc\Import\OfferImportChecker;
use App\Service\Oc\Import\OfferImportException;
use App\Service\Oc\Import\OfferImportReader;
use App\Service\Oc\Import\OfferImportTemplate;
use App\Service\Oc\OcStatus;
use App\Service\Oc\OcX3Lookup;
use App\Service\Divers;
use App\Service\Oc\OfferCalculator;
use App\Service\Oc\OfferFlowService;
use App\Service\Oc\OfferPdfGenerator;
use App\Service\Oc\OfferSizing;
use App\Service\Oc\OfferService;
use App\Service\Oc\OfferValidationException;
use App\Service\Tools\Helpers;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Module Offres Commerciales (OC). Accès : ROLE_SALES, ROLE_MANAGEMENT, ROLE_CONTROLLING et ROLE_ADMIN ; les
 * droits fins sur chaque OC (propriétaire, statut, valideur) sont portés par OfferVoter. Les écritures (POST)
 * exigent un jeton CSRF (en-tête X-CSRF-Token) et passent par OfferService.
 */
#[Route('/oc')]
#[IsGranted(new \Symfony\Component\ExpressionLanguage\Expression(
    'is_granted("ROLE_SALES") or is_granted("ROLE_MANAGEMENT") or is_granted("ROLE_CONTROLLING") or is_granted("ROLE_ADMIN")'
))]
final class OcController extends AbstractController
{
    private const CSRF_ID = 'oc_action';

    public function __construct(
        private readonly OfferRepository $offers,
        private readonly OfferService $service,
        private readonly CsrfTokenManagerInterface $csrf,
        private readonly OfferCalculator $calculator,
        private readonly Divers $divers,
    ) {
    }

    // ── Pages ────────────────────────────────────────────────────────────────

    #[Route('', name: 'app_oc_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('oc/index.html.twig', ['rows' => $this->listingRows(null), 'validationMode' => false]);
    }

    /** « Validation OC » : les OC soumises qui attendent la décision de la Direction (ADMIN / MANAGEMENT). */
    #[Route('/validation', name: 'app_oc_validation', methods: ['GET'])]
    public function validation(): Response
    {
        if (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_MANAGEMENT')) {
            throw $this->createAccessDeniedException('Réservé à la Direction.');
        }

        return $this->render('oc/index.html.twig', ['rows' => $this->listingRows(OcStatus::SUBMITTED), 'validationMode' => true]);
    }

    /**
     * Lignes de la liste. Un commercial ne voit que ses OC ; le valideur (ADMIN / MANAGEMENT) et le contrôle de gestion
     * voient tout.
     *
     * @return list<array<string,mixed>>
     */
    private function listingRows(?OcStatus $onlyStatus): array
    {
        $seeAll = $this->isGranted('ROLE_ADMIN') || $this->isGranted('ROLE_MANAGEMENT') || $this->isGranted('ROLE_CONTROLLING');

        $rows = [];
        foreach ($this->offers->findListing($seeAll ? null : $this->currentUser()) as $row) {
            /** @var Offer $o */
            $o = $row[0];
            if ($onlyStatus !== null && $o->getStatus() !== $onlyStatus) {
                continue;
            }
            $rows[] = [
                'id' => $o->getId(),
                'number' => $o->getNumber(),
                'type' => 'Tarif Spé',
                'customers' => $o->getCustomerCode() . ' - ' . $o->getCustomerName(),
                'lines' => (int) $row['lineCount'],
                'status' => $o->getStatus()->value,
                'statusLabel' => $o->getStatus()->label(),
                'createdBy' => $o->getCreatedByName(),
                'createdAt' => $o->getCreatedAt()->format('Y-m-d'),
                'startDate' => $o->getStartDate()->format('Y-m-d'),
                'endDate' => $o->getEndDate()->format('Y-m-d'),
                // « Modifier » seulement pour un brouillon (et son propriétaire) ; sinon consultation seule.
                'editUrl' => $this->isGranted(OfferVoter::EDIT, $o) ? $this->generateUrl('app_oc_edit', ['id' => $o->getId()]) : null,
            ];
        }

        return $rows;
    }

    #[Route('/new', name: 'app_oc_new', methods: ['GET'])]
    public function new(): Response
    {
        return $this->render('oc/new.html.twig', $this->formContext(null));
    }

    #[Route('/{id}/edit', name: 'app_oc_edit', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function edit(#[MapEntity] Offer $offer): Response
    {
        $this->denyAccessUnlessGranted(OfferVoter::EDIT, $offer);

        return $this->render('oc/new.html.twig', $this->formContext($offer));
    }

    /** Nouvelle OC pré-remplie à partir d'une OC REJETÉE (un rejet est définitif : on repart d'une nouvelle OC). */
    #[Route('/{id}/duplicate', name: 'app_oc_duplicate', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function duplicate(#[MapEntity] Offer $offer): Response
    {
        $this->denyAccessUnlessGranted(OfferVoter::VIEW, $offer);
        if ($offer->getStatus() !== OcStatus::REJECTED) {
            throw $this->createNotFoundException('Seule une OC rejetée peut servir de base à une nouvelle OC.');
        }

        return $this->render('oc/new.html.twig', $this->formContext($offer, true));
    }

    #[Route('/{id}', name: 'app_oc_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(#[MapEntity] Offer $offer, Request $request, OcX3Lookup $x3): Response
    {
        $this->denyAccessUnlessGranted(OfferVoter::VIEW, $offer);

        // Valider / Rejeter ne sont proposés que lorsqu'on arrive depuis le menu « Validation OC » (?from=validation).
        $decisionMode = $request->query->get('from') === 'validation' && $this->isGranted(OfferVoter::DECIDE, $offer);

        // Lignes regroupées par article (le parent puis ses tailles). Pour la Direction, on lit aussi les tailles de chaque article dans X3
        // afin de montrer ce qui partira réellement (« Envoyé à X3 ») ; si X3 ne répond pas, la page s'affiche quand même.
        $lines = $offer->getLines()->toArray();
        $sizes = null;
        $sizesUnavailable = false;
        if ($decisionMode) {
            try {
                $sizes = $x3->getVariants(array_map(static fn ($l): string => $l->getParentCode(), $lines));
            } catch (\Throwable) {
                $sizesUnavailable = true;
            }
        }

        return $this->render('oc/show.html.twig', [
            'groups' => OfferSizing::groups($lines, $sizes),
            'sizesUnavailable' => $sizesUnavailable,
            'offer' => $offer,
            'decisionMode' => $decisionMode,
            // Prix d'achat et marge LCS : uniquement dans la vue de validation (jamais pour le commercial, le PDF ni les mails).
            'lcs' => $decisionMode ? $this->lcsMargins($offer) : [],
            'warnings' => $this->service->warnings($offer),
            'csrf' => $this->csrf->getToken(self::CSRF_ID)->getValue(),
        ]);
    }

    /**
     * Marge LCS de chaque ligne (clé = id de ligne). Cours de change lus dans X3 ; s'ils sont indisponibles, la marge est
     * simplement omise (le prix d'achat reste affiché dans sa devise d'origine).
     *
     * @return array<int, array{cost: float, value: float, percent: float}|null>
     */
    private function lcsMargins(Offer $offer): array
    {
        try {
            $rates = $this->divers->getExchangeRatesValues();
        } catch (\Throwable) {
            $rates = [];
        }

        $out = [];
        foreach ($offer->getLines() as $line) {
            $out[(int) $line->getId()] = $this->calculator->lcsMargin(
                (float) $line->getTariff(),
                $line->getPurchasePrice() !== null ? (float) $line->getPurchasePrice() : null,
                $line->getPurchaseCurrency(),
                $offer->getCustomerCurrency(),
                $rates
            );
        }

        return $out;
    }

    // ── Écritures (JSON) ─────────────────────────────────────────────────────

    /**
     * Import d'un fichier de lignes (Excel / CSV) pour le client de l'écran : POST multipart (file, customerCode), jeton CSRF. Ne crée
     * rien en base : renvoie chaque ligne lue avec son contrôle (ok / error + motifs) ; la page ajoute ensuite les lignes valides à
     * l'OC en cours de saisie, que le commercial relit et enregistre comme d'habitude.
     */
    #[Route('/import', name: 'app_oc_import', methods: ['POST'])]
    public function import(Request $request, OfferImportReader $reader, OfferImportChecker $checker): JsonResponse
    {
        if ($error = $this->guardWrite($request)) {
            return $error;
        }

        $file = $request->files->get('file');
        if (!$file instanceof \Symfony\Component\HttpFoundation\File\UploadedFile || !$file->isValid()) {
            return $this->fail(['Aucun fichier reçu (ou envoi interrompu) : choisissez un fichier .xlsx, .xls ou .csv.'], 422);
        }

        try {
            $rows = $reader->read($file->getPathname(), pathinfo((string) $file->getClientOriginalName(), PATHINFO_EXTENSION));
            $checked = $checker->check((string) $request->request->get('customerCode', ''), $rows);
        } catch (OfferImportException $e) {
            return $this->fail([$e->getMessage()], 422);
        }

        $valid = count(array_filter($checked, static fn (array $r): bool => $r['status'] === 'ok'));

        return new JsonResponse([
            'ok' => true,
            'rows' => $checked,
            'summary' => ['total' => count($checked), 'valid' => $valid, 'errors' => count($checked) - $valid],
        ]);
    }

    /** Fichier modèle de l'import : GET /oc/import-template. */
    #[Route('/import-template', name: 'app_oc_import_template', methods: ['GET'])]
    public function importTemplate(OfferImportTemplate $template): Response
    {
        return new Response($template->binary(), 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => HeaderUtils::makeDisposition(HeaderUtils::DISPOSITION_ATTACHMENT, 'modele_import_oc.xlsx'),
            'Cache-Control' => 'private, no-store',
        ]);
    }

    /** Crée ou met à jour un brouillon. Corps JSON : voir OfferInput ; "id" pour une mise à jour, "sourceOfferId" pour une OC issue d'un rejet. */
    #[Route('/save', name: 'app_oc_save', methods: ['POST'])]
    public function save(Request $request): JsonResponse
    {
        if ($error = $this->guardWrite($request)) {
            return $error;
        }

        $data = json_decode($request->getContent(), true);
        if (!is_array($data)) {
            return $this->fail(['Requête illisible.'], 400);
        }

        $existing = null;
        if (!empty($data['id'])) {
            $existing = $this->offers->find((int) $data['id']);
            if ($existing === null) {
                return $this->fail(['OC introuvable.'], 404);
            }
            $this->denyAccessUnlessGranted(OfferVoter::EDIT, $existing);
        }

        $source = null;
        if ($existing === null && !empty($data['sourceOfferId'])) {
            $source = $this->offers->find((int) $data['sourceOfferId']);
            if ($source === null || $source->getStatus() !== OcStatus::REJECTED) {
                return $this->fail(['L\'OC d\'origine doit être une OC rejetée.'], 422);
            }
            $this->denyAccessUnlessGranted(OfferVoter::VIEW, $source);
        }

        try {
            $offer = $this->service->save($data, $this->currentUser(), $existing, $source);
        } catch (OfferValidationException $e) {
            return $this->fail($e->getErrors(), 422);
        }

        return $this->ok($offer);
    }

    #[Route('/{id}/submit', name: 'app_oc_submit', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function submit(#[MapEntity] Offer $offer, Request $request): JsonResponse
    {
        if ($error = $this->guardWrite($request)) {
            return $error;
        }
        $this->denyAccessUnlessGranted(OfferVoter::SUBMIT, $offer);

        try {
            $this->service->submit($offer, $this->currentUser());
        } catch (OfferValidationException $e) {
            return $this->fail($e->getErrors(), 422);
        } catch (\LogicException $e) {
            return $this->fail([$e->getMessage()], 409);
        }

        return $this->ok($offer);
    }

    #[Route('/{id}/cancel', name: 'app_oc_cancel', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function cancel(#[MapEntity] Offer $offer, Request $request): JsonResponse
    {
        if ($error = $this->guardWrite($request)) {
            return $error;
        }
        $this->denyAccessUnlessGranted(OfferVoter::CANCEL, $offer);

        $data = json_decode($request->getContent(), true);
        $reason = is_array($data) && isset($data['reason']) ? mb_substr((string) $data['reason'], 0, 500) : null;

        try {
            $this->service->cancel($offer, $this->currentUser(), $reason);
        } catch (\LogicException $e) {
            return $this->fail([$e->getMessage()], 409);
        }

        return $this->ok($offer);
    }

    /** Validation par la Direction (ADMIN / MANAGEMENT). Refusée (422) si la remise client a changé dans X3 depuis l'enregistrement. */
    #[Route('/{id}/validate', name: 'app_oc_validate', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function validate(#[MapEntity] Offer $offer, Request $request): JsonResponse
    {
        if ($error = $this->guardWrite($request)) {
            return $error;
        }
        $this->denyAccessUnlessGranted(OfferVoter::DECIDE, $offer);

        try {
            $this->service->validate($offer, $this->currentUser());
        } catch (OfferValidationException $e) {
            return $this->fail($e->getErrors(), 422);
        } catch (\LogicException $e) {
            return $this->fail([$e->getMessage()], 409);
        }

        return $this->ok($offer);
    }

    /** Rejet par la Direction : corps JSON {"reason": "..."} obligatoire ; le commercial en est prévenu par mail. */
    #[Route('/{id}/reject', name: 'app_oc_reject', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function reject(#[MapEntity] Offer $offer, Request $request): JsonResponse
    {
        if ($error = $this->guardWrite($request)) {
            return $error;
        }
        $this->denyAccessUnlessGranted(OfferVoter::DECIDE, $offer);

        $data = json_decode($request->getContent(), true);
        $reason = is_array($data) && isset($data['reason']) ? (string) $data['reason'] : null;

        try {
            $this->service->reject($offer, $this->currentUser(), $reason);
        } catch (OfferValidationException $e) {
            return $this->fail($e->getErrors(), 422);
        } catch (\LogicException $e) {
            return $this->fail([$e->getMessage()], 409);
        }

        return $this->ok($offer);
    }

    /** PDF de l'offre (OC validée) : téléchargement. Même contenu que le PDF envoyé au commercial à la validation. */
    #[Route('/{id}/pdf', name: 'app_oc_pdf', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function pdf(#[MapEntity] Offer $offer, OfferPdfGenerator $generator): Response
    {
        $this->denyAccessUnlessGranted(OfferVoter::PDF, $offer);

        return new Response($generator->generate($offer), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => HeaderUtils::makeDisposition(HeaderUtils::DISPOSITION_INLINE, $generator->filename($offer)),
            'Cache-Control' => 'private, no-store',
        ]);
    }

    /** Renvoie le PDF au commercial créateur (échec d'envoi à la validation, ou mail perdu). Direction seulement. */
    #[Route('/{id}/resend-pdf', name: 'app_oc_resend_pdf', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function resendPdf(#[MapEntity] Offer $offer, Request $request): JsonResponse
    {
        if ($error = $this->guardWrite($request)) {
            return $error;
        }
        $this->denyAccessUnlessGranted(OfferVoter::RESEND_PDF, $offer);

        if (!$this->service->sendValidationPdf($offer, $this->currentUser())) {
            return $this->fail(['Le PDF n\'a pas pu être envoyé : le détail est dans l\'historique de l\'OC.'], 502);
        }

        return $this->ok($offer);
    }

    // ── API de lecture X3 (autocomplétions et prix affichés pendant la saisie) ─

    /** Autocomplétion client (X3_LCS) : GET /oc/api/customers?q=... */
    #[Route('/api/customers', name: 'app_oc_api_customers', methods: ['GET'])]
    public function customers(Request $request, OcX3Lookup $lookup, Helpers $helpers): JsonResponse
    {
        $rows = $lookup->searchCustomers((string) $request->query->get('q', ''));

        return new JsonResponse($helpers->convertArrayToUtf8($rows));
    }

    /** Remise client (%) : GET /oc/api/customers/{code}/discount -> {"discount": 5.0, "startDate": "...", "endDate": "..."} ou {"discount": null} */
    #[Route('/api/customers/{code}/discount', name: 'app_oc_api_customer_discount', requirements: ['code' => '[A-Za-z0-9_-]+'], methods: ['GET'])]
    public function customerDiscount(string $code, OcX3Lookup $lookup): JsonResponse
    {
        $row = $lookup->getCustomerDiscount($code);

        return new JsonResponse($row === null
            ? ['discount' => null, 'startDate' => null, 'endDate' => null]
            : ['discount' => (float) $row['REMISE'], 'startDate' => $row['DATE_DEBUT'], 'endDate' => $row['DATE_FIN']]);
    }

    /** Code tarif / tarif / MSRP d'un article pour un client : GET /oc/api/prices?customer=...&article=... */
    #[Route('/api/prices', name: 'app_oc_api_prices', methods: ['GET'])]
    public function prices(Request $request, OcX3Lookup $lookup): JsonResponse
    {
        $row = $lookup->getArticlePrices(
            (string) $request->query->get('customer', ''),
            (string) $request->query->get('article', ''),
        );

        $num = static fn (mixed $v): ?float => $v === null || $v === '' ? null : (float) $v;

        return new JsonResponse([
            'found' => $row !== null,
            'tariffCode' => $row['CODE_TARIF'] ?? null,
            'currency' => $row['DEVISE'] ?? null,
            'collection' => $row['COLLECTION'] ?? null,
            'tariff' => $num($row['TARIF'] ?? null),
            'msrp' => $num($row['MSRP'] ?? null),
        ]);
    }

    /**
     * Tailles (variants) d'un article parent : GET /oc/api/articles/{parent}/variants -> {"variants": ["2210037_L", ...]}.
     * Une erreur X3 rend 503 (jamais une liste vide, qui voudrait dire « aucune taille »).
     */
    #[Route('/api/articles/{parent}/variants', name: 'app_oc_api_article_variants', requirements: ['parent' => '[A-Za-z0-9]+'], methods: ['GET'])]
    public function articleVariants(string $parent, OcX3Lookup $lookup): JsonResponse
    {
        try {
            $variants = $lookup->getVariants([$parent])[$parent] ?? [];
        } catch (\Throwable) {
            return new JsonResponse(['variants' => [], 'error' => 'X3 indisponible'], Response::HTTP_SERVICE_UNAVAILABLE);
        }

        return new JsonResponse(['variants' => array_values($variants)]);
    }

    /**
     * Tarifs spéciaux déjà en place du client sur ces articles (avertissement avant la soumission d'une OC pas encore soumise) :
     * POST /oc/api/existing-tariffs {customerCode, startDate (Y-m-d), articleCodes[]} -> {"ok":true,"tariffs":[...]}.
     * X3 muet : 503 (jamais une liste vide, qui voudrait dire « aucun tarif »).
     */
    #[Route('/api/existing-tariffs', name: 'app_oc_api_existing_tariffs', methods: ['POST'])]
    public function existingTariffs(Request $request, OfferFlowService $flows): JsonResponse
    {
        if ($error = $this->guardWrite($request)) {
            return $error;
        }

        $data = json_decode($request->getContent(), true);
        $customer = is_array($data) ? (string) ($data['customerCode'] ?? '') : '';
        $start = is_array($data) ? \DateTimeImmutable::createFromFormat('!Y-m-d', (string) ($data['startDate'] ?? '')) : false;
        $codes = is_array($data) && is_array($data['articleCodes'] ?? null) ? array_values(array_filter($data['articleCodes'], 'is_string')) : [];
        $codes = array_values(array_filter($codes, static fn (string $c): bool => preg_match('/^[A-Za-z0-9_]{1,50}$/', $c) === 1));

        if (preg_match('/^[A-Za-z0-9]{1,15}$/', $customer) !== 1 || $start === false || $codes === [] || count($codes) > 200) {
            return $this->fail(['Client, date de début et articles requis.'], 422);
        }

        try {
            $rows = $flows->existingTariffsFor($customer, $start, $codes);
        } catch (\Throwable) {
            return new JsonResponse(['ok' => false, 'unavailable' => true, 'errors' => ['X3 indisponible']], Response::HTTP_SERVICE_UNAVAILABLE);
        }

        return new JsonResponse(['ok' => true, 'tariffs' => array_map(static fn (array $r): array => [
            'article' => $r['ARTICLE'], 'fiche' => $r['FICHE'], 'from' => $r['DATE_DEBUT'], 'to' => $r['DATE_FIN'],
            'price' => (float) $r['PRIX'], 'currency' => $r['DEVISE'], 'conflict' => $r['DATE_DEBUT'] >= $start->format('Y-m-d'),
        ], $rows)]);
    }

    /** Autocomplétion article (X3_LCS) : GET /oc/api/articles?q=... (parents ; variants si la saisie contient « _ ») */
    #[Route('/api/articles', name: 'app_oc_api_articles', methods: ['GET'])]
    public function articles(Request $request, OcX3Lookup $lookup, Helpers $helpers): JsonResponse
    {
        $rows = $lookup->searchArticles((string) $request->query->get('q', ''));

        return new JsonResponse($helpers->convertArrayToUtf8($rows));
    }

    // ── Utilitaires ──────────────────────────────────────────────────────────

    private function currentUser(): User
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        return $user;
    }

    private function guardWrite(Request $request): ?JsonResponse
    {
        if (!$this->isCsrfTokenValid(self::CSRF_ID, (string) $request->headers->get('X-CSRF-Token'))) {
            return $this->fail(['Session expirée : rechargez la page.'], 419);
        }

        return null;
    }

    /** @param list<string> $errors */
    private function fail(array $errors, int $status): JsonResponse
    {
        return new JsonResponse(['ok' => false, 'errors' => $errors], $status);
    }

    private function ok(Offer $offer): JsonResponse
    {
        return new JsonResponse([
            'ok' => true,
            'id' => $offer->getId(),
            'number' => $offer->getNumber(),
            'status' => $offer->getStatus()->value,
            'statusLabel' => $offer->getStatus()->label(),
            'editUrl' => $this->generateUrl('app_oc_edit', ['id' => $offer->getId()]),
            'showUrl' => $this->generateUrl('app_oc_show', ['id' => $offer->getId()]),
            'warnings' => $this->service->warnings($offer),
        ]);
    }

    /**
     * Contexte de la page de saisie : $offer = brouillon à modifier ; avec $fromRejected = nouvelle OC pré-remplie
     * à partir d'une OC rejetée (mêmes client, dates, articles et tarifs ; X3 sera relu à l'enregistrement).
     *
     * @return array<string,mixed>
     */
    private function formContext(?Offer $offer, bool $fromRejected = false): array
    {
        $initial = null;
        if ($offer !== null) {
            $initial = [
                'offerId' => $fromRejected ? null : $offer->getId(),
                'number' => $fromRejected ? null : $offer->getNumber(),
                'sourceOfferId' => $fromRejected ? $offer->getId() : null,
                'sourceNumber' => $fromRejected ? $offer->getNumber() : null,
                'customer' => [
                    'code' => $offer->getCustomerCode(),
                    'name' => $offer->getCustomerName(),
                    'currency' => $offer->getCustomerCurrency(),
                ],
                'startDate' => $offer->getStartDate()->format('Y-m-d'),
                'endDate' => $offer->getEndDate()->format('Y-m-d'),
                'comment' => $offer->getComment(),
                'lines' => array_map(static fn ($l) => [
                    'articleCode' => $l->getArticleCode(),
                    'articleLabel' => $l->getArticleLabel(),
                    'tariff' => $l->getTariff(),
                    'msrp' => $l->getMsrp(),
                    'vatRate' => $l->getVatRate(),
                    'minQty' => $l->getMinQty(),
                    'maxQty' => $l->getMaxQty(),
                    'comment' => $l->getComment(),
                ], $offer->getLines()->toArray()),
            ];
        }

        return [
            'initial' => $initial,
            'csrf' => $this->csrf->getToken(self::CSRF_ID)->getValue(),
        ];
    }
}
