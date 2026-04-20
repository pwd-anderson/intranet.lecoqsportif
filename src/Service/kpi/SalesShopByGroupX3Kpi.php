<?php

namespace App\Service\kpi;

use App\Factory\MssqlManagerFactory;
use App\Service\Tools\CollectionSorter;
use App\Service\Tools\Helpers;
use App\Service\Tools\MssqlManager;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class SalesShopByGroupX3Kpi
{
    private MssqlManager $mssqlLcs;

    // familles
    private const FAMILY_TEXTILE  = 'APL';
    private const FAMILY_FOOTWEAR = 'FTW';

    public function __construct(
        private MssqlManagerFactory $mssqlManagerFactory,
        private Helpers $helpers,
        #[Autowire('%db.lcs%')]
        string $dbLcs,
        #[Autowire('%db.lcs_sei%')]
        string $dbLcsSei,
    ) {
        $this->mssqlLcs = $this->mssqlManagerFactory->create($dbLcsSei);
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
                    groupField: 'TSICOD_1'
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
                    groupField: 'TSICOD_0'
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
                    groupField: 'TSICOD_1'
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
                    groupField: 'TSICOD_0'
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
        string $groupField
    ): array {
        $items = $this->fetchGroupDataNAndN1($familyCode, $year, $week, $scope, $groupField);

        $totalN = 0.0;
        $totalN1 = 0.0;

        foreach ($items as $k => $it) {
            $totalN  += $it['amount_n'];
            $totalN1 += $it['amount_n1'];

            $items[$k]['evolution'] = $this->helpers->variation(
                $it['amount_n'],
                $it['amount_n1']
            );
        }

        // TRI DÉCROISSANT SUR LE CA N
        CollectionSorter::sortDescByKey($items, 'amount_n');

        $totalEvolution = $this->helpers->variation($totalN, $totalN1);

        // LW = total semaine-1 (année N)
        $lw = $this->fetchFamilyTotalForWeek($familyCode, $year, max(1, $week - 1), $scope);

        return [
            'name' => $categoryName,
            'code_label' => $codeLabel,

            'items' => array_map(static fn(array $x) => [
                'code' => $x['code'],
                'amount_n' => (float) $x['amount_n'],
                'amount_n1' => (float) $x['amount_n1'],
                'evolution' => $x['evolution'] === null ? 'Infini' : (float) $x['evolution'],
            ], $items),

            'total' => [
                'amount_n' => (float) $totalN,
                'amount_n1' => (float) $totalN1,
                'evolution' => (float) $totalEvolution,
            ],

            'lw' => (float) $lw,
        ];
    }

    /* =========================================================
       SQL
    ========================================================= */

    private function baseJoFilterSql(): string
    {
        // garde les NULL + exclut la liste
        return " AND (ZMO.ZMODDES_0 NOT LIKE 'EFRO%' AND ZMO.ZMODDES_0 NOT LIKE 'EFRP%') ";
    }

    /**
     * Bloc principal :
     * - APPAREL => co.ItemGroupCode
     * - FOOTWEAR => co.GenusCode
     * avec N / N-1 (en €)
     */
    private function fetchGroupDataNAndN1(
        string $familyCode,
        int $year,
        int $week,
        array $scope,
        string $groupField
    ): array {
        // Sécurité : champ dynamique autorisé uniquement
        $allowed = ['TSICOD_1', 'TSICOD_0'];
        if (!in_array($groupField, $allowed, true)) {
            $groupField = 'TSICOD_1';
        }

        $yearN  = $year;
        $yearN1 = $year - 1;

        $sql = "
        SELECT
            ITM.$groupField AS grp,
            SUM(CASE WHEN YEAR(I.DOCUMENTPOSTINGDATE) = {$yearN}  THEN I.AMOUNTEURTM ELSE 0 END) AS n,
            SUM(CASE WHEN YEAR(I.DOCUMENTPOSTINGDATE) = {$yearN1} THEN I.AMOUNTEURTM ELSE 0 END) AS n1
        FROM SEI_X3_LCS.CONSO_INVOICES I
        LEFT JOIN X3_LCS.ITMMASTER ITM ON I.ITEMNO = ITM.ITMREF_0
        LEFT JOIN X3_LCS.ZMODELE ZMO ON ITM.ZMODELCOD_0 = ZMO.ZMODCOD_0
        LEFT JOIN X3_LCS.BPCUSTOMER BPC ON I.CUSTOMERNO = BPC.BPCNUM_0
        LEFT JOIN X3_LCS.ATEXTRA ATX ON ATX.IDENT2_0 = BPC.TSCCOD_3 AND ATX.CODFIC_0 = 'ATABDIV' AND ATX.LANGUE_0 = 'FRA' AND ATX.ZONE_0 = 'LNGDES' AND ATX.IDENT1_0 = '33'
        LEFT JOIN X3_LCS.ATEXTRA ATX2 ON ATX.IDENT2_0 = BPC.TSCCOD_2 AND ATX2.CODFIC_0 = 'ATABDIV' AND ATX2.LANGUE_0 = 'FRA' AND ATX2.ZONE_0 = 'LNGDES' AND ATX2.IDENT1_0 = '32'
        WHERE
            YEAR(I.DOCUMENTPOSTINGDATE) IN ({$yearN}, {$yearN1})
            AND DATEPART(ISO_WEEK, I.DOCUMENTPOSTINGDATE) = {$week}
            {$this->baseJoFilterSql()}
            AND ITM.TCLCOD_0 = '{$familyCode}'
            AND ITM.$groupField IS NOT NULL
    ";

        // 🔹 Scope CS boutique
        if (($scope['mode'] ?? '') === 'cs_store') {
            $storeCode = $scope['storeCode'] ?? '';
            $sql .= "
            AND ATX.TEXTE_0 = 'CONCEPT STORE'
            AND I.LOCATIONCODE = '{$storeCode}'
        ";
        }

        // 🔹 Scope retail total
        if (($scope['mode'] ?? '') === 'retail_total') {
            $sql .= "
            AND ATX2.TEXTE_0 = 'RETAIL'
        ";
        }

        $sql .= "
        GROUP BY ITM.$groupField
        HAVING
            SUM(CASE WHEN YEAR(I.DOCUMENTPOSTINGDATE) = {$yearN} THEN I.AMOUNTEURTM ELSE 0 END) <> 0
        ORDER BY ITM.$groupField ASC
    ";
        $rows = $this->mssqlLcs->executeQuery($sql);

        $out = [];
        foreach ($rows as $r) {
            $code = (string) ($r->grp ?? '');
            if ($code === '') {
                continue;
            }

            $out[] = [
                'code'       => $code,
                'amount_n'   => (float) ($r->n ?? 0),
                'amount_n1'  => (float) ($r->n1 ?? 0),
            ];
        }

        return $out;
    }

    /**
     * LW = total famille (en €) sur semaine X (année N)
     */
    private function fetchFamilyTotalForWeek(
        string $familyCode,
        int $year,
        int $week,
        array $scope
    ): float {
        $sql = "
        SELECT
            SUM(I.AMOUNTEURTM) AS total_eur
        FROM SEI_X3_LCS.CONSO_INVOICES I
        LEFT JOIN X3_LCS.ITMMASTER ITM ON I.ITEMNO = ITM.ITMREF_0
        LEFT JOIN X3_LCS.ZMODELE ZMO ON ITM.ZMODELCOD_0 = ZMO.ZMODCOD_0
        LEFT JOIN X3_LCS.BPCUSTOMER BPC ON I.CUSTOMERNO = BPC.BPCNUM_0
        LEFT JOIN X3_LCS.ATEXTRA ATX ON ATX.IDENT2_0 = BPC.TSCCOD_3 AND ATX.CODFIC_0 = 'ATABDIV' AND ATX.LANGUE_0 = 'FRA' AND ATX.ZONE_0 = 'LNGDES' AND ATX.IDENT1_0 = '33'
        LEFT JOIN X3_LCS.ATEXTRA ATX2 ON ATX.IDENT2_0 = BPC.TSCCOD_2 AND ATX2.CODFIC_0 = 'ATABDIV' AND ATX2.LANGUE_0 = 'FRA' AND ATX2.ZONE_0 = 'LNGDES' AND ATX2.IDENT1_0 = '32'
        WHERE
            YEAR(I.DOCUMENTPOSTINGDATE) = {$year}
            AND DATEPART(ISO_WEEK, I.DOCUMENTPOSTINGDATE) = {$week}
            AND ITM.TCLCOD_0 = '{$familyCode}'
            {$this->baseJoFilterSql()}
    ";

        // 🔹 Scope CS boutique
        if (($scope['mode'] ?? '') === 'cs_store') {
            $storeCode = $scope['storeCode'] ?? '';
            $sql .= "
            AND ATX.TEXTE_0 = 'CONCEPT STORE'
            AND I.LOCATIONCODE = '{$storeCode}'
        ";
        }

        // 🔹 Scope retail total
        if (($scope['mode'] ?? '') === 'retail_total') {
            $sql .= "
            AND ATX2.TEXTE_0 = 'RETAIL'
        ";
        }

        $rows = $this->mssqlLcs->executeQuery($sql);

        return isset($rows[0]->total_eur)
            ? (float) $rows[0]->total_eur
            : 0.0;
    }
}
