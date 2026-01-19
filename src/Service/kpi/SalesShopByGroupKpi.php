<?php

namespace App\Service\kpi;

use App\Factory\MssqlManagerFactory;
use App\Service\Tools\Helpers;
use App\Service\Tools\MssqlManager;

class SalesShopByGroupKpi
{
    private MssqlManager $mssqlLcs;

    // familles
    private const FAMILY_TEXTILE  = '2 TEXTILE';
    private const FAMILY_FOOTWEAR = '1 FOOTWEAR';

    public function __construct(
        private MssqlManagerFactory $mssqlManagerFactory,
        private Helpers $helpers
    ) {
        $this->mssqlLcs = $this->mssqlManagerFactory->create('lcs');
    }

    public function getData(int $year, int $week): array
    {
        return [
            'shops' => [
                $this->buildShopConceptStore('ST GERMAIN', 'CSFR-STGER', $year, $week),
                $this->buildShopConceptStore('CITADIUM', 'CITADIUM', $year, $week),
                $this->buildShopRetailTotal("TOTAL\nRETAIL", $year, $week),
            ],
        ];
    }

    /* =========================================================
       BUILDERS
    ========================================================= */

    private function buildShopConceptStore(string $title, string $storeCode, int $year, int $week): array
    {
        return [
            'name' => $title,
            'categories' => [
                $this->buildCategory(
                    categoryName: 'APPAREL',
                    codeLabel: 'Item Group Code',
                    familyCode: self::FAMILY_TEXTILE,
                    year: $year,
                    week: $week,
                    scope: [
                        'mode' => 'cs_store',
                        'storeCode' => $storeCode,
                    ],
                    groupField: 'ItemGroupCode'
                ),
                $this->buildCategory(
                    categoryName: 'FOOTWEAR',
                    codeLabel: 'Genus Code',
                    familyCode: self::FAMILY_FOOTWEAR,
                    year: $year,
                    week: $week,
                    scope: [
                        'mode' => 'cs_store',
                        'storeCode' => $storeCode,
                    ],
                    groupField: 'GenusCode'
                ),
            ],
        ];
    }

    private function buildShopRetailTotal(string $title, int $year, int $week): array
    {
        return [
            'name' => $title,
            'categories' => [
                $this->buildCategory(
                    categoryName: 'APPAREL',
                    codeLabel: 'Item Group Code',
                    familyCode: self::FAMILY_TEXTILE,
                    year: $year,
                    week: $week,
                    scope: [
                        'mode' => 'retail_total',
                    ],
                    groupField: 'ItemGroupCode'
                ),
                $this->buildCategory(
                    categoryName: 'FOOTWEAR',
                    codeLabel: 'Genus Code',
                    familyCode: self::FAMILY_FOOTWEAR,
                    year: $year,
                    week: $week,
                    scope: [
                        'mode' => 'retail_total',
                    ],
                    groupField: 'GenusCode'
                ),
            ],
        ];
    }

    private function buildCategory(
        string $categoryName,
        string $codeLabel,
        string $familyCode,
        int $year,
        int $week,
        array $scope,
        string $groupField // <-- ItemGroupCode ou GenusCode
    ): array {
        $items = $this->fetchGroupDataNAndN1($familyCode, $year, $week, $scope, $groupField);

        $totalN = 0.0;
        $totalN1 = 0.0;

        foreach ($items as &$it) {
            $totalN += $it['amount_n'];
            $totalN1 += $it['amount_n1'];

            // évolution = variation(N vs N-1)
            $it['evolution'] = $this->helpers->variation($it['amount_n'], $it['amount_n1']);
        }
        unset($it);

        $totalEvolution = $this->helpers->variation($totalN, $totalN1);

        // LW = total semaine-1 (année N)
        $lw = $this->fetchFamilyTotalForWeek($familyCode, $year, max(1, $week - 1), $scope);

        return [
            'name' => $categoryName,
            'code_label' => $codeLabel,

            'items' => array_map(fn(array $x) => [
                'code' => $x['code'],
                'amount_n' => (float)$x['amount_n'],
                'amount_n1' => (float)$x['amount_n1'],
                'evolution' => $x['evolution'] === null ? 'Infini' : (float)$x['evolution'],
            ], $items),

            'total' => [
                'amount_n' => (float)$totalN,
                'amount_n1' => (float)$totalN1,
                'evolution' => (float)$totalEvolution,
            ],

            'lw' => (float)$lw,
        ];
    }

    /* =========================================================
       SQL
    ========================================================= */

    private function baseJoFilterSql(): string
    {
        return "
            AND (
                co.RoyaltieCode IS NULL
                OR co.RoyaltieCode NOT IN ('EFRO', 'EFRO 25/26', 'EFRP', 'EFRP 25/26', 'P2024')
            )
        ";
    }

    /**
     * Bloc principal :
     * - APPAREL => co.ItemGroupCode
     * - FOOTWEAR => co.GenusCode
     * avec N / N-1 (en €)
     */
    private function fetchGroupDataNAndN1(string $familyCode, int $year, int $week, array $scope, string $groupField): array
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
            LEFT JOIN [BI].[DWH].D_Location   l  ON i.LocationCode = l.Code
            LEFT JOIN [BI].[DWH].D_Item       it ON i.ItemNo = it.ItemNo
            LEFT JOIN [BI].[DWH].D_Collection co ON i.ItemNo = co.Code AND i.SeriesNo = co.SeasonCode
            LEFT JOIN [BI].[DWH].D_Customer   c  ON i.CustomerNo = c.Code AND i.CompanyCode = c.CompanyCode
            WHERE 1=1
                AND YEAR(i.ExpectedInvoicingDate) IN (:y, :y1)
                AND DATEPART(ISO_WEEK, i.ExpectedInvoicingDate) = :week
                AND it.ItemFamilyCode = :family
                AND co.$groupField IS NOT NULL
                {$this->baseJoFilterSql()}
        ";

        $params = [
            'y' => $year,
            'y1' => $year - 1,
            'week' => $week,
            'family' => $familyCode,
        ];

        // SCOPE
        if (($scope['mode'] ?? '') === 'cs_store') {
            $sql .= " AND l.BusinessType IN ('CS') AND l.Code = :storeCode ";
            $params['storeCode'] = $scope['storeCode'];
        }

        if (($scope['mode'] ?? '') === 'retail_total') {
            $sql .= " AND c.ReportingDimensionDescription = 'RETAIL' ";
        }

        $sql .= "
            GROUP BY co.$groupField
            HAVING SUM(CASE WHEN YEAR(i.ExpectedInvoicingDate) = :y THEN i.AmountEurTM ELSE 0 END) <> 0
            ORDER BY co.$groupField ASC
        ";

        $rows = $this->mssqlLcs->executeQueryWithParams($sql, $params);

        $out = [];
        foreach ($rows as $r) {
            $code = (string)($r->grp ?? '');
            if ($code === '') {
                continue;
            }

            $out[] = [
                'code' => $code,
                'amount_n' => (float)($r->n ?? 0),
                'amount_n1' => (float)($r->n1 ?? 0),
            ];
        }

        return $out;
    }

    /**
     * LW = total famille (en €) sur semaine X (année N)
     */
    private function fetchFamilyTotalForWeek(string $familyCode, int $year, int $week, array $scope): float
    {
        $sql = "
            SELECT
                SUM(i.AmountEurTM) AS total_eur
            FROM [BI].[DWH].[F_Invoices] i
            LEFT JOIN [BI].[DWH].D_Location   l  ON i.LocationCode = l.Code
            LEFT JOIN [BI].[DWH].D_Item       it ON i.ItemNo = it.ItemNo
            LEFT JOIN [BI].[DWH].D_Collection co ON i.ItemNo = co.Code AND i.SeriesNo = co.SeasonCode
            LEFT JOIN [BI].[DWH].D_Customer   c  ON i.CustomerNo = c.Code AND i.CompanyCode = c.CompanyCode
            WHERE 1=1
                AND YEAR(i.ExpectedInvoicingDate) = :year
                AND DATEPART(ISO_WEEK, i.ExpectedInvoicingDate) = :week
                AND it.ItemFamilyCode = :family
                {$this->baseJoFilterSql()}
        ";

        $params = [
            'year' => $year,
            'week' => $week,
            'family' => $familyCode,
        ];

        // SCOPE
        if (($scope['mode'] ?? '') === 'cs_store') {
            $sql .= " AND l.BusinessType IN ('CS') AND l.Code = :storeCode ";
            $params['storeCode'] = $scope['storeCode'];
        }

        if (($scope['mode'] ?? '') === 'retail_total') {
            $sql .= " AND c.ReportingDimensionDescription = 'RETAIL' ";
        }

        $rows = $this->mssqlLcs->executeQueryWithParams($sql, $params);

        return isset($rows[0]->total_eur) ? (float)$rows[0]->total_eur : 0.0;
    }
}
