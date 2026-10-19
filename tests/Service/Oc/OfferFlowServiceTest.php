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

    /** @param array<string, list<string>> $variants */
    private function service(array $variants = ['2210037' => ['2210037_40', '2210037_41'], '1511960' => ['1511960_SPL']]): OfferFlowService
    {
        $x3 = $this->createStub(OcX3Lookup::class);
        $x3->method('getVariants')->willReturn($variants);

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
}
