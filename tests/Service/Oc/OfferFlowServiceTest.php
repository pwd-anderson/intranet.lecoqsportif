<?php

namespace App\Tests\Service\Oc;

use App\Entity\Oc\Offer;
use App\Entity\Oc\OfferFlow;
use App\Entity\Oc\OfferLine;
use App\Entity\SalesWebService;
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

    private function service(bool $enabled): OfferFlowService
    {
        return new OfferFlowService($this->em, new OfferXmlBuilder(), new WebServiceDispatcher($this->em), $enabled);
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

    public function testDisabledByDefaultFlowsArePreparedAndNothingIsSent(): void
    {
        $service = $this->service(false);
        $offer = $this->offer();

        $flows = $service->prepare($offer);

        self::assertCount(2, $flows);
        self::assertSame(OfferFlow::PREPARED, $flows[0]->getStatus());
        self::assertSame('WSCRESPL', $flows[0]->getWebservice());
        self::assertSame(0, $service->queue($offer));
        self::assertSame(OfferFlow::PREPARED, $offer->getFlows()->first()->getStatus());
        self::assertSame([], array_filter($this->persisted, static fn ($o) => $o instanceof SalesWebService), 'rien dans sales_web_service');
    }

    public function testPreparingTwiceDoesNotDuplicateTheFlows(): void
    {
        $service = $this->service(false);
        $offer = $this->offer();

        $service->prepare($offer);
        $service->prepare($offer);

        self::assertCount(2, $offer->getFlows());
    }

    public function testEnabledFlowsAreQueuedInSalesWebService(): void
    {
        $service = $this->service(true);
        $offer = $this->offer();
        (new \ReflectionProperty(Offer::class, 'id'))->setValue($offer, 42);

        $service->prepare($offer);

        self::assertSame(2, $service->queue($offer));
        self::assertSame(OfferFlow::QUEUED, $offer->getFlows()->first()->getStatus());
        $queued = array_values(array_filter($this->persisted, static fn ($o) => $o instanceof SalesWebService));
        self::assertCount(2, $queued);
        self::assertSame('WSCRESPL', $queued[0]->getName());
        self::assertStringContainsString('WDISPRI', $queued[0]->getParameter());
        self::assertFalse($queued[0]->isExecuted());
        self::assertSame(42, $queued[0]->getOcOfferId(), 'colonne oc_offer_id renseignée');
        self::assertSame(0, $service->queue($offer), 'un flux déjà en file n\'est pas renvoyé');
    }

    public function testNothingIsStoredWhenOneLineCannotBeBuilt(): void
    {
        $offer = $this->offer();
        $offer->getLines()->last()->setInjectedPrice(null);

        try {
            $this->service(false)->prepare($offer);
            self::fail('Une ligne sans prix à envoyer doit empêcher la génération.');
        } catch (\LogicException) {
            self::assertCount(0, $offer->getFlows());
        }
    }
}
