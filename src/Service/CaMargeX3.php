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
 * mêmes noms (CHF remplacé par EUR), plus les colonnes propres à LCS (client commande, groupement
 * indépendant, genre, famille, collection, segment d'offre, NOOS, second représentant).
 *
 * Les données viennent du cube SEI (SEI_X3_LCS.CONSO_INVOICES) et non plus des tables X3 brutes :
 * les montants y sont déjà convertis (AMOUNTEURTM en EUR, AMOUNTCURRENCY en devise de facture),
 * il n'y a donc plus de taux de change à appliquer.
 *
 * La requête ne renvoie que des champs bruts ; tout ce qui était calculé en SQL (type master, type
 * de document, libellés, signe des avoirs, prix unitaires, article complet, NOOS) est dérivé ici.
 *
 * Seule la partie vente est alimentée pour l'instant ; les colonnes achat/marge/lot/TAR sont vides.
 */
class CaMargeX3
{
    public const array HEADERS = [
        'SOCIETE', 'PAYS', 'DATE FACTURE', 'No FACTURE', 'SDP CLIENT', 'TYPE MASTER', 'TYPE DOCUMENT',
        'MOTIF AVOIR', 'No DOSSIER RMA', 'No DOSSIER INTERNE', 'UTILISATEUR CREATION', 'CODE COMPTABLE',
        'REPRESENTANT 1', 'REPRESENTANT 2', 'CLIENT COMMANDE', 'TIERS PAYEUR', 'RAISON SOCIALE',
        'GROUPEMENT INDEPENDANT', 'MASTER CATEGORIE',
        'MARQUE', 'GENRE', 'FAMILLE', 'COLLECTION', 'SEGMENT OFFRE', 'ARTICLE', 'NOOS',
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

    // Type master déduit des trois premiers caractères du numéro de document.
    private const array PREFIXES_TYPE_MASTER = [
        'AVB' => 'AVCLI', 'AVY' => 'AVCLI',
        'FVB' => 'FACLI', 'FVY' => 'FACLI',
    ];

    // Le cube ne remonte que des documents validés.
    private const string ETAT_FACTURE = 'VALIDE';

    // Profondeur d'historique de l'export. Doit rester alignée avec le DATEADD(YEAR, -N, GETDATE())
    // de export_ca_marge_x3.sql ; sert à nommer le fichier produit par la commande.
    public const int ANNEES_HISTORIQUE = 2;

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
     * Écrit le CSV des factures et avoirs des deux dernières années, au même format
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

        // Type master : le préfixe du numéro de document prime sur la colonne du cube
        $typeMasterCode = self::PREFIXES_TYPE_MASTER[strtoupper(substr((string) $row['NUM_FACTURE'], 0, 3))]
            ?? (string) $row['TYPE_MASTER'];
        $libelleType = $typeMasterCode === 'FACLI' ? 'Facture' : 'Avoir';

        // Pas de signe à appliquer : contrairement aux tables X3, le cube renvoie déjà les
        // quantités et les montants des avoirs en négatif.

        // Type de document : le cube ne le porte pas toujours, on retombe alors sur le type master
        $sivtyp = ($row['SIVTYP'] ?? '') !== '' ? (string) $row['SIVTYP'] : $typeMasterCode;
        $isPassThru = in_array($sivtyp, self::TYPES_PASS_THRU, true);
        $isDeviseSociete = $row['DEVISE_FACTURE'] === self::DEVISE_SOCIETE;

        $quantite = (float) $row['QTE'];
        $montantEur = (float) $row['MONTANT_EUR'];
        $montantDeviseBrut = (float) $row['MONTANT_DEVISE'];

        $qte = $this->formatNumber($quantite, 0);
        $montant = $this->formatNumber(round($montantEur, 3));
        $montantDevise = $this->formatNumber($montantDeviseBrut);
        // Les prix unitaires ressortent positifs même sur un avoir : montant et quantité
        // y sont tous deux négatifs, la division rétablit le signe (comme l'ancien export).
        $prixUnitaire = $this->formatNumber($quantite != 0.0 ? round($montantEur / $quantite, 3) : 0);
        $prixUnitaireDevise = $this->formatNumber($quantite != 0.0 ? $montantDeviseBrut / $quantite : 0);

        // Le cube sépare désormais le code article et le code variante ; le CSV garde la forme ARTICLE_VARIANT
        $article = ($row['CODE_VARIANT'] ?? '') !== ''
            ? $row['ARTICLE'] . '_' . $row['CODE_VARIANT']
            : $row['ARTICLE'];

        return [
            $row['SOCIETE'],
            $row['PAYS'],
            $row['DATE_FACTURE'],
            $row['NUM_FACTURE'],
            $row['SDP_CLIENT'],
            $typeMasterCode . ' - ' . $libelleType,
            $this->codeLibelle($sivtyp, $libelleType),
            $this->codeLibelle($row['MOTIF_AVOIR_CODE'], $row['MOTIF_AVOIR_LIBELLE']),
            '',                                 // No DOSSIER RMA : pas d'équivalent chez LCS
            $row['REFERENCE_FACTURE'],
            $row['UTILISATEUR_CREATION'],
            '',                                 // CODE COMPTABLE
            $row['REPRESENTANT_1'],
            $row['REPRESENTANT_2'],
            $row['CLIENT_COMMANDE'],
            $row['TIERS_PAYEUR'],
            $row['RAISON_SOCIALE'],
            $row['INDEPENDANT_GROUPMENT'],
            '',                                 // MASTER CATEGORIE
            '',                                 // MARQUE
            $row['GENRE'],
            $row['FAMILLE'],
            $row['COLLECTION'],
            $row['SEGMENT_OFFRE'],
            $article,
            (int) $row['NOOS'] === 2 ? 'Oui' : 'Non',
            $row['DESIGNATION'],
            $qte,
            $isPassThru ? 0 : $qte,
            $row['DEVISE_FACTURE'],
            $prixUnitaire,
            $montant,
            $isPassThru ? 0 : $montant,
            $isDeviseSociete ? '' : $prixUnitaireDevise,
            $isDeviseSociete ? '' : $montantDevise,
            $isPassThru ? 0 : ($isDeviseSociete ? '' : $montantDevise),
            ...array_fill(0, 14, ''),           // No DE LOT → TOTAL TAR : partie achat à venir
            self::ETAT_FACTURE,
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
