<?php

namespace App\Tests\Service\Oc;

use App\Entity\Oc\Offer;
use App\Entity\Oc\OfferFlow;
use App\Entity\Oc\OfferLine;
use App\Entity\SalesWebService;
use App\Service\Oc\OcX3Lookup;
use App\Service\Oc\OfferFlowService;
use App\Service\Oc\OfferXmlBuilder;
use App\Service\Webservice\WebServiceDispatcher;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class OfferFlowServiceTest extends TestCase
{
    private EntityManagerInterface&MockObject $em;
    /** @var list<object> */
    private array $persisted = [];

    protected function setUp(): void
    {
        $this->persisted = [];
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->em->method('wrapInTransaction')->willReturnCallback(static fn (callable $fn) => $fn());
        $this->em->method('persist')->willReturnCallback(function (object $o): void { $this->persisted[] = $o; });
    }

    /**
     * @param array<string, list<string>> $variants
     * @param list<array<string,mixed>> $existing tarifs 7TSPEC déjà en place renvoyés par X3
     */
    private function service(array $variants = ['2210037' => ['2210037_40', '2210037_41'], '1511960' => ['1511960_SPL']], array $existing = []): OfferFlowService
    {
        $x3 = $this->createStub(OcX3Lookup::class);
        $x3->method('getVariants')->willReturn($variants);
        $x3->method('getExistingSpecialTariffs')->willReturn($existing);

        return new OfferFlowService($this->em, new OfferXmlBuilder(), new WebServiceDispatcher($this->em), $x3);
    }

    private function offer(): Offer
    {
        $offer = (new Offer())
            ->setNumber('OC2026102054812345678')->setCustomerCode('20548')->setCustomerCurrency('EUR')
            ->setStartDate(new \DateTimeImmutable('2027-02-01'))->setEndDate(new \DateTimeImmutable('2027-12-31'));
        foreach (['2210037', '1511960'] as $code) {
            $offer->addLine((new OfferLine())->setArticleCode($code)->setMinQty(0)->setMaxQty(999999)->setTariff('10')->setInjectedPrice('11.50'));
        }

        return $offer;
    }

    public function testValidationDepositsOneQueuedFlowPerVariantInSalesWebService(): void
    {
        $offer = $this->offer();
        (new \ReflectionProperty(Offer::class, 'id'))->setValue($offer, 42);

        $flows = $this->service()->send($offer);

        self::assertCount(3, $flows, 'un flux par variant (2 + 1), jamais pour le parent');
        self::assertSame(['2210037_40', '2210037_41', '1511960_SPL'], array_map(static fn ($fl) => $fl->getArticleCode(), $flows));
        self::assertStringContainsString('2210037_40', $flows[0]->getXml());
        self::assertSame('WSCRESPL', $flows[0]->getWebservice());
        self::assertSame(OfferFlow::QUEUED, $flows[0]->getStatus());
        $queued = array_values(array_filter($this->persisted, static fn ($o) => $o instanceof SalesWebService));
        self::assertCount(3, $queued);
        self::assertSame('WSCRESPL', $queued[0]->getName());
        self::assertStringContainsString('WDISPRI', $queued[0]->getParameter());
        self::assertFalse($queued[0]->isExecuted(), 'envoyé plus tard par la commande d\'exécution');
        self::assertSame(42, $queued[0]->getOcOfferId(), 'colonne oc_offer_id renseignée');
    }

    private static function existing(string $fiche, string $article, string $start, string $end): array
    {
        return ['FICHE' => $fiche, 'CLIENT' => '20548', 'ARTICLE' => $article, 'DATE_DEBUT' => $start, 'DATE_FIN' => $end, 'PRIX' => 69.77, 'DEVISE' => 'EUR'];
    }

    public function testAnExistingSpecialTariffIsClosedTheDayBeforeAndBeforeTheCreation(): void
    {
        $offer = $this->offer(); // début 2027-02-01
        (new \ReflectionProperty(Offer::class, 'id'))->setValue($offer, 42);

        $flows = $this->service(existing: [self::existing('SPL26-0007', '2210037_40', '2026-10-28', '2027-10-28')])->send($offer);

        self::assertSame(['WSUPD7TSPE', 'WSCRESPL', 'WSCRESPL', 'WSCRESPL'], array_map(static fn ($f) => $f->getWebservice(), $flows), 'la clôture part avant les créations');
        self::assertSame('2210037_40', $flows[0]->getArticleCode());
        self::assertStringContainsString('NAME="WSCODETARIF">7TSPEC<', $flows[0]->getXml());
        self::assertStringContainsString('NAME="WSFICHETARIF">SPL26-0007<', $flows[0]->getXml());
        self::assertStringContainsString('NAME="WSITMREF">2210037_40<', $flows[0]->getXml());
        self::assertStringContainsString('NAME="WSDATE">20270131<', $flows[0]->getXml(), 'veille du début de l\'OC');
        $queued = array_values(array_filter($this->persisted, static fn ($o) => $o instanceof SalesWebService));
        self::assertSame('WSUPD7TSPE', $queued[0]->getName(), 'déposé en premier dans sales_web_service');
        self::assertSame(42, $queued[0]->getOcOfferId());
    }

    public function testAnExistingTariffStartingOnOrAfterTheOfferStartBlocksEverything(): void
    {
        $offer = $this->offer(); // début 2027-02-01

        foreach (['2027-02-01', '2027-03-15'] as $start) {
            try {
                $this->service(existing: [self::existing('SPL26-0009', '2210037_41', $start, '2027-12-31')])->send($offer);
                self::fail('Un ancien tarif qui ne peut pas être clôturé doit empêcher la génération.');
            } catch (\RuntimeException $e) {
                self::assertStringContainsString('SPL26-0009', $e->getMessage());
            }
            self::assertCount(0, $offer->getFlows());
        }
        self::assertCount(0, array_filter($this->persisted, static fn ($o) => $o instanceof SalesWebService));
    }

    public function testConflictMessagesListsTheBlockingTariffs(): void
    {
        $service = $this->service(existing: [
            self::existing('SPL26-0007', '2210037_40', '2026-10-28', '2027-10-28'),
            self::existing('SPL26-0009', '2210037_41', '2027-02-01', '2027-12-31'),
        ]);

        $messages = $service->conflictMessages($this->offer());

        self::assertCount(1, $messages);
        self::assertStringContainsString('2210037_41', $messages[0]);
        self::assertStringContainsString('SPL26-0009', $messages[0]);
        self::assertStringContainsString('01/02/2027', $messages[0]);
    }

    public function testSendingTwiceDoesNotDuplicateTheFlows(): void
    {
        $service = $this->service();
        $offer = $this->offer();

        $service->send($offer);
        $service->send($offer);

        self::assertCount(3, $offer->getFlows());
        self::assertCount(3, array_filter($this->persisted, static fn ($o) => $o instanceof SalesWebService));
    }

    public function testNothingIsStoredWhenOneLineCannotBeBuilt(): void
    {
        $offer = $this->offer();
        $offer->getLines()->last()->setInjectedPrice(null);

        try {
            $this->service()->send($offer);
            self::fail('Une ligne sans prix à envoyer doit empêcher la génération.');
        } catch (\LogicException) {
            self::assertCount(0, $offer->getFlows());
        }
    }

    public function testParentWithoutVariantStopsEverything(): void
    {
        $offer = $this->offer();

        try {
            $this->service(['2210037' => ['2210037_40'], '1511960' => []])->send($offer);
            self::fail('Un parent sans variant doit empêcher la génération.');
        } catch (\RuntimeException $e) {
            self::assertStringContainsString('1511960', $e->getMessage());
            self::assertCount(0, $offer->getFlows());
        }
    }

    public function testSameVariantIsNeverSentTwice(): void
    {
        $flows = $this->service(['2210037' => ['2210037_40'], '1511960' => ['2210037_40']])->send($this->offer());

        self::assertCount(1, $flows);
    }
    // ── Prix par taille : une ligne « variant » l'emporte sur la ligne de son parent ─────────────────────────────

    private function offerWith(array $lines): Offer
    {
        $offer = (new Offer())
            ->setNumber('OC2026102054812345678')->setCustomerCode('20548')->setCustomerCurrency('EUR')
            ->setStartDate(new \DateTimeImmutable('2027-02-01'))->setEndDate(new \DateTimeImmutable('2027-12-31'));
        foreach ($lines as $code => $injected) {
            $offer->addLine((new OfferLine())->setArticleCode($code)->setMinQty(0)->setMaxQty(999999)->setTariff('10')->setInjectedPrice($injected));
        }

        return $offer;
    }

    /** @param list<OfferFlow> $flows @return array<string, string> code article => WDISPRI */
    private function pricesOf(array $flows): array
    {
        $out = [];
        foreach ($flows as $f) {
            preg_match('/NAME="WDISPRI">([^<]+)</', $f->getXml(), $m);
            $out[$f->getArticleCode()] = $m[1];
        }

        return $out;
    }

    public function testVariantLinesOverrideTheParentPriceAndOthersKeepTheParentPrice(): void
    {
        $variants = ['2210037' => ['2210037_L', '2210037_M', '2210037_S']];
        $offer = $this->offerWith(['2210037' => '35.00', '2210037_L' => '30.00', '2210037_S' => '43.00']);

        $flows = $this->service($variants)->send($offer);

        self::assertSame(['2210037_L' => '30', '2210037_M' => '35', '2210037_S' => '43'], $this->sorted($this->pricesOf($flows)));
        self::assertCount(3, $flows, 'un seul flux par taille, jamais deux');
    }

    public function testResultDoesNotDependOnTheOrderOfTheLines(): void
    {
        $variants = ['2210037' => ['2210037_L', '2210037_M', '2210037_S']];
        $parentFirst = $this->sorted($this->pricesOf($this->service($variants)->send($this->offerWith(['2210037' => '35.00', '2210037_L' => '30.00']))));
        $this->persisted = [];
        $childFirst = $this->sorted($this->pricesOf($this->service($variants)->send($this->offerWith(['2210037_L' => '30.00', '2210037' => '35.00']))));

        self::assertSame($parentFirst, $childFirst);
        self::assertSame(['2210037_L' => '30', '2210037_M' => '35', '2210037_S' => '35'], $childFirst);
    }

    public function testVariantLineAloneSendsOnlyThatSize(): void
    {
        $flows = $this->service(['2210037' => ['2210037_L', '2210037_M', '2210037_S']])->send($this->offerWith(['2210037_L' => '30.00']));

        self::assertSame(['2210037_L' => '30'], $this->pricesOf($flows), 'ni les autres tailles ni le parent');
    }

    public function testParentFullyCoveredByVariantLinesSendsOnlyThoseSizes(): void
    {
        $flows = $this->service(['2210037' => ['2210037_L', '2210037_S']])->send($this->offerWith(['2210037' => '35.00', '2210037_L' => '30.00', '2210037_S' => '43.00']));

        self::assertSame(['2210037_L' => '30', '2210037_S' => '43'], $this->sorted($this->pricesOf($flows)));
    }

    public function testVariantUnknownToItsParentStopsEverything(): void
    {
        $offer = $this->offerWith(['2210037' => '35.00', '2210037_XXL' => '30.00']);

        try {
            $this->service(['2210037' => ['2210037_L', '2210037_S']])->send($offer);
            self::fail('Une taille absente de X3 doit empêcher la génération.');
        } catch (\RuntimeException $e) {
            self::assertStringContainsString('2210037_XXL', $e->getMessage());
            self::assertCount(0, $offer->getFlows());
            self::assertCount(0, array_filter($this->persisted, static fn ($o) => $o instanceof SalesWebService));
        }
    }

    public function testVariantsOfTwoParentsAreMixedCorrectly(): void
    {
        $variants = ['2210037' => ['2210037_L', '2210037_S'], '1511960' => ['1511960_40', '1511960_41']];
        $flows = $this->service($variants)->send($this->offerWith(['2210037' => '35.00', '1511960' => '20.00', '1511960_41' => '25.00']));

        self::assertSame(['1511960_40' => '20', '1511960_41' => '25', '2210037_L' => '35', '2210037_S' => '35'], $this->sorted($this->pricesOf($flows)));
    }

    /** @param array<string,string> $a */
    private function sorted(array $a): array
    {
        ksort($a);

        return $a;
    }
}
