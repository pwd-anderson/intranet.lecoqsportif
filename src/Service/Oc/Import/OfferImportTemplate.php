<?php

namespace App\Service\Oc\Import;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Fichier modèle téléchargeable depuis la fenêtre d'import : les en-têtes attendus par OfferImportReader, deux lignes d'exemple (un
 * article parent et une taille à un autre prix) et une feuille d'aide. Les exemples sont des codes fictifs : on les remplace.
 */
final class OfferImportTemplate
{
    public function binary(): string
    {
        $book = new Spreadsheet();
        $sheet = $book->getActiveSheet();
        $sheet->setTitle('Import OC');
        $sheet->fromArray([
            ['Article', 'Tarif OC', 'Qté min', 'Qté max', 'Commentaire'],
            ['2210037', 35, 0, 999999, 'Exemple : toutes les tailles de cet article à 35 €'],
            ['2210037_T9', 30, '', '', 'Exemple : la taille T9 à un autre prix (remplace le prix de l\'article)'],
        ], null, 'A1', true);
        $sheet->getStyle('A1:E1')->getFont()->setBold(true);
        $sheet->getStyle('A1:E1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('D9E8F7');
        foreach (['A' => 18, 'B' => 12, 'C' => 10, 'D' => 10, 'E' => 70] as $col => $width) {
            $sheet->getColumnDimension($col)->setWidth($width);
        }
        // Les codes article restent du texte (un « 0012345 » ne doit pas perdre son zéro).
        $sheet->getStyle('A2:A500')->getNumberFormat()->setFormatCode('@');

        $help = $book->createSheet();
        $help->setTitle('Aide');
        $help->fromArray([
            ['Comment remplir le fichier'],
            ['Un fichier = un client = une OC : le client et les dates sont choisis à l\'écran, pas dans le fichier.'],
            ['Article (obligatoire) : code d\'un article (2210037) ou d\'une taille (2210037_T9).'],
            ['Tarif OC (obligatoire) : le prix NET voulu pour le client (nombre positif, virgule ou point).'],
            ['Qté min / Qté max (facultatives) : 0 et 999999 si vides.'],
            ['Commentaire (facultatif) : 500 caractères maximum.'],
            [''],
            ['Un article sans taille s\'applique à toutes ses tailles ; une ligne de taille remplace le prix de l\'article pour cette taille.'],
            ['Une taille seule (sans ligne de l\'article) ne tarife que cette taille.'],
            ['Maximum 200 lignes. Les lignes en erreur sont signalées avant l\'import et ne sont pas ajoutées.'],
        ], null, 'A1', true);
        $help->getStyle('A1')->getFont()->setBold(true);
        $help->getColumnDimension('A')->setWidth(120);
        $book->setActiveSheetIndex(0);

        $stream = fopen('php://temp', 'r+');
        (new Xlsx($book))->save($stream);
        rewind($stream);
        $binary = (string) stream_get_contents($stream);
        fclose($stream);

        return $binary;
    }
}
