<?php

namespace App\Service\Export;

/**
 * Écrit un fichier .xlsx (une feuille « Export ») ligne par ligne, avec une mémoire constante : les exports
 * automatiques dépassent parfois 100 000 lignes, ce qu'une bibliothèque qui garde toutes les cellules en mémoire
 * ne supporte pas. Mise en forme alignée sur l'export manuel : en-tête blanc sur bleu (#024185), ligne figée,
 * filtre automatique, largeurs de colonnes bornées à 12–40.
 *
 * Valeurs acceptées : int / float (cellule numérique), string (texte, jamais interprété comme une formule),
 * null ou '' (cellule vide).
 */
final class XlsxStreamWriter
{
    /** @var resource */
    private $sheet;
    private string $sheetPath;
    private int $row = 1;
    private bool $closed = false;

    /** @param list<string> $headers */
    public function __construct(private readonly string $targetPath, private readonly array $headers)
    {
        if ($headers === []) {
            throw new \InvalidArgumentException('Aucune colonne à exporter.');
        }

        $this->sheetPath = $targetPath . '.sheet.tmp';
        $handle = fopen($this->sheetPath, 'wb');
        if ($handle === false) {
            throw new \RuntimeException("Impossible d'écrire : {$this->sheetPath}");
        }
        $this->sheet = $handle;

        $lastCol = self::columnName(count($headers));
        $cols = '';
        foreach ($headers as $i => $header) {
            $width = min(40, max(12, mb_strlen($header) + 4));
            $cols .= sprintf('<col min="%1$d" max="%1$d" width="%2$d" customWidth="1"/>', $i + 1, $width);
        }

        fwrite($this->sheet, '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<sheetViews><sheetView workbookViewId="0"><pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>'
            . '<cols>' . $cols . '</cols><sheetData>');

        $cells = '';
        foreach ($headers as $i => $header) {
            $cells .= $this->cell(self::columnName($i + 1) . '1', $header, 1);
        }
        fwrite($this->sheet, '<row r="1" ht="22" customHeight="1">' . $cells . '</row>');
        $this->lastCol = $lastCol;
    }

    private string $lastCol;

    /** @param array<int,int|float|string|null> $values dans l'ordre des colonnes */
    public function addRow(array $values): void
    {
        $this->row++;
        $cells = '';
        $i = 0;
        foreach ($values as $value) {
            $i++;
            if ($value === null || $value === '') {
                continue;
            }
            $cells .= $this->cell(self::columnName($i) . $this->row, $value, 0);
        }
        fwrite($this->sheet, '<row r="' . $this->row . '">' . $cells . '</row>');
    }

    public function rowCount(): int
    {
        return $this->row - 1;
    }

    public function close(): void
    {
        if ($this->closed) {
            return;
        }
        $this->closed = true;

        fwrite($this->sheet, '</sheetData><autoFilter ref="A1:' . $this->lastCol . '1"/></worksheet>');
        fclose($this->sheet);

        $zip = new \ZipArchive();
        if ($zip->open($this->targetPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException("Impossible de créer : {$this->targetPath}");
        }
        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            . '</Types>');
        $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '</Relationships>');
        $zip->addFromString('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets><sheet name="Export" sheetId="1" r:id="rId1"/></sheets></workbook>');
        $zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            . '</Relationships>');
        $zip->addFromString('xl/styles.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<fonts count="2"><font><sz val="11"/><name val="Calibri"/></font><font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font></fonts>'
            . '<fills count="3"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FF024185"/><bgColor indexed="64"/></patternFill></fill></fills>'
            . '<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="2"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            . '<xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf></cellXfs>'
            . '</styleSheet>');
        $zip->addFile($this->sheetPath, 'xl/worksheets/sheet1.xml');

        if (!$zip->close()) {
            throw new \RuntimeException("Impossible de finaliser : {$this->targetPath}");
        }
        @unlink($this->sheetPath);
    }

    public function __destruct()
    {
        if (!$this->closed) {
            if (is_resource($this->sheet)) {
                fclose($this->sheet);
            }
            @unlink($this->sheetPath);
        }
    }

    private function cell(string $ref, int|float|string $value, int $style): string
    {
        $s = $style > 0 ? ' s="' . $style . '"' : '';

        if (is_int($value) || (is_float($value) && is_finite($value))) {
            return '<c r="' . $ref . '"' . $s . '><v>' . (is_int($value) ? $value : json_encode($value)) . '</v></c>';
        }
        if (is_float($value)) {
            return '';
        }

        // Caractères interdits en XML retirés ; texte jamais interprété comme formule.
        $text = preg_replace('/[^\x{9}\x{A}\x{D}\x{20}-\x{D7FF}\x{E000}-\x{FFFD}\x{10000}-\x{10FFFF}]/u', '', $value) ?? '';

        return '<c r="' . $ref . '" t="inlineStr"' . $s . '><is><t xml:space="preserve">'
            . htmlspecialchars($text, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</t></is></c>';
    }

    public static function columnName(int $index): string
    {
        $name = '';
        while ($index > 0) {
            $index--;
            $name = chr(65 + $index % 26) . $name;
            $index = intdiv($index, 26);
        }

        return $name;
    }
}
