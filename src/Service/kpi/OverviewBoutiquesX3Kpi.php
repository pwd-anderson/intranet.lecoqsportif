<?php

namespace App\Service\kpi;

use App\Factory\MssqlManagerFactory;
use App\Repository\KpiDeckPresentationRepository;
use App\Service\Tools\GraphMailer;
use App\Service\Tools\Helpers;
use App\Service\Tools\MssqlManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class OverviewBoutiquesX3Kpi
{
    private MssqlManager $mssqlSei;

    public function __construct(
        private KpiDeckPresentationRepository $kpiDeckPresentationRepository,
        private MssqlManagerFactory $mssqlManagerFactory,
        private LoggerInterface $logger,
        private GraphMailer $graphMailer,
        private Helpers $helpers,
        #[Autowire('%db.lcs_sei%')]
        string $dbLcsSei,
    )
    {
        $this->mssqlSei = $this->mssqlManagerFactory->create($dbLcsSei);
    }

    public function getBoutiquesDataFromKpi(int $year, int $week, string $businessType = 'CONCEPT STORE'): array
    {
        $storeKeys = ['full_boutique', 'st_germain', 'citadium'];

        $comments = $this->kpiDeckPresentationRepository->findByDeck(
            'overview_boutiques',
            $year,
            $week,
            $storeKeys
        );
        $commentsByStore = [];

        foreach ($comments as $comment) {
            $commentsByStore[$comment->getStoreKey()] = $comment->getCommentHtml();
        }
        // FTW
        $caFtwWeek = $this->getCaHtRaw($year, $week, true,false, 'FTW', $businessType);
        // APP
        $caAppWeek = $this->getCaHtRaw($year, $week, true,false, 'APL', $businessType);

        // rfc
        $rfcWeek = $this->getRfcCaByStore($week, false, $businessType);
        $rfcCumul = $this->getRfcCaByStore($week, true, $businessType);

        $caWeekSansJo = $this->getCaHtRaw($year, $week, false, false, null, $businessType);
        $caWeek = $this->getCaHtRaw($year, $week, true, false, null, $businessType);// bloc haut
        $panierAtv = $this->getPannierMoyEtATV($year, $week, $businessType);
        $caYear = $this->getCaHtRaw($year, $week, true, true, null, $businessType); // cumul année

        return [
            $this->buildBoutiqueBlock(
                'Full boutiques',
                'full_boutique',
                $caWeek['full_boutique'],
                $panierAtv['full_boutique'],
                $caYear['full_boutique'],
                    $commentsByStore['full_boutique'] ?? null,
                $caWeekSansJo['full_boutique'],
                $rfcWeek['full_boutique'] ?? 0,
                $rfcCumul['full_boutique'] ?? 0,
                $caFtwWeek['full_boutique'] ?? null,
                $caAppWeek['full_boutique'] ?? null
            ),
            $this->buildBoutiqueBlock(
                'St Germain',
                'st_germain',
                $caWeek['st_germain'],
                $panierAtv['st_germain'],
                $caYear['st_germain'],
                $commentsByStore['st_germain'] ?? null,
                $caWeekSansJo['st_germain'],
                $rfcWeek['st_germain'] ?? 0,
                $rfcCumul['st_germain'] ?? 0,
                $caFtwWeek['st_germain'] ?? null,
                $caAppWeek['st_germain'] ?? null
            ),
            $this->buildBoutiqueBlock(
                'Citadium',
                'citadium',
                $caWeek['citadium'],
                $panierAtv['citadium'],
                $caYear['citadium'],
                $commentsByStore['citadium'] ?? null,
                $caWeekSansJo['citadium'],
                $rfcWeek['citadium'] ?? 0,
                $rfcCumul['citadium'] ?? 0,
                $caFtwWeek['citadium'] ?? null,
                $caAppWeek['citadium'] ?? null
            ),
        ];
    }

    private function buildBoutiqueBlock(
        string $name,
        string $storeKey,
        array $caWeek,
        array $panierAtv,
        array $caYear,
        ?string $comment,
        array $horsJoPercent,
        float $rfcWeek,
        float $rfcCumul,
        ?array $caFtwWeek,
        ?array $caAppWeek
    ): array {

        $caWeekK = $caWeek['ca'] / 1000;
        $variationRfcWeek = $this->helpers->variation($caWeekK, $rfcWeek);

        $caCumulK = $caYear['ca'] / 1000;
        $variationRfcCumul = $this->helpers->variation($caCumulK, $rfcCumul);

        // --- FTW ---
        $ftwCaK = $caFtwWeek ? $caFtwWeek['ca'] / 1000 : 0;
        $ftwVariationRfc = $this->helpers->variation($ftwCaK, $rfcWeek);

        // --- APP ---
        $appCaK = $caAppWeek ? $caAppWeek['ca'] / 1000 : 0;
        $appVariationRfc = $this->helpers->variation($appCaK, $rfcWeek);

        return [
            'store_key' => $storeKey,
            'name' => $name,

            // CA semaine
            'ca_ht' => number_format($caWeek['ca'], 0, ',', ' '),
            'variation_rfc' => $variationRfcWeek,
            'variation_n1' => $caWeek['vs_n1'],
            'variation_hors_jo' => $horsJoPercent['vs_n1'] ?? 0,

            // Panier / ATV
            'panier_moyen' => number_format($panierAtv['panier'], 0, ',', ' '),
            'panier_variation' => $panierAtv['panier_vs_n1'],
            'atv' => number_format($panierAtv['atv'], 2, ',', ''),
            'atv_variation' => $panierAtv['atv_vs_n1'],

            // Trafic (fictif)
            'trafic' => 'XX',

            // FTW / APP fictifs
            'ftw_ca' => number_format($ftwCaK, 1, ',', ''),
            'ftw_variation_rfc' => $ftwVariationRfc,
            'app_ca' => number_format($appCaK, 1, ',', ''),
            'app_variation_rfc' => $appVariationRfc,

            // Couverture
            'couverture' => (string) $panierAtv['couverture'],

            // Cumul année
            'cumul_ca' => (float) $caYear['ca'],
            'cumul_variation_rfc' => $variationRfcCumul,
            'cumul_variation_n1' => $caYear['vs_n1'],

            'comment' => $comment,
        ];
    }

    public function getCaHtRaw(
        int $year,
        ?int $week = null,
        bool $withJo = true,
        bool $cumul = false,
        ?string $itemFamilyCode = null,
        string $businessType = 'CONCEPT STORE'
    ): array {
        $yearMinus1 = $year - 1;

        $query = "
            select I.LOCATIONCODE as Code,
            sum(case when year(I.DOCUMENTPOSTINGDATE) = {$year} then I.AMOUNTEURTM else 0 end) as ca_n,
            sum(case when year(I.DOCUMENTPOSTINGDATE) = {$yearMinus1} then I.AMOUNTEURTM else 0 end) as ca_n1
            from SEI_X3_LCS.CONSO_INVOICES I
            LEFT JOIN X3_LCS.ITMMASTER ITM ON I.ITEMNO = ITM.ITMREF_0
            left join X3_LCS.ZMODELE ZMO ON ITM.ZMODELCOD_0 = ZMO.ZMODCOD_0
            LEFT JOIN X3_LCS.BPCUSTOMER BPC ON I.CUSTOMERNO = BPC.BPCNUM_0
            LEFT JOIN X3_LCS.ATEXTRA ATX ON ATX.IDENT2_0 = BPC.TSCCOD_3 AND ATX.CODFIC_0 = 'ATABDIV' AND ATX.LANGUE_0 = 'FRA' AND ATX.ZONE_0 = 'LNGDES' AND ATX.IDENT1_0 = '33'
            WHERE I.SOURCE = 'LCS'
            AND ATX.TEXTE_0 = '{$businessType}'
            AND (
            CASE
                WHEN DATEPART(ISO_WEEK, I.DOCUMENTPOSTINGDATE) = 1 AND MONTH(I.DOCUMENTPOSTINGDATE) = 12
                    THEN YEAR(I.DOCUMENTPOSTINGDATE) + 1
                WHEN DATEPART(ISO_WEEK, I.DOCUMENTPOSTINGDATE) >= 52 AND MONTH(I.DOCUMENTPOSTINGDATE) = 1
                    THEN YEAR(I.DOCUMENTPOSTINGDATE) - 1
                ELSE YEAR(I.DOCUMENTPOSTINGDATE)
            END
        ) IN ({$year}, {$yearMinus1})
        ";

        // semaine
        if ($week !== null && $cumul === false) {
            $query .= " AND DATEPART(ISO_WEEK, I.DOCUMENTPOSTINGDATE) = {$week} ";
        }

        // Année cumulative
        if ($week !== null && $cumul === true) {
            $query .= " AND DATEPART(ISO_WEEK, I.DOCUMENTPOSTINGDATE) <= {$week} ";
        }

        // exclusion JO
        if (!$withJo) {
            $query .= "
            AND ZMO.ZMODDES_0 NOT LIKE 'EFRO%'
            AND ZMO.ZMODDES_0 NOT LIKE 'EFRP%'
            ";
        }

        // FTW / APP
        if ($itemFamilyCode !== null) {
            $query .= " AND ITM.TCLCOD_0 = '{$itemFamilyCode}' ";
        }

        $query .= "
        GROUP BY I.LOCATIONCODE
        HAVING
            SUM(CASE WHEN YEAR(I.DOCUMENTPOSTINGDATE) = {$year} THEN I.AMOUNTEURTM ELSE 0 END) <> 0
    ";

        $rows = $this->mssqlSei->executeQuery($query);

        $blocks = [
            'full_boutique' => ['ca_n' => 0, 'ca_n1' => 0],
            'st_germain'    => ['ca_n' => 0, 'ca_n1' => 0],
            'citadium'      => ['ca_n' => 0, 'ca_n1' => 0],
        ];

        foreach ($rows as $row) {

            $blocks['full_boutique']['ca_n']  += (float) $row->ca_n;
            $blocks['full_boutique']['ca_n1'] += (float) $row->ca_n1;

            if ($row->Code === 'CSFR-STGER') {
                $blocks['st_germain']['ca_n']  += (float) $row->ca_n;
                $blocks['st_germain']['ca_n1'] += (float) $row->ca_n1;
            }

            if ($row->Code === 'CITADIUM') {
                $blocks['citadium']['ca_n']  += (float) $row->ca_n;
                $blocks['citadium']['ca_n1'] += (float) $row->ca_n1;
            }
        }

        foreach ($blocks as &$b) {
            $b = [
                'ca' => round($b['ca_n'], 0),
                'vs_n1' => $this->helpers->variation($b['ca_n'], $b['ca_n1']),
                'vs_rfc' => -50.0, // placeholder
            ];
        }

        return $blocks;
    }

    public function getPannierMoyEtATV(int $year, int $week, string $businessType = 'CONCEPT STORE'): array
    {
        $yearMinus1 = $year - 1;
        /*
         * 1️⃣ REQUÊTE VENTES (semaine / N vs N-1)
         */
        $salesQuery = "
            SELECT
                I.LOCATIONCODE as Code,

                COUNT(DISTINCT CASE
                    WHEN YEAR(I.DOCUMENTPOSTINGDATE) = {$year}
                    THEN I.RETAILTRANSACTIONNUMBER
                END) AS tickets_n,

                COUNT(DISTINCT CASE
                    WHEN YEAR(I.DOCUMENTPOSTINGDATE) = {$yearMinus1}
                    THEN I.RETAILTRANSACTIONNUMBER
                END) AS tickets_n1,

                SUM(CASE
                    WHEN YEAR(I.DOCUMENTPOSTINGDATE) = {$year}
                    THEN I.QUANTITY
                    ELSE 0
                END) AS qty_n,

                SUM(CASE
                    WHEN YEAR(I.DOCUMENTPOSTINGDATE) = {$yearMinus1}
                    THEN I.QUANTITY
                    ELSE 0
                END) AS qty_n1,

                SUM(CASE
                    WHEN YEAR(I.DOCUMENTPOSTINGDATE) = {$year}
                    THEN I.AMOUNTEURTM * 1.2
                    ELSE 0
                END) AS ca_ttc_n,

                SUM(CASE
                    WHEN YEAR(I.DOCUMENTPOSTINGDATE) = {$yearMinus1}
                    THEN I.AMOUNTEURTM * 1.2
                    ELSE 0
                END) AS ca_ttc_n1

            FROM SEI_X3_LCS.CONSO_INVOICES I
            LEFT JOIN X3_LCS.ITMMASTER ITM ON I.ITEMNO = ITM.ITMREF_0
            left join X3_LCS.ZMODELE ZMO ON ITM.ZMODELCOD_0 = ZMO.ZMODCOD_0
            LEFT JOIN X3_LCS.BPCUSTOMER BPC ON I.CUSTOMERNO = BPC.BPCNUM_0
            LEFT JOIN X3_LCS.ATEXTRA ATX ON ATX.IDENT2_0 = BPC.TSCCOD_3 AND ATX.CODFIC_0 = 'ATABDIV' AND ATX.LANGUE_0 = 'FRA' AND ATX.ZONE_0 = 'LNGDES' AND ATX.IDENT1_0 = '33'
            WHERE I.SOURCE = 'LCS'
            AND ATX.TEXTE_0 = '{$businessType}'
            AND DATEPART(ISO_WEEK, I.DOCUMENTPOSTINGDATE) = {$week}
                AND YEAR(I.DOCUMENTPOSTINGDATE) IN ({$year}, {$yearMinus1})

            GROUP BY I.LOCATIONCODE
            HAVING
                SUM(CASE WHEN YEAR(I.DOCUMENTPOSTINGDATE) = {$year} THEN I.AMOUNTEURTM ELSE 0 END) <> 0
        ";

        $salesRows = $this->mssqlSei->executeQuery($salesQuery);

        /*
         * 2️⃣ INITIALISATION DES BLOCS (Option A)
         */
        $blocks = [
            'full_boutique' => [
                'tickets_n' => 0, 'tickets_n1' => 0,
                'qty_n' => 0, 'qty_n1' => 0,
                'ca_ttc_n' => 0, 'ca_ttc_n1' => 0,
                'stock' => 0,
            ],
            'st_germain' => [
                'tickets_n' => 0, 'tickets_n1' => 0,
                'qty_n' => 0, 'qty_n1' => 0,
                'ca_ttc_n' => 0, 'ca_ttc_n1' => 0,
                'stock' => 0,
            ],
            'citadium' => [
                'tickets_n' => 0, 'tickets_n1' => 0,
                'qty_n' => 0, 'qty_n1' => 0,
                'ca_ttc_n' => 0, 'ca_ttc_n1' => 0,
                'stock' => 0,
            ],
        ];

        /*
         * 3️⃣ AGRÉGATION DES VENTES
         */
        foreach ($salesRows as $row) {

            // Full boutique = somme de tout
            foreach (['tickets_n', 'tickets_n1', 'qty_n', 'qty_n1', 'ca_ttc_n', 'ca_ttc_n1'] as $field) {
                $blocks['full_boutique'][$field] += (float)$row->$field;
            }

            if ($row->Code === 'CSFR-STGER') {
                foreach (['tickets_n', 'tickets_n1', 'qty_n', 'qty_n1', 'ca_ttc_n', 'ca_ttc_n1'] as $field) {
                    $blocks['st_germain'][$field] += (float)$row->$field;
                }
            }

            if ($row->Code === 'CITADIUM') {
                foreach (['tickets_n', 'tickets_n1', 'qty_n', 'qty_n1', 'ca_ttc_n', 'ca_ttc_n1'] as $field) {
                    $blocks['citadium'][$field] += (float)$row->$field;
                }
            }
        }

        /*
         * 4️⃣ REQUÊTE STOCK (global, sans semaine)
         */
        $stockQuery = "
                SELECT SITE as Code,
                sum(STOCK_INTERNE) as stock
              FROM [MASTER_TABLES].[STOCK_ALLOCATION]
              group by SITE
            ";

        $stockRows = $this->mssqlSei->executeQuery($stockQuery);

        /*
         * 5️⃣ AGRÉGATION DU STOCK
         */
        foreach ($stockRows as $row) {

            // Full boutique = somme de tout
            $blocks['full_boutique']['stock'] += (float)$row->stock;

            if ($row->Code === 'CSFR-STGER') {
                $blocks['st_germain']['stock'] += (float)$row->stock;
            }

            if ($row->Code === 'CITADIUM') {
                $blocks['citadium']['stock'] += (float)$row->stock;
            }
        }

        /*
         * 6️⃣ CALCULS MÉTIER FINALS
         */
        foreach ($blocks as &$b) {

            $qty = $b['qty_n'];
            $stock = $b['stock'];

            // ATV
            $atv_n = $b['tickets_n'] > 0 ? $qty / $b['tickets_n'] : 0;
            $atv_n1 = $b['tickets_n1'] > 0 ? $b['qty_n1'] / $b['tickets_n1'] : 0;

            // Panier moyen
            $panier_n = $b['tickets_n'] > 0 ? $b['ca_ttc_n'] / $b['tickets_n'] : 0;
            $panier_n1 = $b['tickets_n1'] > 0 ? $b['ca_ttc_n1'] / $b['tickets_n1'] : 0;

            // ✅ Couverture = stock / qty (arrondi à l'inférieur)
            $couverture = $qty > 0
                ? (int)floor($stock / $qty)
                : 0;

            $b = [
                // Données de base
                'qty' => (int)round($qty, 0),
                'stock' => (int)round($stock, 0),
                'couverture' => $couverture,

                // ATV
                'atv' => round($atv_n, 2),
                'atv_vs_n1' => $this->helpers->variation($atv_n, $atv_n1),

                // Panier moyen
                'panier' => round($panier_n, 2),
                'panier_vs_n1' => $this->helpers->variation($panier_n, $panier_n1),

                // RFC fictif
                'vs_rfc' => -50.0,
            ];
        }

        return $blocks;
    }

    public function getRfcCaByStore(int $week, bool $cumul = false, string $businessType = 'CONCEPT STORE'): array
    {
        // Mapping métier (inchangé)
        $businessTypeLabel = $businessType === 'CONCEPT STORE'
            ? 'CONCEPT STORE'
            : 'FACTORY OUTLET';

        $query = "
        SELECT
            [CODE MAG] AS store_code,
            SUM([RFC WEEK]) AS rfc
        FROM [SEICube].[UCD_LCS].[RFC_AVEC_MARGE]
        WHERE
            RESEAU = '{$businessTypeLabel}'
    ";

        if ($cumul) {
            $query .= " AND SEMAINE <= {$week} ";
        } else {
            $query .= " AND SEMAINE = {$week} ";
        }

        $query .= " GROUP BY [CODE MAG] ";

        // 🔥 Exécution directe
        $rows = $this->mssqlSei->executeQuery($query);

        // Initialisation
        $data = [
            'full_boutique' => 0.0,
            'st_germain'    => 0.0,
            'citadium'      => 0.0,
        ];

        foreach ($rows as $row) {

            $rfc = (float) ($row->rfc ?? 0);

            switch ($row->store_code) {
                case 'CSFR-STGER':
                    $data['st_germain'] += $rfc;
                    break;

                case 'CITADIUM':
                    $data['citadium'] += $rfc;
                    break;
            }

            // Full boutique = cumul de tout
            $data['full_boutique'] += $rfc;
        }

        return $data;
    }

}
