<?php

namespace App\Service\Oc;

use App\Entity\Oc\Offer;
use App\Entity\Oc\OfferHistory;
use App\Entity\Oc\OfferLine;
use App\Entity\User;
use App\Service\Oc\Input\OfferInput;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Enregistrement des OC : création / modification d'un brouillon, soumission, annulation.
 *
 * À CHAQUE enregistrement le serveur relit lui-même X3 (client, remise REMCLI, code tarif, tarif, MSRP,
 * collection) et fige ces valeurs avec la date de lecture : les valeurs envoyées par le navigateur ne servent
 * qu'à ce que le commercial a décidé (tarif OC, MSRP utilisé, TVA, quantités, commentaires). Les droits
 * (qui peut faire quoi) sont contrôlés par OfferVoter dans le contrôleur ; ici, la cohérence métier.
 */
class OfferService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly OcX3Lookup $x3,
        private readonly OfferNumberGenerator $numbers,
        private readonly OfferWorkflow $workflow,
        private readonly OfferCalculator $calculator,
        private readonly OfferMailer $mailer,
        private readonly OfferPdfGenerator $pdf,
        private readonly OfferFlowService $flows,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * Crée un brouillon, ou met à jour le brouillon donné.
     *
     * @param array<string,mixed> $data données reçues du navigateur
     *
     * @throws OfferValidationException données invalides ou client / article introuvable dans X3
     */
    public function save(array $data, User $user, ?Offer $existing = null, ?Offer $sourceOffer = null): Offer
    {
        $input = OfferInput::fromArray($data);

        if ($existing !== null) {
            if ($existing->getStatus() !== OcStatus::DRAFT) {
                throw new OfferValidationException(['Seule une OC en brouillon peut être modifiée.']);
            }
            if ($existing->getCustomerCode() !== $input->customerCode) {
                throw new OfferValidationException(['Le client ne peut pas être modifié : créez une nouvelle OC.']);
            }
        }

        // Relecture X3 AVANT toute écriture : si X3 ne répond pas, rien n'est enregistré.
        $readAt = new \DateTimeImmutable();
        $customer = $this->x3->getCustomer($input->customerCode)
            ?? throw new OfferValidationException(["Client {$input->customerCode} introuvable dans X3 (ou X3 indisponible)."]);
        $discount = $this->x3->getCustomerDiscount($input->customerCode);
        $prices = $this->x3->getArticlesPrices($input->customerCode, array_map(static fn ($l) => $l->articleCode, $input->lines));

        $missing = [];
        foreach ($input->lines as $line) {
            if (!isset($prices[$line->articleCode])) {
                $missing[] = $line->articleCode;
            }
        }
        if ($missing !== []) {
            throw new OfferValidationException(['Article(s) introuvable(s) dans X3 (ou X3 indisponible) : ' . implode(', ', $missing) . '.']);
        }

        // Prix à envoyer à X3, calculés AVANT de toucher à l'entité (une erreur ne doit rien modifier).
        $discountPercent = isset($discount['REMISE']) ? (float) $discount['REMISE'] : null;
        $injected = [];
        $injectionGaps = [];
        foreach ($input->lines as $in) {
            try {
                $injected[$in->articleCode] = $this->calculator->injectedPrice($in->tariff, $discountPercent);
            } catch (\InvalidArgumentException $e) {
                throw new OfferValidationException(["Article {$in->articleCode} : " . $e->getMessage()]);
            }
            if (!$injected[$in->articleCode]['exact']) {
                $injectionGaps[] = $in->articleCode;
            }
        }

        $isNew = $existing === null;
        $offer = $existing ?? new Offer();
        $fromStatus = $isNew ? null : $offer->getStatus();

        $offer->setCustomerCode((string) $customer['CODE_CLIENT'])
            ->setCustomerName((string) $customer['NOM_CLIENT'])
            ->setCustomerCurrency($customer['DEVISE'] ?? null)
            ->setCustomerTariffCode($customer['CODE_TARIF'] ?? null)
            ->setCustomerDiscount($discount['REMISE'] ?? null)
            ->setCustomerDiscountStart(isset($discount['DATE_DEBUT']) ? new \DateTimeImmutable($discount['DATE_DEBUT']) : null)
            ->setCustomerDiscountEnd(isset($discount['DATE_FIN']) ? new \DateTimeImmutable($discount['DATE_FIN']) : null)
            ->setX3ReadAt($readAt)
            ->setStartDate($input->startDate)
            ->setEndDate($input->endDate)
            ->setComment($input->comment)
            ->touch();

        if ($isNew) {
            $offer->setNumber($this->numbers->generate($offer->getCustomerCode(), $readAt))
                ->setCreatedBy($user)
                ->setCreatedByName(self::displayName($user))
                ->setSourceOffer($sourceOffer);
        }

        $offer->clearLines();
        foreach ($input->lines as $i => $in) {
            $x3 = $prices[$in->articleCode];
            $vat = $in->vatRate ?? OfferCalculator::DEFAULT_VAT;

            $offer->addLine((new OfferLine())
                ->setPosition($i + 1)
                ->setArticleCode((string) $x3['ARTICLE'])
                ->setArticleLabel($x3['DESIGNATION'] ?? null)
                ->setCollection($x3['COLLECTION'] ?? null)
                ->setModelCode($x3['MODELE'] ?? null)
                ->setX3Tariff($x3['TARIF'] ?? null)
                ->setX3Msrp($x3['MSRP'] ?? null)
                ->setX3ReadAt($readAt)
                ->setTariff($in->tariff)
                ->setInjectedPrice($injected[$in->articleCode]['price'])
                ->setInjectedDiscount($discountPercent)
                ->setMsrp($in->msrp)
                ->setVatRate($vat)
                ->setMargin($this->calculator->margin($in->msrp, $vat, $in->tariff))
                ->setMinQty($in->minQty)
                ->setMaxQty($in->maxQty)
                ->setComment($in->comment));
        }

        $offer->addHistory(new OfferHistory(
            $isNew ? OcAction::CREATED : OcAction::UPDATED,
            $fromStatus,
            OcStatus::DRAFT,
            $user,
            self::displayName($user),
            null,
            ['lines' => count($input->lines), 'x3ReadAt' => $readAt->format('c'), 'customerDiscount' => $discountPercent]
                + ($injectionGaps !== [] ? ['injectionGaps' => $injectionGaps] : [])
                + ($sourceOffer !== null ? ['sourceOffer' => $sourceOffer->getNumber()] : []),
        ));

        $this->em->wrapInTransaction(function () use ($offer): void {
            $this->em->persist($offer);
            $this->em->flush();
        });

        return $offer;
    }

    /**
     * Avertissements à afficher sur une OC (non bloquants). Aujourd'hui : la remise client REMCLI ne s'applique dans X3
     * qu'à partir de sa date de début ; avant, le client paierait le prix envoyé SANS remise.
     *
     * @return list<string>
     */
    public function warnings(Offer $offer): array
    {
        $warnings = [];
        $discount = $offer->getCustomerDiscount();
        $discountStart = $offer->getCustomerDiscountStart();

        if ($discount !== null && (float) $discount > 0 && $discountStart !== null && $discountStart > $offer->getStartDate()) {
            $warnings[] = sprintf(
                'La remise client (%s %%) ne s\'applique dans X3 qu\'à partir du %s alors que l\'OC débute le %s : jusqu\'à cette date le client paierait le prix envoyé à X3 (supérieur au tarif OC), sans remise.',
                rtrim(rtrim(number_format((float) $discount, 2, ',', ''), '0'), ','),
                $discountStart->format('d/m/Y'),
                $offer->getStartDate()->format('d/m/Y'),
            );
        }

        return $warnings;
    }

    /**
     * À appeler JUSTE AVANT l'envoi à X3 (et à la validation) : relit la remise client dans X3 et la compare à celle
     * figée dans l'OC. Si elle a changé, X3 appliquerait la nouvelle remise sur un prix calculé avec l'ancienne : le
     * prix net facturé ne serait plus celui validé. Renvoie un message (à afficher au valideur) ou null si identique.
     * X3 injoignable : message d'erreur aussi (on n'envoie pas à l'aveugle).
     */
    public function discountDrift(Offer $offer): ?string
    {
        $current = $this->x3->getCustomerDiscount($offer->getCustomerCode());
        $snapshot = $offer->getCustomerDiscount();

        $now = isset($current['REMISE']) ? round((float) $current['REMISE'], 4) : null;
        $was = $snapshot !== null ? round((float) $snapshot, 4) : null;

        if ($now === $was) {
            return null;
        }

        return sprintf(
            'La remise client a changé dans X3 depuis l\'enregistrement (%s → %s, ou X3 ne répond pas) : le prix à envoyer n\'est plus valable, l\'OC doit être refaite.',
            $was === null ? 'aucune' : $was . ' %',
            $now === null ? 'aucune' : $now . ' %',
        );
    }

    /** @throws OfferValidationException brouillon incomplet ; \LogicException si la transition est interdite */
    public function submit(Offer $offer, User $user): void
    {
        if ($offer->getLines()->isEmpty()) {
            throw new OfferValidationException(['Une OC sans article ne peut pas être soumise.']);
        }

        $this->transition($offer, OcAction::SUBMITTED, $user, null, static function (Offer $o): void {
            $o->setSubmittedAt(new \DateTimeImmutable());
        });

        // Après l'enregistrement : un échec d'envoi ne défait pas la soumission (voir OfferMailer).
        $this->mailer->notifySubmitted($offer);
    }

    /**
     * Validation par la Direction (le droit est contrôlé par OfferVoter::DECIDE dans le contrôleur).
     *
     * Refusée si la remise client a changé dans X3 depuis l'enregistrement : le prix envoyé à X3 a été calculé avec
     * l'ancienne remise, X3 appliquerait la nouvelle et le prix net facturé ne serait plus celui qu'on valide.
     *
     * @throws OfferValidationException remise changée ; \LogicException si l'OC n'est pas « soumise »
     */
    public function validate(Offer $offer, User $user): void
    {
        $this->workflow->target($offer->getStatus(), OcAction::VALIDATED); // LogicException si impossible, avant de lire X3

        if (($drift = $this->discountDrift($offer)) !== null) {
            throw new OfferValidationException([$drift]);
        }

        $this->transition($offer, OcAction::VALIDATED, $user, null, static function (Offer $o): void {
            $o->setDecidedAt(new \DateTimeImmutable());
        });

        // La validation est enregistrée : le PDF et son envoi ne peuvent plus la défaire (échec tracé dans l'historique).
        $this->sendValidationPdf($offer, $user);
        $this->prepareX3Flow($offer, $user);
    }

    /**
     * Génère les flux XML X3 de l'OC validée. Par défaut (envoi désactivé) ils sont seulement conservés (« préparés »,
     * visibles à la Direction) ; activé, ils sont placés dans sales_web_service et l'OC passe « Envoyée à X3 ».
     * Ne défait jamais la validation : un échec est tracé (« Erreur d'envoi à X3 ») et notifié à l'admin.
     */
    public function prepareX3Flow(Offer $offer, User $user): void
    {
        try {
            $flows = $this->flows->prepare($offer);
            $queued = $this->flows->queue($offer);

            $offer->addHistory(new OfferHistory(
                OcAction::X3_FLOW_PREPARED, $offer->getStatus(), $offer->getStatus(), $user, self::displayName($user),
                $queued > 0 ? sprintf('%d flux placés en file pour X3.', $queued) : 'Envoi à X3 désactivé : flux conservés sans être envoyés.',
                ['flows' => count($flows), 'queued' => $queued],
            ));
            $this->em->wrapInTransaction(function () use ($offer): void {
                $this->em->persist($offer);
                $this->em->flush();
            });

            if ($queued > 0) {
                $this->transition($offer, OcAction::SENT_TO_X3, $user, null);
            }
        } catch (\Throwable $e) {
            $this->mailer->reportError('flux X3 de l\'OC ' . $offer->getNumber(), $e);
            $offer->addHistory(new OfferHistory(OcAction::X3_ERROR, $offer->getStatus(), $offer->getStatus(), $user, self::displayName($user), 'Flux X3 non généré : ' . $e->getMessage()));
            try {
                $this->em->wrapInTransaction(function () use ($offer): void {
                    $this->em->persist($offer);
                    $this->em->flush();
                });
            } catch (\Throwable $inner) {
                $this->logger->error('LCS OC : historique de l\'erreur de flux X3', ['exception' => $inner]);
            }
        }
    }

    /**
     * Génère le PDF de l'offre et l'envoie au commercial créateur ; trace le résultat dans l'historique
     * (« PDF envoyé » ou « Échec de l'envoi du PDF » + motif). Sert à la validation et au renvoi manuel.
     * Ne lève jamais d'exception. Sans effet (renvoie false, rien tracé) si les mails sont désactivés (environnement de test).
     */
    public function sendValidationPdf(Offer $offer, User $user): bool
    {
        if (!$this->mailer->isEnabled()) {
            return false;
        }

        try {
            $sent = $this->mailer->notifyValidated($offer, $this->pdf->generate($offer), $this->pdf->filename($offer));
            $problem = $sent ? null : 'Le mail n\'a pas pu être envoyé (voir le journal).';
        } catch (\Throwable $e) {
            $problem = 'Génération du PDF impossible : ' . $e->getMessage();
            $this->mailer->reportError('PDF de l\'OC ' . $offer->getNumber(), $e);
        }

        $offer->addHistory(new OfferHistory(
            $problem === null ? OcAction::PDF_SENT : OcAction::PDF_FAILED,
            $offer->getStatus(),
            $offer->getStatus(),
            $user,
            self::displayName($user),
            $problem,
        ));
        $this->em->wrapInTransaction(function () use ($offer): void {
            $this->em->persist($offer);
            $this->em->flush();
        });

        return $problem === null;
    }

    /**
     * Rejet par la Direction : motif OBLIGATOIRE ; le rejet est définitif (le commercial crée une nouvelle OC).
     *
     * @throws OfferValidationException motif manquant ; \LogicException si l'OC n'est pas « soumise »
     */
    public function reject(Offer $offer, User $user, ?string $reason): void
    {
        $reason = $reason !== null ? trim($reason) : '';
        if (mb_strlen($reason) < 3) {
            throw new OfferValidationException(['Le motif du rejet est obligatoire.']);
        }
        $reason = mb_substr($reason, 0, 1000);

        $this->transition($offer, OcAction::REJECTED, $user, $reason, static function (Offer $o): void {
            $o->setDecidedAt(new \DateTimeImmutable());
        });

        $this->mailer->notifyRejected($offer, $reason);
    }

    public function cancel(Offer $offer, User $user, ?string $reason = null): void
    {
        $this->transition($offer, OcAction::CANCELLED, $user, $reason, static function (Offer $o): void {
            $o->setDecidedAt(new \DateTimeImmutable());
        });
    }

    /**
     * @param callable(Offer): void|null $apply
     */
    private function transition(Offer $offer, OcAction $action, User $user, ?string $reason, ?callable $apply = null): void
    {
        $from = $offer->getStatus();
        $to = $this->workflow->target($from, $action);

        $offer->setStatus($to)->touch();
        if ($apply !== null) {
            $apply($offer);
        }
        $offer->addHistory(new OfferHistory($action, $from, $to, $user, self::displayName($user), $reason !== null && trim($reason) !== '' ? trim($reason) : null));

        $this->em->wrapInTransaction(function () use ($offer): void {
            $this->em->persist($offer);
            $this->em->flush();
        });
    }

    public static function displayName(User $user): string
    {
        $name = trim(($user->getFirstname() ?? '') . ' ' . ($user->getLastname() ?? ''));

        return $name !== '' ? $name : (string) $user->getEmail();
    }
}
