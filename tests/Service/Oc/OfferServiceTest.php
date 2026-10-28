<?php

namespace App\Tests\Service\Oc;

use App\Entity\Oc\Offer;
use App\Entity\Oc\OfferHistory;
use App\Entity\User;
use App\Service\Oc\OcAction;
use App\Service\Oc\OcStatus;
use App\Service\Oc\OcX3Lookup;
use App\Service\Oc\OfferCalculator;
use App\Service\Oc\OfferFlowService;
use App\Service\Oc\OfferMailer;
use App\Service\Oc\OfferNumberGenerator;
use App\Service\Oc\OfferPdfGenerator;
use Psr\Log\NullLogger;
use App\Service\Oc\OfferService;
use App\Service\Oc\OfferValidationException;
use App\Service\Oc\OfferWorkflow;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class OfferServiceTest extends TestCase
{
    private EntityManagerInterface&MockObject $em;
    private OcX3Lookup&MockObject $x3;
    private OfferNumberGenerator&MockObject $numbers;
    private OfferMailer&MockObject $mailer;
    private OfferPdfGenerator&MockObject $pdf;
    private OfferFlowService&MockObject $flows;
    private int $flushes = 0;

    protected function setUp(): void
    {
        $this->flushes = 0;
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->em->method('wrapInTransaction')->willReturnCallback(static fn (callable $fn) => $fn());
        $this->em->method('flush')->willReturnCallback(function (): void { ++$this->flushes; });

        $this->x3 = $this->createMock(OcX3Lookup::class);
        $this->mailer = $this->createMock(OfferMailer::class);
        $this->pdf = $this->createMock(OfferPdfGenerator::class);
        $this->flows = $this->createMock(OfferFlowService::class);
        $this->numbers = $this->createMock(OfferNumberGenerator::class);
        $this->numbers->method('generate')->willReturn('OC202610' . '20548' . '12345678');
    }

    private function service(): OfferService
    {
        return new OfferService($this->em, $this->x3, $this->numbers, new OfferWorkflow(), new OfferCalculator(), $this->mailer, $this->pdf, $this->flows, new \App\Service\X3\X3EnvironmentProvider(new \Symfony\Component\HttpFoundation\RequestStack(), 'test'), new NullLogger());
    }

    private function user(): User
    {
        $u = $this->createStub(User::class);
        $u->method('getId')->willReturn(7);
        $u->method('getFirstname')->willReturn('Alice');
        $u->method('getLastname')->willReturn('Martin');
        $u->method('getEmail')->willReturn('alice@example.test');

        return $u;
    }

    /** @return array<string,mixed> */
    private function data(array $override = []): array
    {
        return array_replace([
            'customerCode' => '20548',
            'startDate' => '2026-11-01',
            'endDate' => '2027-10-31',
            'comment' => 'Offre test',
            'lines' => [
                ['articleCode' => '2210037', 'tariff' => 30, 'msrp' => 60, 'vatRate' => 20, 'minQty' => 0, 'maxQty' => 500, 'comment' => 'ligne 1'],
                ['articleCode' => '1511960', 'tariff' => 50, 'msrp' => null, 'vatRate' => null, 'minQty' => 5, 'maxQty' => 50, 'comment' => ''],
            ],
        ], $override);
    }

    private function x3Ok(): void
    {
        $this->x3->method('getCustomer')->willReturn(['CODE_CLIENT' => '20548', 'NOM_CLIENT' => 'T2 SPORT SAS', 'DEVISE' => 'EUR', 'CODE_TARIF' => 'WSPEUR']);
        $this->x3->method('getCustomerDiscount')->willReturn(['REMISE' => 13, 'DATE_DEBUT' => '2027-01-01', 'DATE_FIN' => '2050-12-31']);
        $this->x3->method('getArticlesPrices')->willReturn([
            '2210037' => ['ARTICLE' => '2210037', 'DESIGNATION' => 'EFRO 22 Gants', 'MODELE' => '221H028', 'COLLECTION' => '2026-01-SS', 'TARIF' => 19.5, 'MSRP' => 39],
            '1511960' => ['ARTICLE' => '1511960', 'DESIGNATION' => 'Polo', 'MODELE' => '151A265', 'COLLECTION' => '2015-02-FW', 'TARIF' => null, 'MSRP' => 75],
        ]);
    }

    public function testCreateFreezesX3ValuesAndWritesHistory(): void
    {
        $this->x3Ok();
        $this->em->expects(self::once())->method('persist')->with(self::isInstanceOf(Offer::class));

        $offer = $this->service()->save($this->data(), $this->user());

        // En-tête : client et remise figés depuis X3
        self::assertSame(OcStatus::DRAFT, $offer->getStatus());
        self::assertSame('OC2026102054812345678', $offer->getNumber());
        self::assertSame('T2 SPORT SAS', $offer->getCustomerName());
        self::assertSame('WSPEUR', $offer->getCustomerTariffCode());
        self::assertSame('13.0000', $offer->getCustomerDiscount());
        self::assertSame('2027-01-01', $offer->getCustomerDiscountStart()?->format('Y-m-d'));
        self::assertSame('Alice Martin', $offer->getCreatedByName());

        // Lignes : valeurs X3 d'un côté, décision du commercial de l'autre
        self::assertCount(2, $offer->getLines());
        $l1 = $offer->getLines()[0];
        self::assertSame('EFRO 22 Gants', $l1->getArticleLabel());
        self::assertSame('2026-01-SS', $l1->getCollection());
        self::assertSame('19.5000', $l1->getX3Tariff());
        self::assertSame('39.0000', $l1->getX3Msrp());
        self::assertSame('30.0000', $l1->getTariff());
        self::assertSame('60.0000', $l1->getMsrp());
        self::assertSame('20.00', $l1->getVatRate());
        // marge recalculée côté serveur : PV net HT = 60 / 1,2 = 50 ; (50 - 30) / 50 = 40 %
        self::assertSame('40.0000', $l1->getMargin());

        $l2 = $offer->getLines()[1];
        self::assertNull($l2->getX3Tariff(), 'pas de tarif X3 pour ce groupe : conservé tel quel (null)');
        self::assertNull($l2->getMargin(), 'sans MSRP saisi, pas de marge');
        self::assertSame(5, $l2->getMinQty());

        // Historique
        self::assertCount(1, $offer->getHistory());
        $h = $offer->getHistory()[0];
        self::assertSame(OcAction::CREATED, $h->getAction());
        self::assertNull($h->getFromStatus());
        self::assertSame(OcStatus::DRAFT, $h->getToStatus());
        self::assertSame('Alice Martin', $h->getUserName());
        self::assertSame(2, $h->getContext()['lines']);
    }

    public function testNothingIsSavedWhenCustomerIsNotFoundInX3(): void
    {
        $this->x3->method('getCustomer')->willReturn(null);
        $this->em->expects(self::never())->method('persist');

        try {
            $this->service()->save($this->data(), $this->user());
            self::fail('exception attendue');
        } catch (OfferValidationException $e) {
            self::assertStringContainsString('introuvable dans X3', $e->getErrors()[0]);
        }
        self::assertSame(0, $this->flushes);
    }

    public function testNothingIsSavedWhenAnArticleIsMissingInX3(): void
    {
        $this->x3->method('getCustomer')->willReturn(['CODE_CLIENT' => '20548', 'NOM_CLIENT' => 'T2', 'DEVISE' => 'EUR', 'CODE_TARIF' => 'WSPEUR']);
        $this->x3->method('getCustomerDiscount')->willReturn(null);
        $this->x3->method('getArticlesPrices')->willReturn(['2210037' => ['ARTICLE' => '2210037']]);
        $this->em->expects(self::never())->method('persist');

        try {
            $this->service()->save($this->data(), $this->user());
            self::fail('exception attendue');
        } catch (OfferValidationException $e) {
            self::assertStringContainsString('1511960', $e->getErrors()[0]);
        }
    }

    public function testCustomerWithoutDiscountIsStoredWithNullDiscount(): void
    {
        $this->x3->method('getCustomer')->willReturn(['CODE_CLIENT' => '20548', 'NOM_CLIENT' => 'T2', 'DEVISE' => 'EUR', 'CODE_TARIF' => 'WSPEUR']);
        $this->x3->method('getCustomerDiscount')->willReturn(null);
        $this->x3->method('getArticlesPrices')->willReturn(['2210037' => ['ARTICLE' => '2210037', 'DESIGNATION' => 'G', 'TARIF' => 1, 'MSRP' => 2]]);

        $offer = $this->service()->save($this->data(['lines' => [['articleCode' => '2210037', 'tariff' => 5]]]), $this->user());

        self::assertNull($offer->getCustomerDiscount());
        self::assertNull($offer->getCustomerDiscountStart());
    }

    public function testUpdateReplacesLinesAndAddsAnUpdateHistoryEntry(): void
    {
        $this->x3Ok();
        $service = $this->service();
        $offer = $service->save($this->data(), $this->user());

        $updated = $service->save($this->data(['lines' => [['articleCode' => '2210037', 'tariff' => 25]]]), $this->user(), $offer);

        self::assertSame($offer, $updated);
        self::assertCount(1, $updated->getLines());
        self::assertSame('25.0000', $updated->getLines()[0]->getTariff());
        self::assertCount(2, $updated->getHistory());
        self::assertSame(OcAction::UPDATED, $updated->getHistory()[1]->getAction());
        self::assertSame(OcStatus::DRAFT, $updated->getHistory()[1]->getFromStatus());
    }

    public function testCustomerCannotBeChangedOnExistingOffer(): void
    {
        $this->x3Ok();
        $service = $this->service();
        $offer = $service->save($this->data(), $this->user());

        $this->expectException(OfferValidationException::class);
        $this->expectExceptionMessage('client ne peut pas être modifié');
        $service->save($this->data(['customerCode' => '99999']), $this->user(), $offer);
    }

    public function testOnlyDraftsCanBeModified(): void
    {
        $this->x3Ok();
        $service = $this->service();
        $offer = $service->save($this->data(), $this->user());
        $service->submit($offer, $this->user());

        $this->expectException(OfferValidationException::class);
        $this->expectExceptionMessage('brouillon');
        $service->save($this->data(), $this->user(), $offer);
    }

    public function testSubmitMovesToSubmittedAndTracesIt(): void
    {
        $this->x3Ok();
        $service = $this->service();
        $offer = $service->save($this->data(), $this->user());

        $service->submit($offer, $this->user());

        self::assertSame(OcStatus::SUBMITTED, $offer->getStatus());
        self::assertNotNull($offer->getSubmittedAt());
        $last = $offer->getHistory()->last();
        self::assertSame(OcAction::SUBMITTED, $last->getAction());
        self::assertSame(OcStatus::DRAFT, $last->getFromStatus());
        self::assertSame(OcStatus::SUBMITTED, $last->getToStatus());
    }

    public function testSubmitTwiceIsRefused(): void
    {
        $this->x3Ok();
        $service = $this->service();
        $offer = $service->save($this->data(), $this->user());
        $service->submit($offer, $this->user());

        $this->expectException(\LogicException::class);
        $service->submit($offer, $this->user());
    }

    public function testSubmitWithoutLinesIsRefused(): void
    {
        $this->expectException(OfferValidationException::class);
        $this->service()->submit(new Offer(), $this->user());
    }

    public function testCancelKeepsTheReasonInTheHistory(): void
    {
        $this->x3Ok();
        $service = $this->service();
        $offer = $service->save($this->data(), $this->user());

        $service->cancel($offer, $this->user(), '  Client perdu  ');

        self::assertSame(OcStatus::CANCELLED, $offer->getStatus());
        self::assertSame('Client perdu', $offer->getHistory()->last()->getReason());
        self::assertNotNull($offer->getDecidedAt());
    }

    public function testCancelledOfferCannotBeSubmitted(): void
    {
        $this->x3Ok();
        $service = $this->service();
        $offer = $service->save($this->data(), $this->user());
        $service->cancel($offer, $this->user());

        $this->expectException(\LogicException::class);
        $service->submit($offer, $this->user());
    }

    // ── Prix envoyé à X3 (remise client appliquée à toutes les lignes) ───────

    public function testInjectedPriceCompensatesTheCustomerDiscountOnEveryLine(): void
    {
        $this->x3Ok(); // remise client 13 %
        $offer = $this->service()->save($this->data(['lines' => [
            ['articleCode' => '2210037', 'tariff' => 87, 'msrp' => 200],
            ['articleCode' => '1511960', 'tariff' => 43.5, 'msrp' => 100],
        ]]), $this->user());

        // 87 / 0,87 = 100 ; 43,5 / 0,87 = 50 : X3 reappliquera 13 % et le client paiera bien 87 et 43,5.
        self::assertSame('100.0000', $offer->getLines()[0]->getInjectedPrice());
        self::assertSame('50.0000', $offer->getLines()[1]->getInjectedPrice());
        self::assertSame('13.0000', $offer->getLines()[0]->getInjectedDiscount());
        self::assertSame('87.0000', $offer->getLines()[0]->getTariff(), 'le tarif OC saisi reste intact');
        self::assertSame(13.0, $offer->getHistory()[0]->getContext()['customerDiscount']);
        self::assertArrayNotHasKey('injectionGaps', $offer->getHistory()[0]->getContext());
    }

    public function testWithoutCustomerDiscountTheNetPriceIsSentAsIs(): void
    {
        $this->x3->method('getCustomer')->willReturn(['CODE_CLIENT' => '20548', 'NOM_CLIENT' => 'T2', 'DEVISE' => 'EUR', 'CODE_TARIF' => 'WSPEUR']);
        $this->x3->method('getCustomerDiscount')->willReturn(null);
        $this->x3->method('getArticlesPrices')->willReturn(['2210037' => ['ARTICLE' => '2210037', 'DESIGNATION' => 'G', 'TARIF' => 1, 'MSRP' => 2]]);

        $offer = $this->service()->save($this->data(['lines' => [['articleCode' => '2210037', 'tariff' => 59.25]]]), $this->user());

        self::assertSame('59.2500', $offer->getLines()[0]->getInjectedPrice());
        self::assertNull($offer->getLines()[0]->getInjectedDiscount());
    }

    public function testAbsurdCustomerDiscountIsRefusedAndNothingIsSaved(): void
    {
        $this->x3->method('getCustomer')->willReturn(['CODE_CLIENT' => '20548', 'NOM_CLIENT' => 'T2', 'DEVISE' => 'EUR', 'CODE_TARIF' => 'WSPEUR']);
        $this->x3->method('getCustomerDiscount')->willReturn(['REMISE' => 100, 'DATE_DEBUT' => '2027-01-01', 'DATE_FIN' => '2050-12-31']);
        $this->x3->method('getArticlesPrices')->willReturn(['2210037' => ['ARTICLE' => '2210037', 'DESIGNATION' => 'G', 'TARIF' => 1, 'MSRP' => 2]]);
        $this->em->expects(self::never())->method('persist');

        $this->expectException(OfferValidationException::class);
        $this->expectExceptionMessage('Remise client invalide');
        $this->service()->save($this->data(['lines' => [['articleCode' => '2210037', 'tariff' => 10]]]), $this->user());
    }

    public function testWarningWhenTheDiscountStartsAfterTheOfferStarts(): void
    {
        $this->x3Ok(); // remise valable à partir du 2027-01-01 ; l'OC débute le 2026-11-01
        $offer = $this->service()->save($this->data(), $this->user());

        $warnings = $this->service()->warnings($offer);

        self::assertCount(1, $warnings);
        self::assertStringContainsString('01/01/2027', $warnings[0]);
        self::assertStringContainsString('01/11/2026', $warnings[0]);
        self::assertStringContainsString('13 %', $warnings[0]);
    }

    public function testNoWarningWhenTheDiscountAlreadyAppliesAtOfferStart(): void
    {
        $this->x3Ok();
        $offer = $this->service()->save($this->data(['startDate' => '2027-02-01', 'endDate' => '2027-12-31']), $this->user());

        self::assertSame([], $this->service()->warnings($offer));
    }

    public function testDiscountDriftIsDetectedBeforeSending(): void
    {
        $this->x3->method('getCustomer')->willReturn(['CODE_CLIENT' => '20548', 'NOM_CLIENT' => 'T2', 'DEVISE' => 'EUR', 'CODE_TARIF' => 'WSPEUR']);
        // 1er appel (enregistrement) : 13 % ; appels suivants (avant envoi) : 15 %
        $this->x3->method('getCustomerDiscount')->willReturnOnConsecutiveCalls(
            ['REMISE' => 13, 'DATE_DEBUT' => '2027-01-01', 'DATE_FIN' => '2050-12-31'],
            ['REMISE' => 13, 'DATE_DEBUT' => '2027-01-01', 'DATE_FIN' => '2050-12-31'],
            ['REMISE' => 15, 'DATE_DEBUT' => '2027-01-01', 'DATE_FIN' => '2050-12-31'],
            null,
        );
        $this->x3->method('getArticlesPrices')->willReturn(['2210037' => ['ARTICLE' => '2210037', 'DESIGNATION' => 'G', 'TARIF' => 1, 'MSRP' => 2]]);
        $service = $this->service();
        $offer = $service->save($this->data(['lines' => [['articleCode' => '2210037', 'tariff' => 10]]]), $this->user());

        self::assertNull($service->discountDrift($offer), 'remise inchangée : rien à signaler');
        self::assertStringContainsString('13 % → 15 %', (string) $service->discountDrift($offer));
        self::assertStringContainsString('13 % → aucune', (string) $service->discountDrift($offer), 'remise disparue = changement aussi');
    }

    // ── Soumission, validation et rejet par la Direction ─────────────────────

    public function testSubmitNotifiesTheHeadSale(): void
    {
        $this->x3Ok();
        $service = $this->service();
        $offer = $service->save($this->data(), $this->user());

        $this->mailer->expects(self::once())->method('notifySubmitted')->with($offer);
        $service->submit($offer, $this->user());
    }

    public function testNoMailIsSentWhenSubmitIsRefused(): void
    {
        $this->x3Ok();
        $service = $this->service();
        $offer = $service->save($this->data(), $this->user());
        $service->cancel($offer, $this->user());

        $this->mailer->expects(self::never())->method('notifySubmitted');
        $this->expectException(\LogicException::class);
        $service->submit($offer, $this->user());
    }

    /** Dernière entrée d'historique de cette action (la validation en ajoute plusieurs : PDF, flux X3…). */
    private function entry(Offer $offer, OcAction $action): ?OfferHistory
    {
        $found = null;
        foreach ($offer->getHistory() as $h) {
            if ($h->getAction() === $action) {
                $found = $h;
            }
        }

        return $found;
    }

    private function submittedOffer(OfferService $service): Offer
    {
        $offer = $service->save($this->data(), $this->user());
        $service->submit($offer, $this->user());

        return $offer;
    }

    public function testValidateMovesToValidatedAndTracesTheDecision(): void
    {
        $this->x3Ok();
        $service = $this->service();
        $offer = $this->submittedOffer($service);

        $service->validate($offer, $this->user());

        self::assertSame(OcStatus::VALIDATED, $offer->getStatus());
        self::assertNotNull($offer->getDecidedAt());
        $last = $this->entry($offer, OcAction::VALIDATED);
        self::assertNotNull($last);
        self::assertSame(OcStatus::SUBMITTED, $last->getFromStatus());
        self::assertSame(OcStatus::VALIDATED, $last->getToStatus());
    }

    public function testValidateIsRefusedWhenTheCustomerDiscountChangedInX3(): void
    {
        $this->x3->method('getCustomer')->willReturn(['CODE_CLIENT' => '20548', 'NOM_CLIENT' => 'T2', 'DEVISE' => 'EUR', 'CODE_TARIF' => 'WSPEUR']);
        // enregistrement : 13 % ; au moment de valider : 15 %
        $this->x3->method('getCustomerDiscount')->willReturnOnConsecutiveCalls(
            ['REMISE' => 13, 'DATE_DEBUT' => '2027-01-01', 'DATE_FIN' => '2050-12-31'],
            ['REMISE' => 15, 'DATE_DEBUT' => '2027-01-01', 'DATE_FIN' => '2050-12-31'],
        );
        $this->x3->method('getArticlesPrices')->willReturn(['2210037' => ['ARTICLE' => '2210037', 'DESIGNATION' => 'G', 'TARIF' => 1, 'MSRP' => 2]]);
        $service = $this->service();
        $offer = $service->save($this->data(['lines' => [['articleCode' => '2210037', 'tariff' => 10]]]), $this->user());
        $service->submit($offer, $this->user());
        $historyBefore = $offer->getHistory()->count();

        try {
            $service->validate($offer, $this->user());
            self::fail('La validation aurait dû être refusée.');
        } catch (OfferValidationException $e) {
            self::assertStringContainsString('13 % → 15 %', $e->getErrors()[0]);
        }

        self::assertSame(OcStatus::SUBMITTED, $offer->getStatus(), 'le statut ne change pas');
        self::assertSame($historyBefore, $offer->getHistory()->count(), 'rien n\'est tracé');
    }

    public function testValidateIsRefusedWhenAnExistingSpecialTariffCannotBeClosed(): void
    {
        $this->x3Ok();
        $this->flows->method('conflictMessages')->willReturn(['Article 2210037_40 : la fiche SPL26-0001 commence le jour du début de cette OC.']);
        $this->flows->expects(self::never())->method('send');
        $service = $this->service();
        $offer = $this->submittedOffer($service);

        try {
            $service->validate($offer, $this->user());
            self::fail('La validation aurait dû être refusée.');
        } catch (OfferValidationException $e) {
            self::assertStringContainsString('SPL26-0001', $e->getErrors()[0]);
        }

        self::assertSame(OcStatus::SUBMITTED, $offer->getStatus());
    }

    public function testValidateIsRefusedWhenTheExistingTariffsCannotBeRead(): void
    {
        $this->x3Ok();
        $this->flows->method('conflictMessages')->willThrowException(new \RuntimeException('X3 muet'));
        $service = $this->service();
        $offer = $this->submittedOffer($service);

        try {
            $service->validate($offer, $this->user());
            self::fail('Pas de validation à l\'aveugle.');
        } catch (OfferValidationException $e) {
            self::assertStringContainsString('n\'ont pas pu être contrôlés', $e->getErrors()[0]);
        }

        self::assertSame(OcStatus::SUBMITTED, $offer->getStatus());
    }

    public function testOnlyASubmittedOfferCanBeValidated(): void
    {
        $this->x3Ok();
        $service = $this->service();
        $draft = $service->save($this->data(), $this->user());

        $this->expectException(\LogicException::class);
        $service->validate($draft, $this->user());
    }

    public function testRejectRequiresAReason(): void
    {
        $this->x3Ok();
        $service = $this->service();
        $offer = $this->submittedOffer($service);

        foreach ([null, '', '  ', 'ok'] as $reason) {
            try {
                $service->reject($offer, $this->user(), $reason);
                self::fail('Un rejet sans motif valide aurait dû être refusé.');
            } catch (OfferValidationException $e) {
                self::assertStringContainsString('motif', $e->getErrors()[0]);
            }
        }
        self::assertSame(OcStatus::SUBMITTED, $offer->getStatus());
    }

    public function testRejectKeepsTheReasonAndNotifiesTheCreator(): void
    {
        $this->x3Ok();
        $service = $this->service();
        $offer = $this->submittedOffer($service);

        $this->mailer->expects(self::once())->method('notifyRejected')->with($offer, 'Tarif trop bas pour ce client');
        $service->reject($offer, $this->user(), '  Tarif trop bas pour ce client  ');

        self::assertSame(OcStatus::REJECTED, $offer->getStatus());
        self::assertSame('Tarif trop bas pour ce client', $offer->getHistory()->last()->getReason());
        self::assertNotNull($offer->getDecidedAt());
    }

    public function testARejectionIsFinal(): void
    {
        $this->x3Ok();
        $service = $this->service();
        $offer = $this->submittedOffer($service);
        $service->reject($offer, $this->user(), 'Motif valable');

        foreach ([fn () => $service->validate($offer, $this->user()), fn () => $service->submit($offer, $this->user()), fn () => $service->cancel($offer, $this->user())] as $attempt) {
            try {
                $attempt();
                self::fail('Une OC rejetée ne doit plus changer de statut.');
            } catch (\LogicException) {
                self::assertSame(OcStatus::REJECTED, $offer->getStatus());
            }
        }
    }

    public function testValidationSendsThePdfToTheCreatorAndTracesIt(): void
    {
        $this->x3Ok();
        $this->mailer->method('isEnabled')->willReturn(true);
        $this->pdf->method('generate')->willReturn('%PDF-ok');
        $this->pdf->method('filename')->willReturn('OC_X.pdf');
        $this->mailer->expects(self::once())->method('notifyValidated')->with(self::isInstanceOf(Offer::class), '%PDF-ok', 'OC_X.pdf')->willReturn(true);
        $service = $this->service();
        $offer = $this->submittedOffer($service);

        $service->validate($offer, $this->user());

        self::assertSame(OcStatus::VALIDATED, $offer->getStatus());
        self::assertNotNull($this->entry($offer, OcAction::PDF_SENT));
    }

    public function testAPdfFailureNeverCancelsTheValidationAndIsTraced(): void
    {
        $this->x3Ok();
        $this->mailer->method('isEnabled')->willReturn(true);
        $this->pdf->method('generate')->willThrowException(new \RuntimeException('Dompdf en panne'));
        $this->mailer->expects(self::never())->method('notifyValidated');
        $service = $this->service();
        $offer = $this->submittedOffer($service);

        $service->validate($offer, $this->user());

        self::assertSame(OcStatus::VALIDATED, $offer->getStatus());
        $last = $this->entry($offer, OcAction::PDF_FAILED);
        self::assertNotNull($last);
        self::assertStringContainsString('Dompdf en panne', $last->getReason());
    }

    public function testAMailFailureIsTracedAsPdfFailed(): void
    {
        $this->x3Ok();
        $this->mailer->method('isEnabled')->willReturn(true);
        $this->pdf->method('generate')->willReturn('%PDF');
        $this->pdf->method('filename')->willReturn('OC_X.pdf');
        $this->mailer->method('notifyValidated')->willReturn(false);
        $service = $this->service();
        $offer = $this->submittedOffer($service);

        $service->validate($offer, $this->user());

        self::assertSame(OcStatus::VALIDATED, $offer->getStatus());
        self::assertNotNull($this->entry($offer, OcAction::PDF_FAILED));
    }

    public function testWithMailsDisabledNoPdfIsGeneratedNorTraced(): void
    {
        $this->x3Ok();
        $this->mailer->method('isEnabled')->willReturn(false);
        $this->pdf->expects(self::never())->method('generate');
        $service = $this->service();
        $offer = $this->submittedOffer($service);

        $service->validate($offer, $this->user());

        self::assertNull($this->entry($offer, OcAction::PDF_SENT));
        self::assertNull($this->entry($offer, OcAction::PDF_FAILED));
    }

    public function testValidationSendsTheX3FlowsToTheQueueAndTheOfferBecomesSentToX3(): void
    {
        $this->x3Ok();
        $this->flows->expects(self::once())->method('send')->willReturn([
            new \App\Entity\Oc\OfferFlow(new Offer(), '2210037_40', 'WSUPD7TSPE', '<x/>'),
            new \App\Entity\Oc\OfferFlow(new Offer(), '2210037_40', 'WSCRESPL', '<x/>'),
        ]);
        $service = $this->service();
        $offer = $this->submittedOffer($service);

        $service->validate($offer, $this->user());

        $last = $this->entry($offer, OcAction::X3_FLOW_PREPARED);
        self::assertNotNull($last);
        self::assertStringContainsString('2 flux', $last->getReason());
        self::assertStringContainsString('dont 1 clôtures', $last->getReason());
        self::assertSame(OcStatus::SENT_TO_X3, $offer->getStatus());
        self::assertSame(OcAction::SENT_TO_X3, $offer->getHistory()->last()->getAction());
    }

    public function testAnX3FlowFailureNeverCancelsTheValidationAndIsTraced(): void
    {
        $this->x3Ok();
        $this->flows->method('send')->willThrowException(new \LogicException('prix absent'));
        $this->mailer->expects(self::once())->method('reportError');
        $service = $this->service();
        $offer = $this->submittedOffer($service);

        $service->validate($offer, $this->user());

        self::assertSame(OcStatus::VALIDATED, $offer->getStatus());
        $last = $this->entry($offer, OcAction::X3_ERROR);
        self::assertNotNull($last);
        self::assertStringContainsString('prix absent', $last->getReason());
    }

    public function testValidationIsRefusedWhenTheOfferWasSavedInAnotherX3Environment(): void
    {
        $this->x3Ok();
        $service = $this->service();
        $offer = $this->submittedOffer($service);
        $offer->setX3Env(\App\Service\X3\X3Environment::TEST); // l'environnement actuel (hors dev) est PROD

        try {
            $service->validate($offer, $this->user());
            self::fail('Une OC enregistrée en TEST ne se valide pas depuis PROD.');
        } catch (OfferValidationException $e) {
            self::assertStringContainsString('X3 TEST', $e->getMessage());
        }
        self::assertSame(OcStatus::SUBMITTED, $offer->getStatus());
    }
    // ── Prix par taille : une ligne « variant » (PARENT_TAILLE) à côté, ou à la place, de la ligne parent ──────────

    /** @param array<string, list<string>>|\Throwable $variants tailles connues de X3 par parent (ou erreur X3) */
    private function x3WithSizes(array|\Throwable $variants): void
    {
        $this->x3->method('getCustomer')->willReturn(['CODE_CLIENT' => '20548', 'NOM_CLIENT' => 'T2 SPORT SAS', 'DEVISE' => 'EUR', 'CODE_TARIF' => 'WSPEUR']);
        $this->x3->method('getCustomerDiscount')->willReturn(['REMISE' => 13, 'DATE_DEBUT' => '2027-01-01', 'DATE_FIN' => '2050-12-31']);
        $price = static fn (string $code): array => ['ARTICLE' => $code, 'DESIGNATION' => 'EFRO 22 Gants', 'MODELE' => '221H028', 'COLLECTION' => '2026-01-SS', 'TARIF' => 19.5, 'MSRP' => 39];
        $this->x3->method('getArticlesPrices')->willReturn(['2210037' => $price('2210037'), '2210037_L' => $price('2210037_L'), '2210037_S' => $price('2210037_S'), '2210037_XXL' => $price('2210037_XXL')]);
        if ($variants instanceof \Throwable) {
            $this->x3->method('getVariants')->willThrowException($variants);
        } else {
            $this->x3->method('getVariants')->willReturn($variants);
        }
    }

    /** @param array<string,int|float> $sizes taille => tarif OC */
    private function sizeLines(array $sizes): array
    {
        $lines = [['articleCode' => '2210037', 'tariff' => 35, 'msrp' => 60, 'vatRate' => 20, 'minQty' => 0, 'maxQty' => 500, 'comment' => '']];
        foreach ($sizes as $size => $tariff) {
            $lines[] = ['articleCode' => '2210037_' . $size, 'tariff' => (float) $tariff, 'msrp' => 60, 'vatRate' => 20, 'minQty' => 0, 'maxQty' => 500, 'comment' => ''];
        }

        return $lines;
    }

    public function testVariantLinesAreSavedNextToTheParentLineWithTheirOwnPrice(): void
    {
        $this->x3WithSizes(['2210037' => ['2210037_L', '2210037_M', '2210037_S']]);

        $offer = $this->service()->save($this->data(['lines' => $this->sizeLines(['L' => 30, 'S' => 43])]), $this->user());

        self::assertCount(3, $offer->getLines());
        $byCode = [];
        foreach ($offer->getLines() as $l) {
            $byCode[$l->getArticleCode()] = $l;
        }
        self::assertFalse($byCode['2210037']->isVariant());
        self::assertTrue($byCode['2210037_L']->isVariant());
        self::assertSame('2210037', $byCode['2210037_L']->getParentCode());
        self::assertSame('30.0000', $byCode['2210037_L']->getTariff());
        self::assertSame('43.0000', $byCode['2210037_S']->getTariff());
        self::assertSame('35.0000', $byCode['2210037']->getTariff());
        // prix envoyé à X3 calculé pour CHAQUE ligne avec la remise client (13 %) : 30 / 0,87 = 34,48
        self::assertSame('34.4800', $byCode['2210037_L']->getInjectedPrice());
        self::assertSame('13.0000', $byCode['2210037_L']->getInjectedDiscount());
    }

    public function testVariantLineAloneIsAcceptedAndPricesOnlyThatSize(): void
    {
        $this->x3WithSizes(['2210037' => ['2210037_L', '2210037_S']]);
        $lines = [['articleCode' => '2210037_L', 'tariff' => 30, 'msrp' => 60, 'vatRate' => 20, 'minQty' => 0, 'maxQty' => 500, 'comment' => '']];

        $offer = $this->service()->save($this->data(['lines' => $lines]), $this->user());

        self::assertCount(1, $offer->getLines());
        self::assertSame('2210037_L', $offer->getLines()[0]->getArticleCode());
    }

    public function testSizeUnknownToItsParentIsRefusedAndNothingIsSaved(): void
    {
        $this->x3WithSizes(['2210037' => ['2210037_L', '2210037_S']]);
        $this->em->expects(self::never())->method('persist');

        try {
            $this->service()->save($this->data(['lines' => $this->sizeLines(['XXL' => 30])]), $this->user());
            self::fail('Une taille absente de X3 doit être refusée.');
        } catch (\App\Service\Oc\OfferValidationException $e) {
            self::assertStringContainsString('2210037_XXL', implode(' ', $e->getErrors()));
        }
    }

    public function testSizesCannotBeCheckedWhenX3IsDown(): void
    {
        $this->x3WithSizes(new \RuntimeException('X3 injoignable'));
        $this->em->expects(self::never())->method('persist');

        try {
            $this->service()->save($this->data(['lines' => $this->sizeLines(['L' => 30])]), $this->user());
            self::fail('Sans réponse de X3, une taille ne peut pas être acceptée.');
        } catch (\App\Service\Oc\OfferValidationException $e) {
            self::assertStringContainsString('X3 indisponible', implode(' ', $e->getErrors()));
        }
    }

    public function testParentOnlyOffersNeverCallTheVariantLookup(): void
    {
        $this->x3Ok();
        $this->x3->expects(self::never())->method('getVariants');

        $this->service()->save($this->data(), $this->user());
    }
}
