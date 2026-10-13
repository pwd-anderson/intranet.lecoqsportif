<?php

namespace App\Tests\Service\Oc;

use App\Service\Oc\OfferCalculator;
use PHPUnit\Framework\TestCase;

final class OfferCalculatorTest extends TestCase
{
    public function testMarginMatchesTheOldModuleScreenshot(): void
    {
        // Ancien écran : PVC 99,99, TVA 20 %, prix net 59,25 -> marge 28,89 %.
        self::assertEqualsWithDelta(28.89, (new OfferCalculator())->margin(99.99, 20.0, 59.25), 0.01);
    }

    public function testDefaultVatIsUsedWhenMissing(): void
    {
        $c = new OfferCalculator();
        self::assertSame($c->margin(100.0, 20.0, 50.0), $c->margin(100.0, null, 50.0));
    }

    public function testNoMsrpMeansNoMargin(): void
    {
        self::assertNull((new OfferCalculator())->margin(null, 20.0, 10.0));
        self::assertNull((new OfferCalculator())->margin(0.0, 20.0, 10.0));
    }

    public function testNegativeMarginWhenTariffAboveNetSalePrice(): void
    {
        self::assertLessThan(0, (new OfferCalculator())->margin(60.0, 20.0, 80.0));
    }

    // ── Prix injecté (envoyé à X3) ──────────────────────────────────────────

    public function testNoDiscountSendsTheNetPriceAsIs(): void
    {
        $r = (new OfferCalculator())->injectedPrice(59.25, null);

        self::assertSame(59.25, $r['price']);
        self::assertTrue($r['exact']);
    }

    public function testInjectedPriceIsNetDividedByOneMinusDiscount(): void
    {
        // net voulu 87 avec 13 % de remise : 87 / 0,87 = 100 ; X3 refait 100 × 0,87 = 87.
        $r = (new OfferCalculator())->injectedPrice(87.0, 13.0);

        self::assertSame(100.0, $r['price']);
        self::assertSame(87.0, $r['resultingNet']);
        self::assertTrue($r['exact']);
    }

    public function testResultingNetIsAlwaysExactForRealisticPricesAndDiscounts(): void
    {
        $c = new OfferCalculator();
        $inexact = [];
        foreach ([10, 12.5, 13, 15, 20, 30, 33.33, 45, 60, 99] as $discount) {
            for ($cents = 100; $cents <= 30000; $cents += 37) {
                $net = $cents / 100;
                $r = $c->injectedPrice($net, (float) $discount);
                // Ce que X3 recalcule (prix injecté × (1 - remise), arrondi au centime) doit être le prix saisi.
                if (round($r['price'] * (1 - $discount / 100), 2) !== round($net, 2)) {
                    $inexact[] = "$net / $discount % -> {$r['price']}";
                }
                self::assertSame($r['exact'], round($r['price'] * (1 - $discount / 100), 2) === round($net, 2));
            }
        }

        self::assertSame([], $inexact, 'le net recalculé doit toujours redonner le prix saisi (avec remise < 100 %)');
    }

    public function testDiscountOfOneHundredPercentIsRefused(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new OfferCalculator())->injectedPrice(10.0, 100.0);
    }

    public function testNegativeDiscountIsRefused(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new OfferCalculator())->injectedPrice(10.0, -5.0);
    }

    public function testNonPositiveNetPriceIsRefused(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new OfferCalculator())->injectedPrice(0.0, 10.0);
    }

    public function testLcsMarginConvertsThePurchasePriceToTheOfferCurrency(): void
    {
        $calc = new OfferCalculator();

        // 6 USD, cours USD 1,2 pour 1 EUR → 5 EUR ; net 30 EUR → valeur 25, marge 83,3333 %.
        $r = $calc->lcsMargin(30.0, 6.0, 'USD', 'EUR', ['EUR' => 1.0, 'USD' => 1.2]);
        self::assertEqualsWithDelta(5.0, $r['cost'], 0.0001);
        self::assertEqualsWithDelta(25.0, $r['value'], 0.0001);
        self::assertEqualsWithDelta(83.3333, $r['percent'], 0.0001);

        // Même devise : aucun cours nécessaire.
        self::assertEqualsWithDelta(50.0, $calc->lcsMargin(40.0, 20.0, 'EUR', 'EUR', [])['percent'], 0.0001);

        // OC dans une autre devise que l'achat : 10 EUR d'achat → 16,28830 AUD.
        $aud = $calc->lcsMargin(32.5766, 10.0, 'EUR', 'AUD', ['EUR' => 1.0, 'AUD' => 1.62883]);
        self::assertEqualsWithDelta(16.2883, $aud['cost'], 0.0001);
        self::assertEqualsWithDelta(50.0, $aud['percent'], 0.01);
    }

    public function testLcsMarginIsUnknownWithoutPurchasePriceOrRate(): void
    {
        $calc = new OfferCalculator();

        self::assertNull($calc->lcsMargin(30.0, null, null, 'EUR', ['EUR' => 1.0]));
        self::assertNull($calc->lcsMargin(30.0, 6.0, 'USD', 'EUR', ['EUR' => 1.0]), 'cours USD absent');
    }

    public function testLcsMarginOfAFreeProductIsMinusHundredPercent(): void
    {
        $r = (new OfferCalculator())->lcsMargin(0.0, 5.0, 'EUR', 'EUR', []);

        self::assertSame(-100.0, $r['percent']);
        self::assertEqualsWithDelta(-5.0, $r['value'], 0.0001);
    }
}
