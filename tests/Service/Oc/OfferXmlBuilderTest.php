<?php

namespace App\Tests\Service\Oc;

use App\Entity\Oc\Offer;
use App\Entity\Oc\OfferLine;
use App\Service\Oc\OfferXmlBuilder;
use PHPUnit\Framework\TestCase;

final class OfferXmlBuilderTest extends TestCase
{
    private function offer(?string $injected = '21.26'): array
    {
        $line = (new OfferLine())->setArticleCode('2210037')->setMinQty(10)->setMaxQty(500)->setTariff('18.50')->setInjectedPrice($injected);
        $offer = (new Offer())
            ->setNumber('OC2026102054812345678')->setCustomerCode('20548')->setCustomerCurrency('EUR')
            ->setStartDate(new \DateTimeImmutable('2027-02-01'))->setEndDate(new \DateTimeImmutable('2027-12-31'));
        $offer->addLine($line);

        return [$offer, $line];
    }

    /** @return array<string,string> */
    private static function fields(string $xml): array
    {
        $out = [];
        foreach (simplexml_load_string($xml)->GRP->FLD as $fld) {
            $out[(string) $fld['NAME']] = (string) $fld;
        }

        return $out;
    }

    public function testCreationXmlCarriesTheInjectedPriceAndTheOfferData(): void
    {
        [$offer, $line] = $this->offer();

        $xml = (new OfferXmlBuilder())->creation($offer, $line);

        self::assertSame('INH', (string) simplexml_load_string($xml)->GRP['ID']);
        self::assertSame([
            'WPLI' => '7TSPEC', 'WPLISTRDAT' => '20270201', 'WPLIENDDAT' => '20271231', 'WPLICRDIDX' => 'OC2026102054812345678',
            'WITMREF' => '2210037', 'WBPCNUM' => '20548', 'WMINQTY' => '10', 'WMAXQTY' => '500', 'WCUR' => 'EUR', 'WDISPRI' => '21.26',
        ], self::fields($xml));
    }

    public function testTheNetTariffIsNeverWhatIsSentToX3(): void
    {
        [$offer, $line] = $this->offer('21.26');

        self::assertNotContains('18.5', self::fields((new OfferXmlBuilder())->creation($offer, $line)));
    }

    public function testTheIndexIsTruncatedToThirtyCharacters(): void
    {
        [$offer, $line] = $this->offer();
        $offer->setNumber(str_repeat('A', 45));

        self::assertSame(30, strlen(self::fields((new OfferXmlBuilder())->creation($offer, $line))['WPLICRDIDX']));
    }

    public function testALineWithoutInjectedPriceCannotBeBuilt(): void
    {
        [$offer, $line] = $this->offer(null);

        $this->expectException(\LogicException::class);
        (new OfferXmlBuilder())->creation($offer, $line);
    }

    public function testVariantCodeReplacesTheParentInWitmref(): void
    {
        [$offer, $line] = $this->offer();

        self::assertSame('2210037_40', self::fields((new OfferXmlBuilder())->creation($offer, $line, '2210037_40'))['WITMREF']);
        self::assertSame($line->getArticleCode(), self::fields((new OfferXmlBuilder())->creation($offer, $line))['WITMREF']);
    }
}
