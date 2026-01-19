<?php

namespace App\Service\kpi;

use App\Factory\MssqlManagerFactory;
use App\Repository\KpiDeckPresentationRepository;
use App\Service\Tools\GraphMailer;
use App\Service\Tools\Helpers;
use App\Service\Tools\MssqlManager;
use Psr\Log\LoggerInterface;

class OverviewBoutiquesKpi
{
    private MssqlManager $mssqlLcs;

    public function __construct(
        private KpiDeckPresentationRepository $kpiDeckPresentationRepository,
        private MssqlManagerFactory $mssqlManagerFactory,
        private LoggerInterface $logger,
        private GraphMailer $graphMailer,
        private Helpers $helpers
    )
    {
        $this->mssqlLcs = $this->mssqlManagerFactory->create('lcs');
    }

    public function getBoutiquesDataFromKpi(int $year, int $week, string $businessType = 'CS'): array
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
        $caFtwWeek = $this->getCaHtRaw($year, $week, true,false, '1 FOOTWEAR', $businessType);
        // APP
        $caAppWeek = $this->getCaHtRaw($year, $week, true,false, '2 TEXTILE', $businessType);

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

    public function getCaHtRaw(int $year, ?int $week = null, bool $withJo = true, bool $cumul = false, ?string $itemFamilyCode = null, string $businessType = 'CS'): array
    {
        $query = "
    SELECT
        l.Code,

        SUM(CASE WHEN YEAR(i.ExpectedInvoicingDate) = :year THEN i.AmountEurTM ELSE 0 END) AS ca_n,
        SUM(CASE WHEN YEAR(i.ExpectedInvoicingDate) = :year_minus_1 THEN i.AmountEurTM ELSE 0 END) AS ca_n1
        FROM [BI].[DWH].[F_Invoices] i
        LEFT JOIN [BI].[DWH].D_Location l ON i.LocationCode = l.Code
        LEFT JOIN [BI].[DWH].D_Item it ON i.ItemNo = it.ItemNo
        LEFT JOIN [BI].[DWH].D_Collection c on i.ItemNo = c.Code and i.SeriesNo = c.SeasonCode
        WHERE l.BusinessType IN (:businessType)
        AND YEAR(i.ExpectedInvoicingDate) IN (:year, :year_minus_1) ";

        $params = [
            'year' => $year,
            'year_minus_1' => $year - 1,
            'businessType' => $businessType,
        ];

        // semaine
        if ($week !== null && $cumul === false) {
            $query .= " AND DATEPART(ISO_WEEK, i.ExpectedInvoicingDate) = :week ";
            $params['week'] = $week;
        }
        // Année cumulative
        if ($week !== null && $cumul) {
            $query .= " AND DATEPART(ISO_WEEK, i.ExpectedInvoicingDate) <= :week ";
            $params['week'] = $week;
        }

        // exclusion JO
        if (!$withJo) {
            $query .= " AND c.RoyaltieCode NOT IN ('EFRO', 'EFRO 25/26', 'EFRP', 'EFRP 25/26', 'P2024') ";
        }

        // FTW / APP
        if ($itemFamilyCode !== null) {
            $query .= " AND it.ItemFamilyCode = :itemFamilyCode ";
            $params['itemFamilyCode'] = $itemFamilyCode;
        }

        $query .= "
                GROUP BY l.Code
                HAVING
                    SUM(CASE WHEN YEAR(i.ExpectedInvoicingDate) = :year THEN i.AmountEurTM ELSE 0 END) <> 0
            ";

        $rows = $this->mssqlLcs->executeQueryWithParams($query, $params);

        $blocks = [
            'full_boutique' => ['ca_n' => 0, 'ca_n1' => 0],
            'st_germain'    => ['ca_n' => 0, 'ca_n1' => 0],
            'citadium'      => ['ca_n' => 0, 'ca_n1' => 0],
        ];

        foreach ($rows as $row) {

            // Full boutique
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
                'vs_rfc' => -50.0, // à remplire dynamiquement
            ];
        }

        return $blocks;
    }

    public function getPannierMoyEtATV(int $year, int $week, string $businessType = 'CS'): array
    {
        /*
         * 1️⃣ REQUÊTE VENTES (semaine / N vs N-1)
         */
        $salesQuery = "
        SELECT
            l.Code,

            COUNT(DISTINCT CASE
                WHEN YEAR(i.ExpectedInvoicingDate) = :year
                THEN i.RetailTransactionNumber
            END) AS tickets_n,

            COUNT(DISTINCT CASE
                WHEN YEAR(i.ExpectedInvoicingDate) = :year_minus_1
                THEN i.RetailTransactionNumber
            END) AS tickets_n1,

            SUM(CASE
                WHEN YEAR(i.ExpectedInvoicingDate) = :year
                THEN i.Quantity
                ELSE 0
            END) AS qty_n,

            SUM(CASE
                WHEN YEAR(i.ExpectedInvoicingDate) = :year_minus_1
                THEN i.Quantity
                ELSE 0
            END) AS qty_n1,

            SUM(CASE
                WHEN YEAR(i.ExpectedInvoicingDate) = :year
                THEN i.AmountEurTM * 1.2
                ELSE 0
            END) AS ca_ttc_n,

            SUM(CASE
                WHEN YEAR(i.ExpectedInvoicingDate) = :year_minus_1
                THEN i.AmountEurTM * 1.2
                ELSE 0
            END) AS ca_ttc_n1

        FROM [BI].[DWH].[F_Invoices] i
        LEFT JOIN [BI].[DWH].D_Location l ON i.LocationCode = l.Code
        LEFT JOIN [BI].[DWH].D_Item it ON i.ItemNo = it.ItemNo
        WHERE
            l.BusinessType IN (:businessType)
            AND DATEPART(ISO_WEEK, i.ExpectedInvoicingDate) = :week
            AND YEAR(i.ExpectedInvoicingDate) IN (:year, :year_minus_1)
        GROUP BY l.Code
        HAVING SUM(CASE WHEN YEAR(i.ExpectedInvoicingDate) = :year THEN i.AmountEurTM ELSE 0 END) <> 0 ";

        $salesRows = $this->mssqlLcs->executeQueryWithParams($salesQuery, [
            'year' => $year,
            'year_minus_1' => $year - 1,
            'week' => $week,
            'businessType' => $businessType,
        ]);

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
            foreach (['tickets_n','tickets_n1','qty_n','qty_n1','ca_ttc_n','ca_ttc_n1'] as $field) {
                $blocks['full_boutique'][$field] += (float) $row->$field;
            }

            if ($row->Code === 'CSFR-STGER') {
                foreach (['tickets_n','tickets_n1','qty_n','qty_n1','ca_ttc_n','ca_ttc_n1'] as $field) {
                    $blocks['st_germain'][$field] += (float) $row->$field;
                }
            }

            if ($row->Code === 'CITADIUM') {
                foreach (['tickets_n','tickets_n1','qty_n','qty_n1','ca_ttc_n','ca_ttc_n1'] as $field) {
                    $blocks['citadium'][$field] += (float) $row->$field;
                }
            }
        }

        /*
         * 4️⃣ REQUÊTE STOCK (global, sans semaine)
         */
        $stockQuery = "
        SELECT
            l.Code,
            SUM(s.StockMovementQuantity) AS stock
        FROM [BI].[DWH].[F_Inventory] s
        LEFT JOIN [BI].[DWH].D_Location l ON s.LocationCode = l.Code
        WHERE
            l.BusinessType IN ('CS')
        and l.Code <> 'CSFR-RENNE'
        GROUP BY l.Code
    ";

        $stockRows = $this->mssqlLcs->executeQueryWithParams($stockQuery, []);

        /*
         * 5️⃣ AGRÉGATION DU STOCK
         */
        foreach ($stockRows as $row) {

            // Full boutique = somme de tout
            $blocks['full_boutique']['stock'] += (float) $row->stock;

            if ($row->Code === 'CSFR-STGER') {
                $blocks['st_germain']['stock'] += (float) $row->stock;
            }

            if ($row->Code === 'CITADIUM') {
                $blocks['citadium']['stock'] += (float) $row->stock;
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
                ? (int) floor($stock / $qty)
                : 0;

            $b = [
                // Données de base
                'qty' => (int) round($qty, 0),
                'stock' => (int) round($stock, 0),
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

    public function getRfcCaByStore(int $week, bool $cumul = false, string $businessType= 'CS'): array
    {
        $businessType = $businessType === 'CS' ? 'CONCEPT STORE' : 'FACTORY OUTLET';
        $query = "
        SELECT
            [CODE MAG] AS store_code,
            SUM([RFC WEEK]) AS rfc
        FROM UCD..RFC_AVEC_MARGE
        WHERE
            RESEAU = :businessType
    ";

        if ($cumul) {
            $query .= " AND SEMAINE <= :week ";
        } else {
            $query .= " AND SEMAINE = :week ";
        }

        $query .= " GROUP BY [CODE MAG] ";

        $rows = $this->mssqlLcs->executeQueryWithParams($query, [
            'week' => $week, 'businessType' => $businessType
        ]);

        // Initialisation
        $data = [
            'full_boutique' => 0,
            'st_germain'    => 0,
            'citadium'      => 0,
        ];

        foreach ($rows as $row) {
            switch ($row->store_code) {
                case 'CSFR-STGER':
                    $data['st_germain'] += (float) $row->rfc;
                    break;

                case 'CITADIUM':
                    $data['citadium'] += (float) $row->rfc;
                    break;
            }

            // Full boutique = tout cumuler
            $data['full_boutique'] += (float) $row->rfc;
        }

        return $data;
    }

}
