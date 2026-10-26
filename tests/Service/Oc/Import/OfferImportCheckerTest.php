<?php

namespace App\Tests\Service\Oc\Import;

use App\Service\Oc\Import\OfferImportChecker;
use App\Service\Oc\Import\OfferImportException;
use App\Service\Oc\OcX3Lookup;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class OfferImportCheckerTest extends TestCase
{
    private OcX3Lookup&MockObject $x3;

    protected function setUp(): void
    {
        $this->x3 = $this->createMock(OcX3Lookup::class);
    }

    private function row(string $article, array $errors = [], ?float $tariff = 30.0): array
    {
        return ['line' => 2, 'article' => $article, 'tariff' => $tariff, 'minQty' => 0, 'maxQty' => 999999, 'comment' => null, 'errors' => $errors];
    }

    private function x3Knows(array $codes, array|\Throwable $sizes = []): void
    {
        $this->x3->method('getCustomer')->willReturn(['CODE_CLIENT' => '20548']);
        $this->x3->method('getArticlesPrices')->willReturnCallback(static function (string $customer, array $asked) use ($codes): array {
            $out = [];
            foreach ($asked as $c) {
                if (in_array($c, $codes, true)) {
                    $out[$c] = ['ARTICLE' => $c, 'DESIGNATION' => 'GANTS ' . $c, 'COLLECTION' => '2026-01-SS', 'TARIF' => 19.5, 'MSRP' => 39];
                }
            }

            return $out;
        });
        if ($sizes instanceof \Throwable) {
            $this->x3->method('getVariants')->willThrowException($sizes);
        } else {
            $this->x3->method('getVariants')->willReturn($sizes);
        }
    }

    public function testValidAndUnknownArticlesAreMarkedPerLine(): void
    {
        $this->x3Knows(['2210037']);

        $out = (new OfferImportChecker($this->x3))->check('20548', [$this->row('2210037'), $this->row('9999999')]);

        self::assertSame('ok', $out[0]['status']);
        self::assertSame('GANTS 2210037', $out[0]['label']);
        self::assertSame(19.5, $out[0]['x3Tariff']);
        self::assertFalse($out[0]['isSize']);
        self::assertSame('error', $out[1]['status']);
        self::assertStringContainsString('introuvable dans X3', $out[1]['errors'][0]);
    }

    public function testSizeMustExistForItsParent(): void
    {
        $this->x3Knows(['2210037_T9', '2210037_ZZ'], ['2210037' => ['2210037_T9', '2210037_T10']]);

        $out = (new OfferImportChecker($this->x3))->check('20548', [$this->row('2210037_T9'), $this->row('2210037_ZZ')]);

        self::assertSame('ok', $out[0]['status']);
        self::assertTrue($out[0]['isSize']);
        self::assertSame('error', $out[1]['status']);
        self::assertStringContainsString('2210037_ZZ n\'existe pas pour l\'article 2210037', $out[1]['errors'][0]);
    }

    public function testSizesAreNotReportedUnknownWhenX3DoesNotAnswer(): void
    {
        $this->x3Knows(['2210037_T9'], new \RuntimeException('X3 injoignable'));

        $out = (new OfferImportChecker($this->x3))->check('20548', [$this->row('2210037_T9')]);

        self::assertSame('error', $out[0]['status']);
        self::assertStringContainsString('X3 indisponible', $out[0]['errors'][0]);
    }

    public function testFormErrorsFromTheReaderAreKeptAndTheArticleIsStillChecked(): void
    {
        $this->x3Knows(['2210037']);

        $out = (new OfferImportChecker($this->x3))->check('20548', [$this->row('2210037', ['Tarif manquant ou invalide (nombre positif attendu).'], null)]);

        self::assertSame('error', $out[0]['status']);
        self::assertCount(1, $out[0]['errors']);
        self::assertSame('GANTS 2210037', $out[0]['label'], 'la désignation reste affichée pour aider à corriger');
    }

    public function testAnUnknownCustomerRefusesTheWholeFile(): void
    {
        $this->x3->method('getCustomer')->willReturn(null);

        $this->expectException(OfferImportException::class);

        (new OfferImportChecker($this->x3))->check('00000', [$this->row('2210037')]);
    }

    public function testRowsWithABrokenArticleCodeDoNotQueryX3ForIt(): void
    {
        $this->x3->method('getCustomer')->willReturn(['CODE_CLIENT' => '20548']);
        $this->x3->expects(self::never())->method('getArticlesPrices');

        $out = (new OfferImportChecker($this->x3))->check('20548', [$this->row('', ['Article manquant.'])]);

        self::assertSame('error', $out[0]['status']);
        self::assertCount(1, $out[0]['errors']);
    }
}
