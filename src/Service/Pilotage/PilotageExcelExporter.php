<?php

namespace App\Service\Pilotage;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Génère le même classeur Excel (9 onglets) que l'export navigateur
 * du module Pilotage Livraisons V2 (templates/pilotage/pilotage.html.twig → exportFull()).
 *
 * Ordre des onglets, identique au JS :
 * Maccro View, PILOTAGE FRANCE, PILOTAGE INTERNATIONAL, DETAIL FRANCE, DETAIL INTL,
 * SUBST FRANCE, SUBST INTL, RESTE A ALLOUER, METHODE DE CALCUL.
 */
class PilotageExcelExporter
{
    public function build(array $result): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->removeSheetByIndex(0);

        $this->buildAoaSheet($spreadsheet, 'Maccro View', PilotageEngine::HEAD_MACRO, $result['macro']);
        $this->buildAoaSheet($spreadsheet, 'PILOTAGE FRANCE', $result['pfrHead'], $result['pfr']);
        $this->buildAoaSheet($spreadsheet, 'PILOTAGE INTERNATIONAL', $result['pintHead'], $result['pint']);
        $this->buildAoaSheet($spreadsheet, 'DETAIL FRANCE', PilotageEngine::HEAD_DET, $this->flattenDetail($result['detFR']));
        $this->buildAoaSheet($spreadsheet, 'DETAIL INTL', PilotageEngine::HEAD_DET, $this->flattenDetail($result['detIN']));
        $this->buildAoaSheet($spreadsheet, 'SUBST FRANCE', $result['subFR']['head'], $result['subFR']['rows']);
        $this->buildAoaSheet($spreadsheet, 'SUBST INTL', $result['subIN']['head'], $result['subIN']['rows']);
        $this->buildAoaSheet($spreadsheet, 'RESTE A ALLOUER', $result['restHead'], $result['rest']);
        $this->buildAoaSheet($spreadsheet, 'METHODE DE CALCUL', ['Sujet', 'Règle'], $result['methode']);

        $spreadsheet->setActiveSheetIndex(0);

        return $spreadsheet;
    }

    public function save(Spreadsheet $spreadsheet, string $path): void
    {
        (new Xlsx($spreadsheet))->save($path);
    }

    /**
     * @param string $ord   commande client
     */
    private function flattenDetail(array $detByOrder): array
    {
        $out = [];
        foreach ($detByOrder as $ord => $lines) {
            foreach ($lines as $l) {
                $out[] = [$ord, ...$l];
            }
        }
        return $out;
    }

    private function buildAoaSheet(Spreadsheet $spreadsheet, string $title, array $header, array $rows): void
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle(mb_substr($title, 0, 31));

        $sheet->fromArray([$header], null, 'A1');

        $rowIdx = 2;
        foreach ($rows as $row) {
            // Les valeurs sont déjà scalaires (string|int|float|null) : construites par PilotageEngine.
            $sheet->fromArray([array_values($row)], null, 'A' . $rowIdx);
            $rowIdx++;
        }

        $nbCols = count($header);
        if ($nbCols > 0) {
            $lastCol = Coordinate::stringFromColumnIndex($nbCols);
            $lastRow = max(1, $rowIdx - 1);
            $sheet->setAutoFilter('A1:' . $lastCol . $lastRow);
            $sheet->freezePane('A2');
            $this->applyColumnWidths($sheet, $header);
        }

        $headerStyle = $sheet->getStyle('A1:' . Coordinate::stringFromColumnIndex(max(1, $nbCols)) . '1');
        $headerStyle->getFont()->setBold(true);
        $headerStyle->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setRGB('EFEFEF');
    }

    private function applyColumnWidths(Worksheet $sheet, array $header): void
    {
        foreach ($header as $i => $label) {
            $colLetter = Coordinate::stringFromColumnIndex($i + 1);
            $len = mb_strlen((string) $label);
            $sheet->getColumnDimension($colLetter)->setWidth(max(10, min(42, $len + 3)));
        }
    }
}
