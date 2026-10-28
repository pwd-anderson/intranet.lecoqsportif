<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\AggridOptionRepository;
use App\Repository\UserStatExclusionRepository;
use App\Service\Achat;
use App\Service\PlanTransport\PlanTransportImporter;
use App\Service\AgGrid\AgGridColumnBuilder;
use App\Service\Tools\Helpers;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class AchatController extends AbstractController
{
    public function __construct(
        private AggridOptionRepository $aggridOptionRepository,
        private AgGridColumnBuilder $columnBuilder,
    ) {}

    // ################## ROUTES ALIAS #####################
    #[Route('/achat/backlog_fournisseur', name: 'app_backlog_fournisseur')]
    public function backlogFournisseurAlias(): Response
    {
        return $this->achatGeneric('backlog_fournisseur');
    }

    #[Route('/achat/backlog_fournisseur_x3', name: 'app_backlog_fournisseur_x3')]
    public function backlogFournisseurX3Alias(): Response
    {
        return $this->achatGeneric('backlog_fournisseur_x3');
    }

    #[Route('/achat/reception_fournisseur', name: 'app_reception_fournisseur')]
    public function receptionFournisseurAlias(): Response
    {
        return $this->achatGeneric('reception_fournisseur');
    }

    #[Route('/achat/base_articles_tarifs', name: 'app_achat_base_articles_tarifs')]
    public function baseArticlesTarifsAlias(): Response
    {
        return $this->achatGeneric('base_articles_tarifs');
    }

    /**
     * Plan transport prévision (Excel SharePoint PURCHASING) : template dédié (ETD colorée selon le statut
     * de départ, légende, date du dernier import) ; les colonnes restent configurées en base (aggrid_option).
     */
    #[Route('/achat/plan_transport_prevision', name: 'app_plan_transport_prevision')]
    public function planTransportPrevision(PlanTransportImporter $planTransport): Response
    {
        $agridOptions = $this->aggridOptionRepository->findBy(
            ['gridName' => 'plan_transport_prevision_grid'],
            ['orderIndex' => 'ASC']
        );
        $grid = $this->columnBuilder->build($agridOptions);

        return $this->render('achat/plan_transport_prevision.html.twig', [
            'columns' => $grid['columns'],
            'numericColumns' => $grid['numericColumns'],
            'integerColumns' => $grid['integerColumns'],
            'totalColumns' => $grid['totalColumns'],
            'dataUrl' => $this->generateUrl('plan_transport_prevision_json'),
            'lastImport' => $planTransport->getLastImport(),
            'canImport' => $this->canImportPlanTransport(),
        ]);
    }

    /**
     * Bouton « Import » de la stat : relance la commande app:import-plan-transport (même code que le cron) et renvoie
     * un résultat lisible pour le popup. Réservé aux rôles de la stat (PURCHASING + super-users), vérifié côté serveur.
     * En cas d'échec la table garde sa version précédente (import transactionnel de la commande).
     */
    #[Route('/achat/plan_transport_prevision_import', name: 'plan_transport_prevision_import', methods: ['POST'])]
    public function planTransportPrevisionImport(
        Request $request,
        KernelInterface $kernel,
        PlanTransportImporter $planTransport,
        UserStatExclusionRepository $exclusions,
    ): JsonResponse {
        if (!$this->canImportPlanTransport()) {
            return new JsonResponse(['success' => false, 'message' => "Vous n'avez pas le droit de lancer cet import."], Response::HTTP_FORBIDDEN);
        }
        $user = $this->getUser();
        if ($user instanceof User && in_array('app_plan_transport_prevision', $exclusions->findExcludedStatKeysForUser($user), true)) {
            return new JsonResponse(['success' => false, 'message' => "Vous n'avez pas accès à cette statistique."], Response::HTTP_FORBIDDEN);
        }
        if (!$this->isCsrfTokenValid('plan_transport_import', (string) $request->request->get('_token'))) {
            return new JsonResponse(['success' => false, 'message' => 'Session expirée : rechargez la page puis réessayez.'], Response::HTTP_FORBIDDEN);
        }

        // Un seul import à la fois (double clic, deux utilisateurs) : sinon deux remplacements complets se croiseraient.
        $lockFile = $kernel->getProjectDir() . '/var/plan_transport_import.lock';
        $lock = fopen($lockFile, 'c');
        if ($lock === false || !flock($lock, LOCK_EX | LOCK_NB)) {
            return new JsonResponse(['success' => false, 'message' => 'Un import est déjà en cours. Patientez une minute puis rechargez la page.'], Response::HTTP_CONFLICT);
        }

        try {
            // Téléchargement (35 Mo) + lecture + écriture : plus long que la limite PHP par défaut.
            set_time_limit(300);

            $application = new Application($kernel);
            $application->setAutoExit(false);
            $output = new BufferedOutput();
            $exitCode = $application->run(new ArrayInput(['command' => 'app:import-plan-transport']), $output);
            $text = trim(preg_replace('/\s+/u', ' ', $output->fetch()));
        } catch (\Throwable $e) {
            $exitCode = 1;
            $text = $e->getMessage();
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }

        if ($exitCode !== 0) {
            // La commande écrit « Import en échec, la table est inchangée : <raison> ».
            $reason = preg_match('/table est inchangée\s*:\s*(.+?)(?:\s*\[ERROR\]|$)/u', $text, $m) ? trim($m[1]) : $text;

            return new JsonResponse([
                'success' => false,
                'message' => "L'import a échoué. Les données précédentes sont conservées et le service IT a été prévenu par mail.",
                'detail' => mb_substr($reason, 0, 500),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $rows = preg_match('/([\d\s\x{00A0}\x{202F}]+)\s+lignes importées/u', $text, $m) ? (int) preg_replace('/\D+/', '', $m[1]) : null;
        $last = $planTransport->getLastImport();

        return new JsonResponse([
            'success' => true,
            'message' => 'Le plan transport prévision a été mis à jour.',
            'rows' => $rows,
            'sourceFile' => $last['sourceFile'] ?? null,
            'importedAt' => $last['importedAt'] ?? null,
        ]);
    }

    private function canImportPlanTransport(): bool
    {
        return $this->isGranted('ROLE_PURCHASING') || $this->isGranted('ROLE_MANAGEMENT') || $this->isGranted('ROLE_CONTROLLING');
    }

    // ################## ROUTES JSON (inchangées) #####################

    #[Route('/achat/plan_transport_prevision_json', name: 'plan_transport_prevision_json')]
    public function planTransportPrevisionJson(PlanTransportImporter $planTransport, Helpers $helpers): JsonResponse
    {
        return new JsonResponse($helpers->convertArrayToUtf8($planTransport->fetchAll()));
    }


    #[Route('/achat/base_articles_tarifs_json', name: 'base_articles_tarifs_json')]
    public function baseArticlesTarifsJson(Achat $achat, Helpers $helpers): JsonResponse
    {
        $this->denyUnlessBaseArticlesAccess();

        return new JsonResponse($helpers->convertArrayToUtf8($achat->getBaseArticlesTarifs()));
    }

    /**
     * Prix d'achat, landed cost et tarifs : réservé à MANAGEMENT et CONTROLLING (pour l'instant), vérifié côté
     * serveur sur la page ET la route JSON (le rôle seul ne gouverne que l'affichage du menu pour les autres stats).
     * L'exclusion par utilisateur reste gérée par StatAccessSubscriber (clé app_achat_base_articles_tarifs).
     */
    private function denyUnlessBaseArticlesAccess(): void
    {
        if (!$this->isGranted('ROLE_MANAGEMENT') && !$this->isGranted('ROLE_CONTROLLING')) {
            throw $this->createAccessDeniedException();
        }
    }

    // Routes JSON
    #[Route('/achat/backlog_fournisseur_json', name: 'backlog_fournisseur_json')]
    public function backlogFournisseurJson(Achat $achat, Helpers $helpers): JsonResponse
    {
        $data = $achat->getBAcklogFournisseurNavision();
        $dataUtf8 = $helpers->convertArrayToUtf8($data);

        return new JsonResponse($dataUtf8);
    }

    #[Route('/achat/backlog_fournisseur_x3_json', name: 'backlog_fournisseur_x3_json')]
    public function backlogFournisseurX3Json(Achat $achat, Helpers $helpers): JsonResponse
    {
        $data = $achat->getBacklogFournisseur();
        $dataUtf8 = $helpers->convertArrayToUtf8($data);

        return new JsonResponse($dataUtf8);
    }

    #[Route('/achat/backlog_fournisseur_intersites_json', name: 'backlog_fournisseur_intersites_json')]
    public function backlogFournisseurIntersitesJson(Achat $achat, Helpers $helpers): JsonResponse
    {
        $data = $achat->getBacklogFournisseurIntersites();
        $dataUtf8 = $helpers->convertArrayToUtf8($data);

        return new JsonResponse($dataUtf8);
    }

    #[Route('/achat/reception_fournisseur_json', name: 'reception_fournisseur_json')]
    public function receptionFournisseurJson(Achat $achat, Helpers $helpers): JsonResponse
    {
        $data = $achat->getReceptionFournisseur();
        $dataUtf8 = $helpers->convertArrayToUtf8($data);

        return new JsonResponse($dataUtf8);
    }


    #[Route(
        '/achat/{type}',
        name: 'app_achat_generic',
        requirements: ['type' => 'backlog_fournisseur|backlog_fournisseur_x3|reception_fournisseur']
    )]
    public function achatGeneric(string $type): Response
    {
        if ($type === 'base_articles_tarifs') {
            $this->denyUnlessBaseArticlesAccess();
        }

        $config = [
            'base_articles_tarifs' => [
                'gridName' => 'base_articles_tarifs_grid',
                'title' => 'Base Articles/Tarifs',
                'jsonRoute' => 'base_articles_tarifs_json',
            ],
            'backlog_fournisseur' => [
                'gridName' => 'backlog_fournisseur_grid',
                'title' => 'Backlog Fournisseur (Nav.)',
                'jsonRoute' => 'backlog_fournisseur_json',
            ],
            'backlog_fournisseur_x3' => [
                'gridName' => 'backlog_fournisseur_x3_grid',
                'title' => 'Backlog Fournisseur',
                'jsonRoute' => 'backlog_fournisseur_x3_json',
            ],
            'reception_fournisseur' => [
                'gridName' => 'reception_fournisseur_grid',
                'title' => 'Réception Fournisseur',
                'jsonRoute' => 'reception_fournisseur_json',
            ],
        ];

        if (!isset($config[$type])) {
            throw $this->createNotFoundException(sprintf('Unknown achat type "%s"', $type));
        }

        $gridConfig = $config[$type];

        $agridOptions = $this->aggridOptionRepository->findBy(
            ['gridName' => $gridConfig['gridName']],
            ['orderIndex' => 'ASC']
        );

        $grid = $this->columnBuilder->build($agridOptions);

        return $this->render('achat/achat_generic.html.twig', [
            'title' => $gridConfig['title'],
            'columns' => $grid['columns'],
            'numericColumns' => $grid['numericColumns'],
            'integerColumns' => $grid['integerColumns'],
            'totalColumns' => $grid['totalColumns'],
            'dataUrl' => $this->generateUrl($gridConfig['jsonRoute']),
            'intersitesUrl' => $type === 'backlog_fournisseur_x3'   // 🆕
                ? $this->generateUrl('backlog_fournisseur_intersites_json')
                : null,
            'type' => $type,
        ]);
    }
}
