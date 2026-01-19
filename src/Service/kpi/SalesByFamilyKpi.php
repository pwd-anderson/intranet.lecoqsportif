<?php

namespace App\Service\kpi;

use App\Factory\MssqlManagerFactory;
use App\Service\Tools\Helpers;
use App\Service\Tools\MssqlManager;

class SalesByFamilyKpi
{
    private MssqlManager $mssqlLcs;

    // familles
    private const FAMILY_FOOTWEAR = '1 FOOTWEAR';
    private const FAMILY_TEXTILE  = '2 TEXTILE';
    private const FAMILY_HARDWARE = '3 HARDWARE';

    // exclusions hors JO (RoyaltieCode)
    private const ROYALTIES_EXCLUDED = ['EFRO', 'EFRO 25/26', 'EFRP', 'EFRP 25/26', 'P2024'];

    public function __construct(
        private MssqlManagerFactory $mssqlManagerFactory,
        private Helpers $helpers
    ) {
        $this->mssqlLcs = $this->mssqlManagerFactory->create('lcs');
    }

    public function getData(int $year, int $week, string $businessType = 'CS'): array
    {
        // 1) CHART 1 : ventes par famille (K€) année N
        $familyTotalsK = $this->fetchFamilyTotalsK($year, $week, $businessType);

        // 2) CHART 2 : ventes semaine par jour (K€) année N
        // -> on sort 2 datasets uniquement (TEXTILE + FOOTWEAR) pour coller à ton Twig/JS actuel
        $dailyByFamilyK = $this->fetchDailyByFamilyK($year, $week, $businessType);
        $weeklyChart = $this->buildWeeklyChart3Datasets($dailyByFamilyK);

        // 3) TABLES : group code (N / N-1 / evolution) + total + LW
        $apparel = $this->buildGroupBlock(self::FAMILY_TEXTILE, $year, $week, 'ItemGroupCode');
        $footwear = $this->buildGroupBlock(self::FAMILY_FOOTWEAR, $year, $week, 'GenusCode');

        // 4) TOP produits : 5 textile + 5 footwear (en €)
        $topTextile = $this->fetchTopProductsEuro($year, $week, self::FAMILY_TEXTILE, 5, $businessType);
        $topTextile = $this->helpers->convertArrayToUtf8($topTextile);
        $topFootwear = $this->fetchTopProductsEuro($year, $week, self::FAMILY_FOOTWEAR, 5, $businessType);;
        return [
            'sales_by_family' => [
                'labels' => [
                    self::FAMILY_TEXTILE,
                    self::FAMILY_FOOTWEAR,
                    self::FAMILY_HARDWARE,
                ],
                'data' => [
                    (float) ($familyTotalsK[self::FAMILY_TEXTILE] ?? 0),
                    (float) ($familyTotalsK[self::FAMILY_FOOTWEAR] ?? 0),
                    (float) ($familyTotalsK[self::FAMILY_HARDWARE] ?? 0),
                ],
                // si tu veux, tu peux hardcoder ici les couleurs pour matcher ta charte
                'colors' => ['#1f2d5c', '#5ca83e', '#9aa5b1'],
            ],

            'weekly_sales' => $weeklyChart,

            'apparel' => $apparel,
            'footwear' => $footwear,

            // 10 lignes : 5 textile puis 5 footwear (ton Twig met un espace à index0==5)
            'top_products' => array_merge($topTextile, $topFootwear),
        ];
    }

    /* =========================================================
       HELPERS SQL
    ========================================================= */

    private function baseJoFilterSql(): string
    {
        // garde les NULL + exclut la liste
        return " AND (co.RoyaltieCode IS NULL OR co.RoyaltieCode NOT IN ('EFRO','EFRO 25/26','EFRP','EFRP 25/26','P2024')) ";
    }

    /**
     * 1) Totaux par famille (K€) - année N uniquement
     * => correspond à ta requête "par item code"
     */
    private function fetchFamilyTotalsK(int $year, int $week, string $businessType = 'CS'): array
    {
        $sql = "
            SELECT
                it.ItemFamilyCode AS family,
                SUM(i.AmountEurTM)/1000.0 AS amount_k
            FROM [BI].[DWH].[F_Invoices] i
            LEFT JOIN [BI].[DWH].D_Location l  ON i.LocationCode = l.Code
            LEFT JOIN [BI].[DWH].D_Item it     ON i.ItemNo = it.ItemNo
            LEFT JOIN [BI].[DWH].D_Collection co ON i.ItemNo = co.Code AND i.SeriesNo = co.SeasonCode
            LEFT JOIN [BI].[DWH].D_Customer c  ON i.CustomerNo = c.Code AND i.CompanyCode = c.CompanyCode
            WHERE
                l.BusinessType IN (:businessType)
                AND YEAR(i.ExpectedInvoicingDate) = :year
                AND DATEPART(ISO_WEEK, i.ExpectedInvoicingDate) = :week
                {$this->baseJoFilterSql()}
                AND it.ItemFamilyCode IN (:f1, :f2, :f3)
            GROUP BY it.ItemFamilyCode
        ";

        $rows = $this->mssqlLcs->executeQueryWithParams($sql, [
            'businessType' => $businessType,
            'year' => $year,
            'week' => $week,
            'f1' => self::FAMILY_FOOTWEAR,
            'f2' => self::FAMILY_TEXTILE,
            'f3' => self::FAMILY_HARDWARE,
        ]);

        $out = [];
        foreach ($rows as $r) {
            $out[(string)$r->family] = (float)($r->amount_k ?? 0);
        }
        return $out;
    }

    /**
     * 2) Par jour + famille (K€) - année N uniquement
     * => correspond à ta requête "par jour/item code"
     * IMPORTANT: day() retourne le jour du mois => labels = jours du mois (comme ta requête)
     */
    private function fetchDailyByFamilyK(int $year, int $week, string $businessType = 'CS'): array
    {
        $sql = "
            SELECT
                it.ItemFamilyCode AS family,
                DAY(i.ExpectedInvoicingDate) AS jour,
                SUM(i.AmountEurTM)/1000.0 AS amount_k
            FROM [BI].[DWH].[F_Invoices] i
            LEFT JOIN [BI].[DWH].D_Location l  ON i.LocationCode = l.Code
            LEFT JOIN [BI].[DWH].D_Item it     ON i.ItemNo = it.ItemNo
            LEFT JOIN [BI].[DWH].D_Collection co ON i.ItemNo = co.Code AND i.SeriesNo = co.SeasonCode
            LEFT JOIN [BI].[DWH].D_Customer c  ON i.CustomerNo = c.Code AND i.CompanyCode = c.CompanyCode
            WHERE
                l.BusinessType IN (:businessType)
                AND YEAR(i.ExpectedInvoicingDate) = :year
                AND DATEPART(ISO_WEEK, i.ExpectedInvoicingDate) = :week
                {$this->baseJoFilterSql()}
                AND it.ItemFamilyCode IN (:f1, :f2, :f3)
            GROUP BY it.ItemFamilyCode, DAY(i.ExpectedInvoicingDate)
            ORDER BY jour ASC
        ";

        $rows = $this->mssqlLcs->executeQueryWithParams($sql, [
            'year' => $year,
            'week' => $week,
            'businessType' => $businessType,
            'f1' => self::FAMILY_FOOTWEAR,
            'f2' => self::FAMILY_TEXTILE,
            'f3' => self::FAMILY_HARDWARE,
        ]);

        // out[jour][family] = amount_k
        $out = [];
        foreach ($rows as $r) {
            $jour = (int)($r->jour ?? 0);
            $fam  = (string)($r->family ?? '');
            if ($jour <= 0 || $fam === '') continue;
            $out[$jour][$fam] = (float)($r->amount_k ?? 0);
        }

        return $out;
    }

    /**
     * Construit le format attendu par ton Twig/JS :
     * weekly_sales.labels + weekly_sales.datasets[]
     * -> 3 datasets : TEXTILE + FOOTWEAR + HARDWARE
     */
    private function buildWeeklyChart3Datasets(array $dailyByFamilyK): array
    {
        $jours = array_keys($dailyByFamilyK);
        sort($jours);

        $labels = array_map(fn($d) => (string)$d, $jours);

        $dataTextile = [];
        $dataFootwear = [];
        $dataHardware = [];

        foreach ($jours as $j) {
            $dataTextile[]  = (float)($dailyByFamilyK[$j][self::FAMILY_TEXTILE] ?? 0);
            $dataFootwear[] = (float)($dailyByFamilyK[$j][self::FAMILY_FOOTWEAR] ?? 0);
            $dataHardware[] = (float)($dailyByFamilyK[$j][self::FAMILY_HARDWARE] ?? 0);
        }

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'TEXTILE',
                    'data' => $dataTextile,
                    'backgroundColor' => '#5ca83e',
                    'stack' => 'Stack 0',
                ],
                [
                    'label' => 'FOOTWEAR',
                    'data' => $dataFootwear,
                    'backgroundColor' => '#1f2d5c',
                    'stack' => 'Stack 0',
                ],
                [
                    'label' => 'HARDWARE',
                    'data' => $dataHardware,
                    'backgroundColor' => '#9aa5b1',
                    'stack' => 'Stack 0',
                ],
            ],
        ];
    }

    /**
     * 3) Bloc tableau (items + total + lw) pour une famille
     * - amount_n / amount_n_1 / evolution (%)
     * - total idem
     * - lw = total N semaine-1 (en €)
     */
    private function buildGroupBlock(string $family, int $year, int $week, string $groupField): array
    {
        $items = $this->fetchGroupByFamilyWithNAndN1($family, $year, $week, $groupField);

        $totalN = 0.0;
        $totalN1 = 0.0;

        foreach ($items as &$it) {
            $totalN += $it['amount_n'];
            $totalN1 += $it['amount_n_1'];
            $it['evolution'] = $this->helpers->variation($it['amount_n'], $it['amount_n_1']);
        }
        unset($it);

        $totalEvolution = $this->helpers->variation($totalN, $totalN1);

        // LW = semaine-1 (sur l’année N)
        $lw = $this->fetchFamilyTotalEuroForWeek($family, $year, max(1, $week - 1));

        return [
            'items' => array_map(function(array $x) {
                // Twig attend "name"
                return [
                    'name' => $x['name'],
                    'amount_n' => (float) $x['amount_n'],
                    'amount_n_1' => (float) $x['amount_n_1'],
                    'evolution' => $x['evolution'],
                ];
            }, $items),

            'total' => [
                'amount_n' => (float) $totalN,
                'amount_n_1' => (float) $totalN1,
                'evolution' => $totalEvolution,
            ],

            'lw' => (float) $lw,
        ];
    }

    /**
     * GROUP CODE avec N & N-1 (en €)
     * -> tu avais une requête pour TEXTILE uniquement en 2025
     * -> ici on fait N et N-1, et on rend la requête générique pour TEXTILE/FOOTWEAR
     */
    private function fetchGroupByFamilyWithNAndN1(string $family, int $year, int $week, string $groupField): array
    {
        // Sécurité : évite l’injection SQL via champ dynamique
        $allowed = ['ItemGroupCode', 'GenusCode'];
        if (!in_array($groupField, $allowed, true)) {
            $groupField = 'ItemGroupCode';
        }
        $sql = "
            SELECT
                co.$groupField AS grp,
                SUM(CASE WHEN YEAR(i.ExpectedInvoicingDate) = :y  THEN i.AmountEurTM ELSE 0 END) AS n,
                SUM(CASE WHEN YEAR(i.ExpectedInvoicingDate) = :y1 THEN i.AmountEurTM ELSE 0 END) AS n1
            FROM [BI].[DWH].[F_Invoices] i
            LEFT JOIN [BI].[DWH].D_Location l  ON i.LocationCode = l.Code
            LEFT JOIN [BI].[DWH].D_Item it     ON i.ItemNo = it.ItemNo
            LEFT JOIN [BI].[DWH].D_Collection co ON i.ItemNo = co.Code AND i.SeriesNo = co.SeasonCode
            LEFT JOIN [BI].[DWH].D_Customer c  ON i.CustomerNo = c.Code AND i.CompanyCode = c.CompanyCode
            WHERE
                l.BusinessType IN ('CS')
                AND YEAR(i.ExpectedInvoicingDate) IN (:y, :y1)
                AND DATEPART(ISO_WEEK, i.ExpectedInvoicingDate) = :week
                {$this->baseJoFilterSql()}
                AND it.ItemFamilyCode = :family
                AND co.$groupField IS NOT NULL
            GROUP BY co.$groupField
            HAVING SUM(CASE WHEN YEAR(i.ExpectedInvoicingDate) = :y THEN i.AmountEurTM ELSE 0 END) <> 0
            ORDER BY co.$groupField ASC
        ";

        $rows = $this->mssqlLcs->executeQueryWithParams($sql, [
            'y' => $year,
            'y1' => $year - 1,
            'week' => $week,
            'family' => $family,
        ]);

        $out = [];
        foreach ($rows as $r) {
            $name = (string)($r->grp ?? '');
            if ($name === '') continue;

            $out[] = [
                'name' => $name,
                'amount_n' => (float)($r->n ?? 0),
                'amount_n_1' => (float)($r->n1 ?? 0),
            ];
        }
        return $out;
    }

    /**
     * LW = total famille semaine X (en €)
     */
    private function fetchFamilyTotalEuroForWeek(string $family, int $year, int $week): float
    {
        $sql = "
            SELECT
                SUM(i.AmountEurTM) AS total_eur
            FROM [BI].[DWH].[F_Invoices] i
            LEFT JOIN [BI].[DWH].D_Location l  ON i.LocationCode = l.Code
            LEFT JOIN [BI].[DWH].D_Item it     ON i.ItemNo = it.ItemNo
            LEFT JOIN [BI].[DWH].D_Collection co ON i.ItemNo = co.Code AND i.SeriesNo = co.SeasonCode
            WHERE
                l.BusinessType IN ('CS')
                AND YEAR(i.ExpectedInvoicingDate) = :year
                AND DATEPART(ISO_WEEK, i.ExpectedInvoicingDate) = :week
                {$this->baseJoFilterSql()}
                AND it.ItemFamilyCode = :family
        ";

        $rows = $this->mssqlLcs->executeQueryWithParams($sql, [
            'year' => $year,
            'week' => $week,
            'family' => $family,
        ]);

        return isset($rows[0]->total_eur) ? (float)$rows[0]->total_eur : 0.0;
    }

    /**
     * 4) Top produits (en €) : marge + qty
     * -> comme ta requête top 5 (mais paramétrable famille + limit)
     */
    private function fetchTopProductsEuro(int $year, int $week, string $family, int $limit = 5, string $businessType = 'CS'): array
    {
        $sql = "
            SELECT TOP {$limit}
                it.ItemNo AS itemno,
                it.[Description] AS descr,
                SUM(i.AmountEurTM) AS amount_eur,
                SUM(i.Quantity) AS qty
            FROM [BI].[DWH].[F_Invoices] i
            LEFT JOIN [BI].[DWH].D_Location l  ON i.LocationCode = l.Code
            LEFT JOIN [BI].[DWH].D_Item it     ON i.ItemNo = it.ItemNo
            LEFT JOIN [BI].[DWH].D_Collection co ON i.ItemNo = co.Code AND i.SeriesNo = co.SeasonCode
            WHERE
                l.BusinessType IN (:businessType)
                AND YEAR(i.ExpectedInvoicingDate) = :year
                AND DATEPART(ISO_WEEK, i.ExpectedInvoicingDate) = :week
                {$this->baseJoFilterSql()}
                AND it.ItemFamilyCode = :family
            GROUP BY it.ItemNo, it.[Description]
            ORDER BY SUM(i.AmountEurTM) DESC
        ";

        $rows = $this->mssqlLcs->executeQueryWithParams($sql, [
            'year' => $year,
            'week' => $week,
            'family' => $family,
            'businessType' => $businessType,
        ]);

        $out = [];
        foreach ($rows as $r) {

            $itemNo = trim((string) ($r->itemno ?? ''));
            $safeItemNo = rawurlencode($itemNo);

            $out[] = [
                'image'  => "https://www.lecoqbiz.com/CMS/Images/Small/{$safeItemNo}.jpg",
                'code'   => $itemNo,
                'name'   => (string)($r->descr ?? ''),
                'qty'    => (int)($r->qty ?? 0),
                'amount' => (float)($r->amount_eur ?? 0),
            ];
        }

        return $out;
    }
}
