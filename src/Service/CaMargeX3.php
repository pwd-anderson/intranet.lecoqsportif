<?php

namespace App\Service;

use App\Factory\MssqlManagerFactory;
use App\Infrastructure\Sql\SqlFileLoader;
use App\Service\Tools\Helpers;
use App\Service\Tools\MssqlManager;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Export CA / Marge X3 — reprise de l'export "marge CA détails MASTER" de l'ancienne société
 * (ExportMargeCA::getSalesRevenueMarginMaster). Mêmes colonnes, dans le même ordre, avec les
 * mêmes noms (CHF remplacé par EUR), plus 6 colonnes propres à LCS (client commande, groupement
 * indépendant, genre, famille, collection, NOOS).
 * Montants convertis dans la devise société (EUR) via le taux de la facture (SINVOICE.RATMLT_0).
 * Seule la partie vente est alimentée pour l'instant ; les colonnes achat/marge/lot/TAR sont vides.
 */
class CaMargeX3
{
    public const array HEADERS = [
        'SOCIETE', 'PAYS', 'DATE FACTURE', 'No FACTURE', 'SDP CLIENT', 'TYPE MASTER', 'TYPE DOCUMENT',
        'MOTIF AVOIR', 'No DOSSIER RMA', 'No DOSSIER INTERNE', 'UTILISATEUR CREATION', 'CODE COMPTABLE',
        'REPRESENTANT', 'CLIENT COMMANDE', 'TIERS PAYEUR', 'RAISON SOCIALE', 'GROUPEMENT INDEPENDANT', 'MASTER CATEGORIE',
        'MARQUE', 'GENRE', 'FAMILLE', 'COLLECTION', 'ARTICLE', 'NOOS',
        'DESIGNATION', 'QTE FACTUREE X3', 'QTE PHYSIQUE', 'DEVISE FACTURE', 'PRIX UNITAIRE HT EUR',
        'MONTANT HT EUR', 'MONTANT HT EUR HORS PASS THRU', 'PRIX UNITAIRE HT DEVISE', 'MONTANT HT DEVISE',
        'MONTANT HT DEVISE HORS PASS THRU', 'No DE LOT', 'ORIGINE ACHAT', 'PRIX ACHAT DEVISE',
        'DEVISE PRIX ACHAT', 'PRIX ACHAT EUR', 'MARGE VALEUR EUR HORS FA', 'MARGE % HORS FA', 'METHODE PA',
        'FA UNITAIRE EUR', 'ORGANISME TAR', 'CODE TAR', 'TAR INTITULE COURT', 'TAR UNITAIRE', 'TOTAL TAR',
        'ETAT FACTURE',
    ];

    // Devise de la société : les colonnes "DEVISE" sont vidées quand la facture est déjà dans cette devise
    // (l'ancien export, suisse, faisait la même chose avec le CHF).
    private const string DEVISE_SOCIETE = 'EUR';

    // Types "pass-thru" : quantité physique et montants hors pass-thru à 0.
    private const array TYPES_PASS_THRU = ['AVOPP', 'AVSOA', 'FACPP', 'FASOA'];

    private const array TYPES_FACTURE = [1 => 'Facture', 2 => 'Avoir', 3 => 'Note de débit', 4 => 'Note de crédit', 5 => 'Proforma'];

    private const array ETATS_FACTURE = [1 => 'NON VALIDE', 2 => 'INUTILISE', 3 => 'VALIDE'];

    private MssqlManager $mssqlSei;

    public function __construct(
        MssqlManagerFactory $mssqlManagerFactory,
        private SqlFileLoader $sqlFileLoader,
        private Helpers $helpers,
        #[Autowire('%db.lcs_sei%')]
        string $dbLcsSei,
    ) {
        $this->mssqlSei = $mssqlManagerFactory->create($dbLcsSei);
    }

    /**
     * Écrit le CSV des factures et avoirs de l'année en cours (hors proformas), au même format
     * que l'ancien export : UTF-8 avec BOM, ligne "sep=;" puis séparateur ";".
     *
     * @return int nombre de lignes écrites (hors en-tête)
     */
    public function writeSalesCsv(string $path): int
    {
        $file = fopen($path, 'w');
        if ($file === false) {
            throw new \RuntimeException('Impossible de créer le fichier : ' . $path);
        }

        try {
            fwrite($file, "\xEF\xBB\xBF" . 'sep=;' . PHP_EOL);
            fputcsv($file, self::HEADERS, ';', '"', '');

            $count = 0;
            $sql = $this->sqlFileLoader->load('Sei/export_ca_marge_x3.sql');
            foreach ($this->mssqlSei->iterateQuery($sql) as $row) {
                fputcsv($file, $this->buildSalesLine($this->helpers->convertArrayToUtf8($row)), ';', '"', '');
                $count++;
            }
        } finally {
            fclose($file);
        }

        return $count;
    }

    private function buildSalesLine(array $row): array
    {
        $row = array_map(fn($v) => is_string($v) ? trim($v) : $v, $row);

        $invoiceType = (int) $row['INVTYP'];
        // Avoirs et notes de crédit en négatif (l'ancien code testait GTE_0 = 'AVC', 'AVCLI' chez LCS)
        $sign = in_array($invoiceType, [2, 4], true) ? -1 : 1;
        $isPassThru = in_array($row['SIVTYP'], self::TYPES_PASS_THRU, true);
        $isDeviseSociete = $row['DEVISE_FACTURE'] === self::DEVISE_SOCIETE;

        $qte = $this->formatNumber($sign * (float) $row['QTE'], 0);
        $montant = $this->formatNumber(round($sign * (float) $row['MONTANT_HT'] * (float) $row['TAUX'], 3));
        // Comme l'ancien export : prix unitaire converti sans le signe de l'avoir
        $prixUnitaire = $this->formatNumber(round((float) $row['PRIX_NET'] * (float) $row['TAUX'], 3));
        $montantDevise = $this->formatNumber($sign * (float) $row['MONTANT_HT']);

        $typeMaster = isset(self::TYPES_FACTURE[$invoiceType])
            ? $row['GTE'] . ' - ' . self::TYPES_FACTURE[$invoiceType]
            : '';

        return [
            $row['SOCIETE'],
            $row['PAYS'],
            $row['DATE_FACTURE'],
            $row['NUM_FACTURE'],
            $row['SDP_CLIENT'],
            $typeMaster,
            $this->codeLibelle($row['SIVTYP'], $row['SIVTYP_LIBELLE']),
            $this->codeLibelle($row['MOTIF_AVOIR_CODE'], $row['MOTIF_AVOIR_LIBELLE']),
            '',                                 // No DOSSIER RMA : pas d'équivalent chez LCS
            $row['REFERENCE_FACTURE'],
            $row['UTILISATEUR_CREATION'],
            '',                                 // CODE COMPTABLE
            $row['REPRESENTANT'],
            $row['CLIENT_COMMANDE'],
            $row['TIERS_PAYEUR'],
            $row['RAISON_SOCIALE'],
            $row['INDEPENDANT_GROUPMENT'],
            '',                                 // MASTER CATEGORIE
            '',                                 // MARQUE
            $row['GENRE'],
            $row['FAMILLE'],
            $row['COLLECTION'],
            $row['ARTICLE'],
            $row['NOOS'],
            $row['DESIGNATION'],
            $qte,
            $isPassThru ? 0 : $qte,
            $row['DEVISE_FACTURE'],
            $prixUnitaire,
            $montant,
            $isPassThru ? 0 : $montant,
            $isDeviseSociete ? '' : $this->formatNumber($sign * (float) $row['PRIX_NET_HT']),
            $isDeviseSociete ? '' : $montantDevise,
            $isPassThru ? 0 : ($isDeviseSociete ? '' : $montantDevise),
            ...array_fill(0, 14, ''),           // No DE LOT → TOTAL TAR : partie achat à venir
            self::ETATS_FACTURE[(int) $row['ETAT']] ?? '',
        ];
    }

    private function codeLibelle(?string $code, ?string $libelle): string
    {
        if ($code === null || $code === '') {
            return '';
        }

        return $libelle !== null && $libelle !== '' ? $code . ' - ' . $libelle : $code;
    }

    private function formatNumber(float $value, int $decimals = 2): string
    {
        return number_format($value, $decimals, '.', '');
    }
}
