<?php

namespace App\Tests\Service\Oc;

use App\Service\Oc\Input\OfferInput;
use App\Service\Oc\OfferValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class OfferInputTest extends TestCase
{
    /** @return array<string,mixed> */
    private function valid(array $override = []): array
    {
        return array_replace_recursive([
            'customerCode' => '20548',
            'startDate' => '2026-11-01',
            'endDate' => '2027-10-31',
            'comment' => '  Offre de rentrée  ',
            'lines' => [
                ['articleCode' => '2210037', 'tariff' => '19,5', 'msrp' => '39', 'vatRate' => 20, 'minQty' => 0, 'maxQty' => 999999, 'comment' => ''],
            ],
        ], $override);
    }

    public function testValidDataIsTypedAndNormalized(): void
    {
        $in = OfferInput::fromArray($this->valid());

        self::assertSame('20548', $in->customerCode);
        self::assertSame('2026-11-01', $in->startDate->format('Y-m-d'));
        self::assertSame('Offre de rentrée', $in->comment);
        self::assertCount(1, $in->lines);
        self::assertSame(19.5, $in->lines[0]->tariff, 'la virgule décimale est acceptée');
        self::assertSame(39.0, $in->lines[0]->msrp);
        self::assertNull($in->lines[0]->comment, 'un commentaire vide devient null');
    }

    /** @return iterable<string, array{array<string,mixed>, string}> */
    public static function invalidProvider(): iterable
    {
        $line = ['articleCode' => 'A1', 'tariff' => 10, 'minQty' => 0, 'maxQty' => 10];

        yield 'client manquant' => [['customerCode' => ''], 'client est obligatoire'];
        yield 'date invalide' => [['startDate' => '2026-13-45'], 'date de début est invalide'];
        yield 'date au mauvais format' => [['endDate' => '31/10/2027'], 'date de fin est invalide'];
        yield 'fin avant début' => [['startDate' => '2027-01-01', 'endDate' => '2026-01-01'], 'postérieure'];
        yield 'aucune ligne' => [['lines' => []], 'au moins un article'];
        yield 'tarif manquant' => [['lines' => [array_replace($line, ['tariff' => ''])]], 'tarif OC est obligatoire'];
        yield 'tarif négatif' => [['lines' => [array_replace($line, ['tariff' => -3])]], 'tarif OC est obligatoire'];
        yield 'tarif texte' => [['lines' => [array_replace($line, ['tariff' => 'abc'])]], 'tarif OC est obligatoire'];
        yield 'min > max' => [['lines' => [array_replace($line, ['minQty' => 20, 'maxQty' => 10])]], 'min dépasse'];
        yield 'TVA hors bornes' => [['lines' => [array_replace($line, ['vatRate' => 150])]], 'TVA invalide'];
        yield 'article en double' => [['lines' => [$line, $line]], 'en double'];
        yield 'commentaire trop long' => [['comment' => str_repeat('x', 501)], 'dépasse 500'];
    }

    #[DataProvider('invalidProvider')]
    public function testInvalidDataIsRejected(array $override, string $expectedMessage): void
    {
        // array_replace_recursive fusionnerait les listes de lignes : on remplace les clés racines.
        $data = array_replace($this->valid(), $override);

        try {
            OfferInput::fromArray($data);
            self::fail('Une OfferValidationException était attendue.');
        } catch (OfferValidationException $e) {
            self::assertStringContainsString($expectedMessage, implode(' ', $e->getErrors()));
        }
    }

    public function testAllErrorsAreReportedAtOnce(): void
    {
        try {
            OfferInput::fromArray(['customerCode' => '', 'startDate' => 'x', 'endDate' => 'y', 'lines' => []]);
            self::fail('exception attendue');
        } catch (OfferValidationException $e) {
            self::assertGreaterThanOrEqual(4, count($e->getErrors()));
        }
    }

    public function testTooManyLinesAreRejected(): void
    {
        $lines = [];
        for ($i = 0; $i <= OfferInput::MAX_LINES; ++$i) {
            $lines[] = ['articleCode' => "A$i", 'tariff' => 1];
        }

        $this->expectException(OfferValidationException::class);
        OfferInput::fromArray(array_replace($this->valid(), ['lines' => $lines]));
    }
}
