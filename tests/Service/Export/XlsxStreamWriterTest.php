<?php

namespace App\Tests\Service\Export;

use App\Service\Export\XlsxStreamWriter;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PHPUnit\Framework\TestCase;

/** Écrivain xlsx en flux : relu avec PhpSpreadsheet, comme le fera l'outil qui consomme les exports. */
final class XlsxStreamWriterTest extends TestCase
{
    private string $path;

    protected function setUp(): void
    {
        $this->path = sys_get_temp_dir() . '/xlsx_writer_' . bin2hex(random_bytes(4)) . '.xlsx';
    }

    protected function tearDown(): void
    {
        @unlink($this->path);
    }

    public function testNumbersStayNumbersAndTextIsNeverAFormula(): void
    {
        $w = new XlsxStreamWriter($this->path, ['A', 'B', 'C', 'D']);
        $w->addRow([12, 13.35, '=1+1', "a\x01b & <c>"]);
        $w->addRow(['00123', null, '', 'é — N°1']);
        self::assertSame(2, $w->rowCount());
        $w->close();

        $sheet = IOFactory::load($this->path)->getActiveSheet();

        self::assertSame(['A', 'B', 'C', 'D'], $sheet->rangeToArray('A1:D1')[0]);
        self::assertSame(DataType::TYPE_NUMERIC, $sheet->getCell('A2')->getDataType());
        self::assertSame(13.35, $sheet->getCell('B2')->getValue());
        self::assertSame('=1+1', (string) $sheet->getCell('C2')->getValue());
        self::assertFalse($sheet->getCell('C2')->isFormula(), 'jamais une formule');
        self::assertSame('ab & <c>', (string) $sheet->getCell('D2')->getValue(), 'caractère de contrôle retiré');
        self::assertSame('00123', (string) $sheet->getCell('A3')->getValue(), 'texte conservé tel quel');
        self::assertNull($sheet->getCell('B3')->getValue());
        self::assertSame('é — N°1', (string) $sheet->getCell('D3')->getValue());
    }

    public function testColumnLetters(): void
    {
        self::assertSame('A', XlsxStreamWriter::columnName(1));
        self::assertSame('Z', XlsxStreamWriter::columnName(26));
        self::assertSame('AA', XlsxStreamWriter::columnName(27));
        self::assertSame('AZ', XlsxStreamWriter::columnName(52));
        self::assertSame('BA', XlsxStreamWriter::columnName(53));
    }

    public function testHeaderOnlyFileIsStillValid(): void
    {
        $w = new XlsxStreamWriter($this->path, ['X']);
        $w->close();
        self::assertSame(1, IOFactory::load($this->path)->getActiveSheet()->getHighestRow());
    }
}
