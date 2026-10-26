<?php

namespace App\Tests\Service\Oc\Import;

use App\Service\Oc\Import\OfferImportException;
use App\Service\Oc\Import\OfferImportReader;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PHPUnit\Framework\TestCase;

final class OfferImportReaderTest extends TestCase
{
    /** @var list<string> */
    private array $tmp = [];

    protected function tearDown(): void
    {
        foreach ($this->tmp as $f) {
            @unlink($f);
        }
    }

    private function file(string $content, string $ext = 'csv'): string
    {
        $base = tempnam(sys_get_temp_dir(), 'ocimpt');
        $path = $base . '.' . $ext;
        rename($base, $path);
        file_put_contents($path, $content);
        $this->tmp[] = $path;

        return $path;
    }

    /** @param list<list<mixed>> $cells */
    private function xlsx(array $cells): string
    {
        $book = new Spreadsheet();
        $book->getActiveSheet()->fromArray($cells, null, 'A1', true);
        $base = tempnam(sys_get_temp_dir(), 'ocimpt');
        $path = $base . '.xlsx';
        rename($base, $path);
        (new Xlsx($book))->save($path);
        $this->tmp[] = $path;

        return $path;
    }

    public function testReadsASemicolonCsvWithAccentedHeadersAndFrenchDecimals(): void
    {
        $csv = "Article;Tarif OC;Qté min;Qté max;Commentaire\n2210037;35,5;10;500;Offre été\n2210037_T9;30;;;\n";

        $rows = (new OfferImportReader())->read($this->file($csv), 'csv');

        self::assertCount(2, $rows);
        self::assertSame('2210037', $rows[0]['article']);
        self::assertSame(35.5, $rows[0]['tariff']);
        self::assertSame(10, $rows[0]['minQty']);
        self::assertSame(500, $rows[0]['maxQty']);
        self::assertSame('Offre été', $rows[0]['comment']);
        self::assertSame([], $rows[0]['errors']);
        self::assertSame(0, $rows[1]['minQty'], 'quantité min vide = 0');
        self::assertSame(999999, $rows[1]['maxQty'], 'quantité max vide = 999999');
        self::assertNull($rows[1]['comment']);
        self::assertSame(2, $rows[0]['line'], 'numéro de ligne dans le fichier (en-tête = ligne 1)');
    }

    public function testColumnsCanBeInAnyOrderAndUseSynonyms(): void
    {
        $csv = "Commentaire,Prix net,Référence\nok,12.5,1511960\n";

        $rows = (new OfferImportReader())->read($this->file($csv), 'csv');

        self::assertSame('1511960', $rows[0]['article']);
        self::assertSame(12.5, $rows[0]['tariff']);
        self::assertSame('ok', $rows[0]['comment']);
    }

    public function testHeaderMayBeBelowATitleRow(): void
    {
        $csv = "Offre client Dupont\n\nArticle;Tarif\n2210037;20\n";

        $rows = (new OfferImportReader())->read($this->file($csv), 'csv');

        self::assertCount(1, $rows);
        self::assertSame(4, $rows[0]['line'], 'numéro de ligne du fichier, pour retrouver la ligne en erreur');
    }

    public function testReadsAnExcelFileWhereTheArticleCodeIsANumber(): void
    {
        $rows = (new OfferImportReader())->read($this->xlsx([['Article', 'Tarif', 'Qté min', 'Qté max'], [2210037, 35, 0, 999999], ['2210037_T9', '30,00 €', 5, 50]]), 'xlsx');

        self::assertSame('2210037', $rows[0]['article'], 'un nombre Excel redevient un code sans « .0 »');
        self::assertSame(30.0, $rows[1]['tariff'], 'le symbole € et la virgule sont acceptés');
        self::assertSame(5, $rows[1]['minQty']);
    }

    public function testRowLevelErrorsAreReportedPerLine(): void
    {
        $csv = "Article;Tarif;Qté min;Qté max\n;10;;\n2210037;abc;;\n2210038;0;;\n2210039;10;-1;\n2210040;10;50;10\n2210041;10;1,5;\n2210037;12;;\n";

        $rows = (new OfferImportReader())->read($this->file($csv), 'csv');

        self::assertStringContainsString('Article manquant', $rows[0]['errors'][0]);
        self::assertStringContainsString('Tarif', $rows[1]['errors'][0]);
        self::assertStringContainsString('Tarif', $rows[2]['errors'][0], 'un tarif nul est refusé');
        self::assertStringContainsString('Qté min invalide', $rows[3]['errors'][0]);
        self::assertStringContainsString('dépasse', $rows[4]['errors'][0]);
        self::assertStringContainsString('Qté min invalide', $rows[5]['errors'][0], 'une quantité décimale est refusée');
        self::assertStringContainsString('déjà présent', $rows[6]['errors'][0]);
    }

    public function testBlankRowsAreIgnored(): void
    {
        $rows = (new OfferImportReader())->read($this->file("Article;Tarif\n2210037;10\n;\n\n2210038;12\n"), 'csv');

        self::assertCount(2, $rows);
    }

    public function testFileWithoutRecognisedHeadersIsRefused(): void
    {
        $this->expectException(OfferImportException::class);
        $this->expectExceptionMessage('En-têtes introuvables');

        (new OfferImportReader())->read($this->file("Truc;Machin\n1;2\n"), 'csv');
    }

    public function testFileWithoutDataRowsIsRefused(): void
    {
        $this->expectException(OfferImportException::class);
        $this->expectExceptionMessage('aucune ligne');

        (new OfferImportReader())->read($this->file("Article;Tarif\n"), 'csv');
    }

    public function testTooManyLinesAreRefused(): void
    {
        $csv = "Article;Tarif\n";
        for ($i = 1; $i <= 201; ++$i) {
            $csv .= (2000000 + $i) . ";10\n";
        }

        $this->expectException(OfferImportException::class);
        $this->expectExceptionMessage('200');

        (new OfferImportReader())->read($this->file($csv), 'csv');
    }

    public function testUnsupportedExtensionAndEmptyFilesAreRefused(): void
    {
        $reader = new OfferImportReader();
        try {
            $reader->read($this->file('x', 'pdf'), 'pdf');
            self::fail('pdf refusé');
        } catch (OfferImportException $e) {
            self::assertStringContainsString('Format non pris en charge', $e->getMessage());
        }
        $this->expectException(OfferImportException::class);
        $reader->read($this->file('', 'csv'), 'csv');
    }

    public function testLatin1CsvIsReadCorrectly(): void
    {
        $csv = mb_convert_encoding("Article;Tarif;Commentaire\n2210037;10;Offre été\n", 'ISO-8859-1', 'UTF-8');

        $rows = (new OfferImportReader())->read($this->file($csv), 'csv');

        self::assertSame('Offre été', $rows[0]['comment']);
    }
    public function testAFileStoredWithoutExtensionLikeABrowserUploadIsStillRead(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'phpUp');   // nom sans extension, comme /tmp/phpAbC123 d'un envoi HTTP
        file_put_contents($path, "Article;Tarif\n2210037;10\n");
        $this->tmp[] = $path;

        $rows = (new OfferImportReader())->read($path, 'csv');

        self::assertSame('2210037', $rows[0]['article']);
        self::assertSame([], glob(sys_get_temp_dir() . '/ocimp*') ?: [], 'la copie de travail est supprimée');
    }
}
