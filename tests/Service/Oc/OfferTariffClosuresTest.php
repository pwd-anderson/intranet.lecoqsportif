<?php

namespace App\Tests\Service\Oc;

use App\Service\Oc\OfferTariffClosures;
use App\Service\Oc\OfferXmlBuilder;
use PHPUnit\Framework\TestCase;

final class OfferTariffClosuresTest extends TestCase
{
    private static function row(string $fiche, string $start, string $end = '2027-12-31'): array
    {
        return ['FICHE' => $fiche, 'CLIENT' => '20548', 'ARTICLE' => '2210037_40', 'DATE_DEBUT' => $start, 'DATE_FIN' => $end, 'PRIX' => 11.5, 'DEVISE' => 'EUR'];
    }

    public function testAnOlderTariffIsClosedTheDayBeforeEvenAcrossAMonthBoundary(): void
    {
        $plan = OfferTariffClosures::plan([self::row('SPL26-0001', '2026-10-01')], new \DateTimeImmutable('2027-03-01'));

        self::assertSame([], $plan['conflicts']);
        self::assertCount(1, $plan['closures']);
        self::assertSame('20270228', $plan['closures'][0]['endDate']);
        self::assertSame('SPL26-0001', $plan['closures'][0]['fiche']);
    }

    public function testATariffStartingTheDayBeforeCanStillBeClosed(): void
    {
        $plan = OfferTariffClosures::plan([self::row('SPL26-0001', '2027-02-28')], new \DateTimeImmutable('2027-03-01'));

        self::assertCount(1, $plan['closures']);
        self::assertSame([], $plan['conflicts']);
    }

    public function testATariffStartingTheSameDayOrLaterIsAConflict(): void
    {
        $plan = OfferTariffClosures::plan(
            [self::row('SPL26-0001', '2027-03-01'), self::row('SPL26-0002', '2027-06-01')],
            new \DateTimeImmutable('2027-03-01'),
        );

        self::assertSame([], $plan['closures']);
        self::assertSame(['SPL26-0001', 'SPL26-0002'], array_column($plan['conflicts'], 'FICHE'));
    }

    public function testClosureXmlCarriesTheFourWebserviceFields(): void
    {
        $xml = simplexml_load_string((new OfferXmlBuilder())->closure('SPL26-0007', '2210037_40', '20270131'));
        $fields = [];
        foreach ($xml->GRP->FLD as $fld) {
            $fields[(string) $fld['NAME']] = (string) $fld;
        }

        self::assertSame('INH', (string) $xml->GRP['ID']);
        self::assertSame(['WSCODETARIF' => '7TSPEC', 'WSFICHETARIF' => 'SPL26-0007', 'WSITMREF' => '2210037_40', 'WSDATE' => '20270131'], $fields);
    }
}
