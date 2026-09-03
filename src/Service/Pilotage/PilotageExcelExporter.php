<?php

namespace App\Service\Pilotage;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Génère le même classeur Excel (3 onglets) que l'export navigateur
 * du module Pilotage Livraisons (templates/pilotage/pilotage.html.twig → doExcel()).
 */
class PilotageExcelExporter
{
    private const PIL_COLS = [
        ['cat', 'Catégorie'], ['cf', 'Client facturé'], ['ord', 'N° commande'], ['stat', 'Statut'], ['coll', 'Collection'],
        ['dliv', 'Date demandée'], ['qte', 'Qté commandée'], ['pctOn', '% à temps'], ['pctLate', '% en retard'], ['on', 'Qté à temps'],
        ['etaOn1', '1ʳᵉ ETA à temps'], ['etaOn2', 'Dernière ETA à temps'], ['late', 'Qté en retard'], ['etaL1', '1ʳᵉ ETA retard'],
        ['etaL2', 'Dernière ETA retard'], ['retMoy', 'Retard moy (j)'], ['qs', 'Qté dispo (stock)'], ['qp', 'Qté à venir (prod)'],
        ['qa', 'Qté à annuler'], ['cov', '% couverture'], ['nsrc', 'Nb sources'], ['tsrc', 'Type de source'], ['posTxt', 'PO fournisseur'],
        ['eur', 'Montant EUR'], ['mag', 'Magasin / client cmd'], ['ref', 'Réf. cmd client'],
    ];

    public function build(array $orders, array $detail, PilotageEngine $engine): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->removeSheetByIndex(0);

        $this->buildSynthese($spreadsheet, $orders, $engine);
        $this->buildPilotage($spreadsheet, $orders, $engine);
        $this->buildDetail($spreadsheet, $detail, $engine);

        $spreadsheet->setActiveSheetIndex(0);

        return $spreadsheet;
    }

    public function save(Spreadsheet $spreadsheet, string $path): void
    {
        (new Xlsx($spreadsheet))->save($path);
    }

    private function buildSynthese(Spreadsheet $spreadsheet, array $orders, PilotageEngine $engine): void
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('SYNTHÈSE DIRECTION');

        $today = new \DateTimeImmutable('today');
        $nQ = array_sum(array_column($orders, 'qte'));
        $nE = array_sum(array_column($orders, 'eur'));
        $nS = array_sum(array_column($orders, 'qs'));
        $nP = array_sum(array_column($orders, 'qp'));
        $nA = array_sum(array_column($orders, 'qa'));
        $count = count($orders);

        $cnt = fn(string $needle) => count(array_filter($orders, fn($o) => str_contains($o['stat'], $needle)));
        $sum = fn(string $needle) => array_sum(array_column(array_filter($orders, fn($o) => str_contains($o['stat'], $needle)), 'eur'));

        $rows = [
            ['SYNTHÈSE DIRECTION — État des livraisons'],
            ['Analyse au', $engine->fmtD($today)],
            [],
            ["VUE D'ENSEMBLE"],
            ['Nombre de commandes', $count],
            ['Pièces commandées (total)', $nQ],
            ['Pièces disponibles en stock', $nS],
            ['Pièces à venir (production)', $nP],
            ['Pièces à annuler', $nA],
            ['Montant total commandé', $nE],
            ['Taux de couverture global', $nQ ? ($nS + $nP) / $nQ : 0],
            [],
            ['PONCTUALITÉ DES LIVRAISONS', 'Commandes', '% du total', 'Montant €'],
            ['🟢 On time', $cnt('On time'), $count ? $cnt('On time') / $count : 0, $sum('On time')],
            ['🟠 Partiellement en retard', $cnt('Partiellement'), $count ? $cnt('Partiellement') / $count : 0, $sum('Partiellement')],
            ['⚫ À annuler (aucune source, collection lancée)', $cnt('À annuler'), $count ? $cnt('À annuler') / $count : 0, $sum('À annuler')],
            ['🔴 Critique +50% retard', $cnt('Critique'), $count ? $cnt('Critique') / $count : 0, $sum('Critique')],
            [],
        ];

        foreach (['WHOLESALE FRANCE', 'INTERNATIONAL'] as $cat) {
            $m = [];
            foreach (array_filter($orders, fn($o) => $o['cat'] === $cat) as $o) {
                $m[$o['cf']] ??= ['eur' => 0.0, 'g' => 0, 'a' => 0, 'r' => 0];
                $m[$o['cf']]['eur'] += $o['eur'];
                if (str_contains($o['stat'], 'On time')) {
                    $m[$o['cf']]['g']++;
                } elseif (str_contains($o['stat'], 'Partiellement')) {
                    $m[$o['cf']]['a']++;
                } else {
                    $m[$o['cf']]['r']++;
                }
            }
            $totCat = array_sum(array_column($m, 'eur'));
            $rows[] = ['TOP 5 CLIENTS — ' . $cat, 'Montant €', '% CA', '🟢', '🟠', '🔴'];
            uasort($m, fn($x, $y) => $y['eur'] <=> $x['eur']);
            $i = 0;
            foreach ($m as $client => $v) {
                if (++$i > 5) {
                    break;
                }
                $rows[] = [$client, $v['eur'], $totCat ? $v['eur'] / $totCat : 0, $v['g'], $v['a'], $v['r']];
            }
            $rows[] = [];
        }

        $rows[] = ['RÉPARTITION FRANCE / INTERNATIONAL', 'Commandes', 'Pièces', 'Montant €'];
        foreach (['WHOLESALE FRANCE', 'INTERNATIONAL'] as $cat) {
            $g = array_filter($orders, fn($o) => $o['cat'] === $cat);
            $rows[] = [$cat, count($g), array_sum(array_column($g, 'qte')), array_sum(array_column($g, 'eur'))];
        }

        $sheet->fromArray($rows, null, 'A1');
        $sheet->getColumnDimension('A')->setWidth(38);
        foreach (['B', 'C', 'D'] as $col) {
            $sheet->getColumnDimension($col)->setWidth(16);
        }
        $sheet->getColumnDimension('E')->setWidth(8);
        $sheet->getColumnDimension('F')->setWidth(8);
    }

    private function buildPilotage(Spreadsheet $spreadsheet, array $orders, PilotageEngine $engine): void
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('PILOTAGE LIVRAISONS');

        $header = array_map(fn($c) => mb_strtoupper($c[1]), self::PIL_COLS);
        $sheet->fromArray([$header], null, 'A1');

        $rowIdx = 2;
        foreach ($orders as $o) {
            $col = 1;
            foreach (self::PIL_COLS as [$key, ]) {
                $v = $o[$key] ?? null;
                if ($v instanceof \DateTimeImmutable) {
                    $v = $engine->fmtD($v);
                } elseif ($key === 'retMoy' && $v !== null) {
                    $v = round($v);
                }
                $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
                $sheet->setCellValue($colLetter . $rowIdx, $v);
                $col++;
            }
            $rowIdx++;
        }

        $lastCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count(self::PIL_COLS));
        $lastRow = $rowIdx - 1;
        $sheet->setAutoFilter('A1:' . $lastCol . max(1, $lastRow));
        $sheet->freezePane('D2');

        foreach (self::PIL_COLS as $i => [$key, $label]) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i + 1);
            $sheet->getColumnDimension($colLetter)->setWidth(max(11, min(30, mb_strlen($label) + 4)));
        }
    }

    private function buildDetail(Spreadsheet $spreadsheet, array $detail, PilotageEngine $engine): void
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('DETAIL PAR ARTICLE');

        $headers = ['CATÉGORIE', 'CLIENT FACTURÉ', 'N° COMMANDE', 'SKU', 'DÉSIGNATION', 'QTÉ COMMANDÉE', 'DATE DEMANDÉE',
            'ETA', 'RETARD vs DATE DEMANDÉE', 'STATUT', 'QTÉ LIVRABLE (stock)', 'QTÉ PROD / TRANSIT', 'QTÉ À ANNULER',
            'PO / SOURCES', 'COLLECTION', 'DROPPÉ', 'MONTANT EUR'];
        $sheet->fromArray([$headers], null, 'A1');

        $rowIdx = 2;
        foreach ($detail as $r) {
            $sheet->fromArray([[
                $r['cat'], $r['cf'], $r['ord'], $r['sku'], $r['des'], $r['qte'],
                $engine->fmtD($r['dliv']), $engine->fmtD($r['eta']), $r['ret'], $r['st'],
                $r['qs'], $r['qp'], $r['qa'], $r['srcTxt'], $r['coll'], $r['drop'], $r['eur'],
            ]], null, 'A' . $rowIdx);
            $rowIdx++;
        }

        $widths = [17, 24, 15, 12, 28, 10, 12, 12, 10, 14, 11, 11, 11, 70, 11, 8, 11];
        foreach ($widths as $i => $w) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i + 1);
            $sheet->getColumnDimension($colLetter)->setWidth($w);
        }
    }
}
