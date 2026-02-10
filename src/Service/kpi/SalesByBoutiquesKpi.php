<?php

namespace App\Service\kpi;

use App\Factory\MssqlManagerFactory;
use App\Repository\KpiDeckPresentationRepository;
use App\Service\Tools\CollectionSorter;
use App\Service\Tools\GraphMailer;
use App\Service\Tools\Helpers;
use App\Service\Tools\MssqlManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class SalesByBoutiquesKpi
{
    private MssqlManager $mssqlLcs;

    // ⚠️ À ADAPTER selon ta table RFC_AVEC_MARGE
    private string $rfcCaColumn = 'RFC WEEK';
    private string $rfcMarginColumn = 'RFC MARGE'; // <-- change si besoin

    public function __construct(
        private KpiDeckPresentationRepository $kpiDeckPresentationRepository,
        private RfcByBoutique $rfcByBoutique,
        private MssqlManagerFactory $mssqlManagerFactory,
        private LoggerInterface $logger,
        private GraphMailer $graphMailer,
        private Helpers $helpers,
        #[Autowire('%db.lcs%')]
        string $dbLcs,
    ) {
        $this->mssqlLcs = $this->mssqlManagerFactory->create($dbLcs);
    }

    public function getSalesByBoutique(int $year, int $week, string $businessType = 'CS'): array
    {
        return [
            'week'  => $this->buildPeriod($year, $week, false, $businessType),
            'cumul' => $this->buildPeriod($year, $week, true, $businessType),
        ];
    }

    private function buildPeriod(int $year, int $week, bool $cumul, string $businessType = 'CS'): array
    {
        /**
         * 1) BOUTIQUES (CS périmètre constant)
         * ================================
         */
        $rowsBoutiques = $this->fetchBoutiquesData($year, $week, $cumul, $businessType);

        $boutiques = [];
        foreach ($rowsBoutiques as $row) {
            $boutiques[] = $this->buildLineFromRow($row);
        }

        /**
         * 2) CS PÉRIMÈTRE CONSTANT
         * ================================
         * → somme des boutiques affichées
         */
        $csPerimetre = $this->initLine($businessType .' PERIMETRE CONSTANT');
        foreach ($boutiques as $b) {
            $this->addToTotal($csPerimetre, $b);
        }
        $this->finalizeLine($csPerimetre);

        /**
         * 3) CONCEPT STORE (ENTÊTE)
         * ================================
         * → requête AGRÉGÉE (pas somme des boutiques)
         */
        $rowConcept = $this->fetchConceptStoreTotal($year, $week, $cumul, $businessType);
        $label = $businessType === 'CS'
            ? 'CONCEPT STORE'
            : 'FACTORY OUTLET';

        $concept = $this->initLine($label);

        if ($rowConcept !== null) {
            $concept['ca']['reel'] = $this->toK($rowConcept->Amount_N ?? 0);
            $concept['ca']['ly']   = $this->toK($rowConcept->Amount_N1 ?? 0);

            $concept['marge']['reel'] = $this->toK($rowConcept->Margin_N ?? 0);
            $concept['marge']['ly']   = $this->toK($rowConcept->Margin_N1 ?? 0);

            $this->finalizeLine($concept);
        }

        /**
         * 4) POIDS DES BOUTIQUES
         * ================================
         * → % du Concept Store
         */
        $this->applyPoidsOnBoutiques($boutiques, $concept['ca']['reel']);

        /**
         * 4bis) RFC PAR BOUTIQUE
         * ================================
         */
        $rfcByBoutique = $this->rfcByBoutique->getRfcByStore($year, $week, $cumul);

        foreach ($boutiques as &$b) {
            $code = $b['code'] ?? null; // on va corriger ça juste après

            if (!$code || !isset($rfcByBoutique[$code])) {
                continue;
            }

            $rfc = $rfcByBoutique[$code];

            // CA
            if (!empty($rfc['ca'])) {
                $rfcCa = round((float)$rfc['ca']);

                $b['ca']['rfc'] = $rfcCa;
                $b['ca']['ecart_bud'] = $this->helpers->variation(
                    (float)$b['ca']['reel'],
                    (float)$rfcCa        // ✅ même unité, même arrondi
                );
            }

            // MARGE
            if (!empty($rfc['marge'])) {
                $rfcMarge = round((float)$rfc['marge']);

                $b['marge']['rfc'] = $rfcMarge;
                $b['marge']['ecart_bud'] = $this->helpers->variation(
                    (float)$b['marge']['reel'],
                    (float)$rfcMarge
                );
            }
        }
        unset($b);

        /**
         * 5) TOTAL RETAIL (ENTÊTE)
         * ================================
         * → ReportingDimensionDescription = RETAIL
         * → CS + FACTORY OUTLET
         */
        $rowTotalRetail = $this->fetchTotalRetailData($year, $week, $cumul);
        $total = $this->initLine('TOTAL RETAIL');

        if ($rowTotalRetail !== null) {
            $total['ca']['reel'] = $this->toK($rowTotalRetail->Amount_N ?? 0);
            $total['ca']['ly']   = $this->toK($rowTotalRetail->Amount_N1 ?? 0);

            $total['marge']['reel'] = $this->toK($rowTotalRetail->Margin_N ?? 0);
            $total['marge']['ly']   = $this->toK($rowTotalRetail->Margin_N1 ?? 0);

            $this->finalizeLine($total);
        }

        /**
         * 6) RFC / ECART BUD
         * ================================
         */

// RFC CONCEPT STORE (agrégé)
        $rfcConcept = $this->rfcByBoutique->getRfcAggregate(
            $year,
            $week,
            $cumul,
            ['CONCEPT STORE']
        );

// CA
        $concept['ca']['rfc'] = round($rfcConcept['ca']);
        $concept['ca']['ecart_bud'] = $this->helpers->variation(
            (float) $concept['ca']['reel'],
            (float) $rfcConcept['ca']
        );

// MARGE
        $concept['marge']['rfc'] = round($rfcConcept['marge']);
        $concept['marge']['ecart_bud'] = $this->helpers->variation(
            (float) $concept['marge']['reel'],
            (float) $rfcConcept['marge']
        );


// RFC TOTAL RETAIL (agrégé)
        $rfcTotal = $this->rfcByBoutique->getRfcAggregate(
            $year,
            $week,
            $cumul,
            ['CONCEPT STORE', 'FACTORY OUTLET', 'MARKETPLACE', 'ESHOP']
        );

// CA
        $total['ca']['rfc'] = round($rfcTotal['ca']);
        $total['ca']['ecart_bud'] = $this->helpers->variation(
            (float) $total['ca']['reel'],
            (float) $rfcTotal['ca']
        );

// MARGE
        $total['marge']['rfc'] = round($rfcTotal['marge']);
        $total['marge']['ecart_bud'] = $this->helpers->variation(
            (float) $total['marge']['reel'],
            (float) $rfcTotal['marge']
        );

        CollectionSorter::sortDescByPath($boutiques, ['ca', 'reel']);

        /**
         * 7) RETOUR FINAL
         * ================================
         */
        return [
            'total_retail'  => $total,
            'concept_store' => $concept,
            'boutiques'     => $boutiques,
            'cs_perimetre'  => $csPerimetre,
        ];
    }

    /**
     * Données boutique : CS + RETAIL (liste)
     */
    private function fetchBoutiquesData(int $year, int $week, bool $cumul, string $businessType = 'CS'): array
    {
        $sql = "
            SELECT
                l.BusinessType,
                l.Code,
                l.StoreDescription,

                SUM(CASE WHEN YEAR(i.ExpectedInvoicingDate) = :year THEN i.AmountEurTM ELSE 0 END) AS Amount_N,
                SUM(CASE WHEN YEAR(i.ExpectedInvoicingDate) = :year_minus_1 THEN i.AmountEurTM ELSE 0 END) AS Amount_N1,

                SUM(CASE WHEN YEAR(i.ExpectedInvoicingDate) = :year THEN i.Margin_AmountEurTM ELSE 0 END) AS Margin_N,
                SUM(CASE WHEN YEAR(i.ExpectedInvoicingDate) = :year_minus_1 THEN i.Margin_AmountEurTM ELSE 0 END) AS Margin_N1

            FROM [BI].[DWH].[F_Invoices] i
            LEFT JOIN [BI].[DWH].D_Location l ON i.LocationCode = l.Code
            LEFT JOIN [BI].[DWH].D_Customer c ON i.CustomerNo = c.Code AND i.CompanyCode = c.CompanyCode

            WHERE
                l.BusinessType = '$businessType'
                AND c.ReportingDimensionDescription = 'RETAIL'
                AND (
        CASE
            WHEN DATEPART(ISO_WEEK, i.ExpectedInvoicingDate) = 1 AND MONTH(i.ExpectedInvoicingDate) = 12 THEN YEAR(i.ExpectedInvoicingDate) + 1
            WHEN DATEPART(ISO_WEEK, i.ExpectedInvoicingDate) >= 52 AND MONTH(i.ExpectedInvoicingDate) = 1 THEN YEAR(i.ExpectedInvoicingDate) - 1
            ELSE YEAR(i.ExpectedInvoicingDate)
        END
    ) IN (:year, :year_minus_1)
        ";

        $params = [
            'year' => $year,
            'year_minus_1' => $year - 1,
            'week' => $week,
        ];

        if ($cumul) {
            $sql .= " AND DATEPART(ISO_WEEK, i.ExpectedInvoicingDate) <= :week ";
        } else {
            $sql .= " AND DATEPART(ISO_WEEK, i.ExpectedInvoicingDate) = :week ";
        }

        $sql .= "
            GROUP BY l.BusinessType, l.Code, l.StoreDescription
            HAVING SUM(CASE WHEN YEAR(i.ExpectedInvoicingDate) = :year THEN i.AmountEurTM ELSE 0 END) <> 0
            ORDER BY l.Code ASC
        ";
        return $this->mssqlLcs->executeQueryWithParams($sql, $params);
    }

    /**
     * TOTAL RETAIL : uniquement RETAIL (pas de filtre CS)
     * -> on agrège tout en une ligne
     */
    private function fetchTotalRetailData(int $year, int $week, bool $cumul): ?object
    {
        $sql = "
            SELECT
                SUM(CASE WHEN YEAR(i.ExpectedInvoicingDate) = :year THEN i.AmountEurTM ELSE 0 END) AS Amount_N,
                SUM(CASE WHEN YEAR(i.ExpectedInvoicingDate) = :year_minus_1 THEN i.AmountEurTM ELSE 0 END) AS Amount_N1,

                SUM(CASE WHEN YEAR(i.ExpectedInvoicingDate) = :year THEN i.Margin_AmountEurTM ELSE 0 END) AS Margin_N,
                SUM(CASE WHEN YEAR(i.ExpectedInvoicingDate) = :year_minus_1 THEN i.Margin_AmountEurTM ELSE 0 END) AS Margin_N1

            FROM [BI].[DWH].[F_Invoices] i
            LEFT JOIN [BI].[DWH].D_Customer c ON i.CustomerNo = c.Code AND i.CompanyCode = c.CompanyCode
            LEFT JOIN [BI].[DWH].D_Location l on i.LocationCode = l.Code

            WHERE
                c.ReportingDimensionDescription = 'RETAIL'
                AND l.BusinessType <> ''
                AND (
        CASE
            WHEN DATEPART(ISO_WEEK, i.ExpectedInvoicingDate) = 1 AND MONTH(i.ExpectedInvoicingDate) = 12 THEN YEAR(i.ExpectedInvoicingDate) + 1
            WHEN DATEPART(ISO_WEEK, i.ExpectedInvoicingDate) >= 52 AND MONTH(i.ExpectedInvoicingDate) = 1 THEN YEAR(i.ExpectedInvoicingDate) - 1
            ELSE YEAR(i.ExpectedInvoicingDate)
        END
    ) IN (:year, :year_minus_1)
        ";

        $params = [
            'year' => $year,
            'year_minus_1' => $year - 1,
            'week' => $week,
        ];

        if ($cumul) {
            $sql .= " AND DATEPART(ISO_WEEK, i.ExpectedInvoicingDate) <= :week ";
        } else {
            $sql .= " AND DATEPART(ISO_WEEK, i.ExpectedInvoicingDate) = :week ";
        }
        $sql .= " HAVING
                    SUM(CASE WHEN YEAR(i.ExpectedInvoicingDate) = :year THEN i.AmountEurTM ELSE 0 END) <> 0 ";

        $rows = $this->mssqlLcs->executeQueryWithParams($sql, $params);
        return $rows[0] ?? null;
    }

    private function fetchConceptStoreTotal(int $year, int $week, bool $cumul, string $businessType = 'CS'): object
    {
        $sql = "
        SELECT
            SUM(CASE WHEN YEAR(i.ExpectedInvoicingDate) = :year THEN i.AmountEurTM ELSE 0 END) AS Amount_N,
            SUM(CASE WHEN YEAR(i.ExpectedInvoicingDate) = :year_minus_1 THEN i.AmountEurTM ELSE 0 END) AS Amount_N1,
            SUM(CASE WHEN YEAR(i.ExpectedInvoicingDate) = :year THEN i.Margin_AmountEurTM ELSE 0 END) AS Margin_N,
            SUM(CASE WHEN YEAR(i.ExpectedInvoicingDate) = :year_minus_1 THEN i.Margin_AmountEurTM ELSE 0 END) AS Margin_N1
        FROM [BI].[DWH].[F_Invoices] i
        LEFT JOIN [BI].[DWH].D_Location l ON i.LocationCode = l.Code
        LEFT JOIN [BI].[DWH].D_Customer c
            ON i.CustomerNo = c.Code
           AND i.CompanyCode = c.CompanyCode
        WHERE
            l.BusinessType = '$businessType'
            AND c.ReportingDimensionDescription = 'RETAIL'
            AND (
        CASE
            WHEN DATEPART(ISO_WEEK, i.ExpectedInvoicingDate) = 1 AND MONTH(i.ExpectedInvoicingDate) = 12 THEN YEAR(i.ExpectedInvoicingDate) + 1
            WHEN DATEPART(ISO_WEEK, i.ExpectedInvoicingDate) >= 52 AND MONTH(i.ExpectedInvoicingDate) = 1 THEN YEAR(i.ExpectedInvoicingDate) - 1
            ELSE YEAR(i.ExpectedInvoicingDate)
        END
    ) IN (:year, :year_minus_1)
    ";

        $params = [
            'year' => $year,
            'year_minus_1' => $year - 1,
        ];

        if ($cumul) {
            $sql .= " AND DATEPART(ISO_WEEK, i.ExpectedInvoicingDate) <= :week ";
        } else {
            $sql .= " AND DATEPART(ISO_WEEK, i.ExpectedInvoicingDate) = :week ";
        }

        $params['week'] = $week;

        return $this->mssqlLcs->executeQueryWithParams($sql, $params)[0];
    }

    private function buildLineFromRow(object $r): array
    {
        // Conversion K€
        $caN  = $this->toK($r->Amount_N ?? 0);
        $caN1 = $this->toK($r->Amount_N1 ?? 0);

        $mN  = $this->toK($r->Margin_N ?? 0);
        $mN1 = $this->toK($r->Margin_N1 ?? 0);

        $caEcart = $caN - $caN1;

        $caEcartPercent = $this->helpers->variation($caN, $caN1);

        $tauxN  = $caN > 0 ? ($mN / $caN) * 100 : 0;
        $tauxN1 = $caN1 > 0 ? ($mN1 / $caN1) * 100 : 0;

        return [
            'code' => (string)($r->Code ?? ''),
            'name' => $r->StoreDescription ?? '',
            'type' => $this->resolveType((string)($r->Code ?? '')),

            'ca' => [
                'reel' => round($caN),
                'poids' => '', // rempli après (applyPoidsOnBoutiques)
                'rfc' => null,
                'ecart_bud' => null,
                'ly' => round($caN1),
                'ecart' => round($caEcart),
                'ecart_percent' => round($caEcartPercent, 1),
            ],

            'marge' => [
                'reel' => round($mN),
                'rfc' => null,
                'ecart_bud' => null,
                'ly' => round($mN1),
                'ecart' => round($mN - $mN1),
                'ecart_percent' => round($this->helpers->variation($mN, $mN1), 1),
            ],

            'taux' => [
                'reel' => round($tauxN, 1),
                'rfc' => 68, // fictif
                'ecart_bud' => round($tauxN - 68, 1),
                'ly' => round($tauxN1, 1),
                'ecart_n1' => round($tauxN - $tauxN1, 1),
            ],
        ];
    }

    private function applyPoidsOnBoutiques(array &$boutiques, float $conceptTotalCaReel): void
    {
        if ($conceptTotalCaReel <= 0) {
            return;
        }

        foreach ($boutiques as &$b) {
            $val = (float)($b['ca']['reel'] ?? 0);
            $pct = ($val * 100) / $conceptTotalCaReel;
            $b['ca']['poids'] = round($pct, 1) . '%';
        }
    }

    private function applyRfcOnLine(array &$line, float $rfcCaK, float $rfcMarginK): void
    {
        // CA RFC
        if ($rfcCaK > 0) {
            $line['ca']['rfc'] = round($rfcCaK);
            $line['ca']['ecart_bud'] = $this->helpers->variation((float)$line['ca']['reel'], $rfcCaK);
        } else {
            $line['ca']['rfc'] = null;
            $line['ca']['ecart_bud'] = null;
        }

        // MARGE RFC (si dispo)
        if ($rfcMarginK > 0) {
            $line['marge']['rfc'] = round($rfcMarginK);
            $line['marge']['ecart_bud'] = $this->helpers->variation((float)$line['marge']['reel'], $rfcMarginK);
        } else {
            $line['marge']['rfc'] = null;
            $line['marge']['ecart_bud'] = null;
        }
    }

    private function initLine(string $name): array
    {
        return [
            'name' => $name,
            'type' => '',

            'ca' => [
                'reel' => 0,
                'poids' => '',
                'rfc' => null,
                'ecart_bud' => null,
                'ly' => 0,
                'ecart' => 0,
                'ecart_percent' => 0,
            ],

            'marge' => [
                'reel' => 0,
                'rfc' => null,
                'ecart_bud' => null,
                'ly' => 0,
                'ecart' => 0,
                'ecart_percent' => 0,
            ],

            'taux' => [
                'reel' => 0,
                'rfc' => 68, // fictif
                'ecart_bud' => 0,
                'ly' => 0,
                'ecart_n1' => 0,
            ],
        ];
    }

    private function addToTotal(array &$total, array $line): void
    {
        $total['ca']['reel'] += (float)$line['ca']['reel'];
        $total['ca']['ly']   += (float)$line['ca']['ly'];

        $total['marge']['reel'] += (float)$line['marge']['reel'];
        $total['marge']['ly']   += (float)$line['marge']['ly'];
    }

    private function finalizeLine(array &$line): void
    {
        // 🔒 Arrondi des montants (K€)
        $line['ca']['reel'] = round($line['ca']['reel']);
        $line['ca']['ly']   = round($line['ca']['ly']);

        $line['marge']['reel'] = round($line['marge']['reel']);
        $line['marge']['ly']   = round($line['marge']['ly']);

        // CA
        $line['ca']['ecart'] = $line['ca']['reel'] - $line['ca']['ly'];
        $line['ca']['ecart_percent'] =
            $this->helpers->variation($line['ca']['reel'], $line['ca']['ly']);

        // Marge
        $line['marge']['ecart'] = $line['marge']['reel'] - $line['marge']['ly'];
        $line['marge']['ecart_percent'] =
            $this->helpers->variation($line['marge']['reel'], $line['marge']['ly']);

        // Taux
        $tauxN  = $line['ca']['reel'] > 0
            ? ($line['marge']['reel'] / $line['ca']['reel']) * 100
            : 0;

        $tauxN1 = $line['ca']['ly'] > 0
            ? ($line['marge']['ly'] / $line['ca']['ly']) * 100
            : 0;

        $line['taux']['reel'] = round($tauxN, 1);
        $line['taux']['ly']   = round($tauxN1, 1);

        $line['taux']['rfc'] = $line['taux']['rfc'] ?? 68;
        $line['taux']['ecart_bud'] = round($line['taux']['reel'] - $line['taux']['rfc'], 1);
        $line['taux']['ecart_n1']  = round($line['taux']['reel'] - $line['taux']['ly'], 1);
    }

    private function buildCsPerimetre(array $boutiques): array
    {
        $line = $this->initLine('CS PERIMETRE CONSTANT');

        foreach ($boutiques as $b) {
            $this->addToTotal($line, $b);
        }

        $this->finalizeLine($line);

        return $line;
    }

    private function resolveType(string $code): string
    {
        // Adapte si tu as une vraie règle.
        // Ici : INT pour STGER + CITADIUM, sinon AFF
        $intCodes = ['CSFR-STGER', 'CITADIUM'];

        return in_array($code, $intCodes, true) ? 'INT' : 'AFF';
    }

    private function toK(float $eur): float
    {
        return $eur / 1000.0;
    }
}
