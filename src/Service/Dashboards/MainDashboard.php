<?php

namespace App\Service\Dashboards;

use App\Factory\MssqlManagerFactory;
use App\Service\Divers;
use App\Service\Tools\GraphMailer;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Psr\Log\LoggerInterface;
use App\Service\Tools\MssqlManager;

class MainDashboard
{
    private MssqlManager $mssqlMade2design;
    private MssqlManager $mssqlLcs;
    private bool $isDev;

    public function __construct(
        private MssqlManagerFactory $mssqlManagerFactory,
        private LoggerInterface $logger,
        private GraphMailer $graphMailer,
        private Divers $divers,
        #[Autowire('%db.lcs_sei%')]
        string $dbLcsSei,
        #[Autowire('%db.lcs%')]
        string $dbLcs,
        #[Autowire('%kernel.environment%')]
        string $environment,
    ) {
        $this->mssqlMade2design = $this->mssqlManagerFactory->create($dbLcsSei);
        $this->mssqlLcs         = $this->mssqlManagerFactory->create($dbLcs);
        $this->isDev = ($environment === 'dev');
    }

    private function table(string $name): string
    {
        return 'MASTER_TABLES.' . $name . ($this->isDev ? '_DEV' : '');
    }

    private function normalizeNetworkFilter(?string $network): string
    {
        return in_array($network, ['global', 'boutique', 'ecom', 'wholesale_fr', 'wholesale_eu', 'wholesale_int'], true)
            ? $network
            : 'global';
    }

    private function buildNetworkWhereClause(
        string $network,
        string $mainNetworkCol    = 'mainnetwork',
        string $reportingDimCol   = 'reportingdimension'
    ): string {
        return match ($this->normalizeNetworkFilter($network)) {
            'boutique'      => " AND {$mainNetworkCol} IN ('RETAIL FO', 'CLEARANCE', 'RETAIL CS', 'CONCEPT STORE', 'FACTORY OUTLET')",
            'ecom'          => " AND {$mainNetworkCol} IN ('RETAIL MARKET PLACE', 'RETAIL ESHOP', 'E BUSINESS DIRECT', 'E BUSINESS MARKET PLACE')",
            'wholesale_fr'  => " AND {$reportingDimCol} = 'WHOLESALE FRANCE'",
            'wholesale_eu'  => " AND {$reportingDimCol} = 'WHOLESALE EUROPE'",
            'wholesale_int' => " AND {$reportingDimCol} = 'WHOLESALE INTERNATIO'",
            default         => '',
        };
    }

    public function getSalesComparaisonYears(string $network = 'global'): array
    {
        try {
            $networkWhere = $this->buildNetworkWhereClause($network);
            $table        = $this->table('INTRANET_SALES_DAILY');

            $query = "SELECT
                    SUM(CASE WHEN annee = YEAR(GETDATE()) THEN ca ELSE 0 END) AS ca_n,

                    SUM(CASE WHEN annee = YEAR(GETDATE()) - 1 THEN ca ELSE 0 END) AS ca_n_1,

                    ROUND(
                        CASE
                            WHEN SUM(CASE WHEN annee = YEAR(GETDATE()) - 1 THEN ca ELSE 0 END) = 0
                                THEN NULL
                            ELSE
                                (
                                    SUM(CASE WHEN annee = YEAR(GETDATE()) THEN ca ELSE 0 END)
                                    -
                                    SUM(CASE WHEN annee = YEAR(GETDATE()) - 1 THEN ca ELSE 0 END)
                                )
                                /
                                SUM(CASE WHEN annee = YEAR(GETDATE()) - 1 THEN ca ELSE 0 END)
                                * 100
                        END
                    , 2) AS variation_pourcent

                FROM {$table}
                WHERE
                    (
                        (mois < MONTH(GETDATE()))
                        OR (mois = MONTH(GETDATE()) AND jour <= DAY(GETDATE()))
                    )
                    {$networkWhere};";

            return $this->mssqlMade2design->executeQuery($query);

        } catch (\Exception $e) {
            $this->graphMailer->notifyError('❌ OGIER Erreur Dashboard : Récupération de données Conversion Rate', $e);
            $this->logger->error('Error Récupération de données Conversion Rate', ['exception' => $e]);
            return [];
        }
    }

    public function getSalesComparaisonByMonths(string $network = 'global'): array
    {
        try {
            $networkWhere = $this->buildNetworkWhereClause($network);
            $table        = $this->table('INTRANET_SALES_DAILY');

            $query = "SELECT
                    mois,
                    SUM(CASE WHEN annee = YEAR(GETDATE()) THEN ca ELSE 0 END) AS ca_n,
                    SUM(CASE WHEN annee = YEAR(GETDATE())-1 THEN ca ELSE 0 END) AS ca_n_1
                FROM {$table}
                WHERE 1 = 1
                {$networkWhere}
                GROUP BY mois
                ORDER BY mois;";

            return $this->mssqlMade2design->executeQuery($query);

        } catch (\Exception $e) {
            $this->graphMailer->notifyError('❌ OGIER Erreur Dashboard : Récupération de données Conversion Rate', $e);
            $this->logger->error('Error Récupération de données Conversion Rate', ['exception' => $e]);
            return [];
        }
    }

    public function getSalesComparaisonCurrentMonthByDay(string $network = 'global'): array
    {
        try {
            $networkWhere = $this->buildNetworkWhereClause($network);
            $table        = $this->table('INTRANET_SALES_DAILY');

            $j_1      = new \DateTime('yesterday');
            $year     = (int) $j_1->format('Y');
            $month    = (int) $j_1->format('n');
            $day      = (int) $j_1->format('j');
            $year_n_1 = $year - 1;

            $query = "
            SELECT
                jour,
                SUM(CASE WHEN annee = {$year}     THEN ca ELSE 0 END) AS ca_n,
                SUM(CASE WHEN annee = {$year_n_1} THEN ca ELSE 0 END) AS ca_n_1
            FROM {$table}
            WHERE mois = {$month}
              AND jour <= {$day}
              AND annee IN ({$year}, {$year_n_1})
              {$networkWhere}
            GROUP BY jour
            ORDER BY jour;";

            return $this->mssqlMade2design->executeQuery($query);

        } catch (\Exception $e) {
            $this->graphMailer->notifyError('❌ OGIER Erreur Dashboard : CA MTD jour', $e);
            $this->logger->error('Erreur CA MTD jour', ['exception' => $e]);
            return [];
        }
    }

    public function getSalesComparaisonCurrentMonth(string $network = 'global'): array
    {
        try {
            $networkWhere = $this->buildNetworkWhereClause($network, 'd.mainnetwork', 'd.reportingdimension');
            $table        = $this->table('INTRANET_SALES_DAILY');

            $j_1     = (new \DateTime('yesterday'))->format('Y-m-d');
            $j_1_n_1 = (new \DateTime('yesterday -1 year'))->format('Y-m-d');

            $query = "
            WITH bornes AS (
                SELECT
                    DATEFROMPARTS(YEAR('{$j_1}'),     MONTH('{$j_1}'), 1) AS start_n,
                    CAST('{$j_1}' AS DATE)                                AS end_n,

                    DATEFROMPARTS(YEAR('{$j_1_n_1}'), MONTH('{$j_1_n_1}'), 1) AS start_n1,
                    CAST('{$j_1_n_1}' AS DATE)                               AS end_n1
            )

            SELECT
                SUM(CASE WHEN d.date BETWEEN b.start_n  AND b.end_n  THEN d.ca ELSE 0 END) AS ca_n,
                SUM(CASE WHEN d.date BETWEEN b.start_n1 AND b.end_n1 THEN d.ca ELSE 0 END) AS ca_n_1,

                ROUND(
                    CASE
                        WHEN SUM(CASE WHEN d.date BETWEEN b.start_n1 AND b.end_n1 THEN d.ca ELSE 0 END) = 0
                            THEN NULL
                        ELSE
                            (
                                SUM(CASE WHEN d.date BETWEEN b.start_n  AND b.end_n  THEN d.ca ELSE 0 END)
                                -
                                SUM(CASE WHEN d.date BETWEEN b.start_n1 AND b.end_n1 THEN d.ca ELSE 0 END)
                            )
                            /
                            SUM(CASE WHEN d.date BETWEEN b.start_n1 AND b.end_n1 THEN d.ca ELSE 0 END)
                            * 100
                    END
                , 2) AS variation_pourcent

            FROM {$table} d
            CROSS JOIN bornes b
            WHERE 1 = 1
            {$networkWhere};";

            return $this->mssqlMade2design->executeQuery($query);

        } catch (\Exception $e) {
            $this->graphMailer->notifyError('❌ OGIER Erreur Dashboard : CA MTD', $e);
            $this->logger->error('Erreur CA MTD', ['exception' => $e]);
            return [];
        }
    }

    public function getTopClients(string $network = 'global'): array
    {
        try {
            $networkWhere = $this->buildNetworkWhereClause($network);
            $table        = $this->table('INTRANET_SALES_AGG_YEAR');

            $query = "
            SELECT TOP 10
                customer_name as CustomerName,
                SUM(ca) AS TotalCA_EUR
            FROM {$table}
            WHERE annee = YEAR(GETDATE())
            {$networkWhere}
            GROUP BY customer_name
            ORDER BY TotalCA_EUR DESC;";

            return $this->mssqlMade2design->executeQuery($query);

        } catch (\Exception $e) {
            $this->graphMailer->notifyError('❌ LCS Erreur Dashboard : Top Clients', $e);
            $this->logger->error('LCS Erreur Top Clients', ['exception' => $e]);
            return [];
        }
    }

    public function getTopFamilySales(string $network = 'global'): array
    {
        try {
            $networkWhere = $this->buildNetworkWhereClause($network);
            $table        = $this->table('INTRANET_SALES_AGG_YEAR');

            $query = "SELECT
                    CASE
                        WHEN item_family_code = 'FTW' THEN 'FOOTWEAR'
                        WHEN item_family_code = 'APL' THEN 'TEXTILE'
                        WHEN item_family_code = 'HDW' THEN 'HARDWARE'
                    END AS ItemFamilyCode,
                    SUM(ca) AS TotalSales
                FROM {$table}
                WHERE annee = YEAR(GETDATE())
                {$networkWhere}
                GROUP BY item_family_code
                ORDER BY TotalSales DESC;";

            return $this->mssqlMade2design->executeQuery($query);

        } catch (\Exception $e) {
            $this->graphMailer->notifyError('❌ LCS Erreur Dashboard : Top familles CA', $e);
            $this->logger->error('LCS Erreur Top familles CA', ['exception' => $e]);
            return [];
        }
    }

    public function getTopProductsBySales(string $network = 'global'): array
    {
        try {
            $networkWhere = $this->buildNetworkWhereClause($network);
            $table        = $this->table('INTRANET_SALES_AGG_YEAR');

            $query = "SELECT TOP 5
                    item_no as ItemNo,
                    item_description as ItemDescription,
                    SUM(ca) AS TotalSales
                FROM {$table}
                WHERE annee = YEAR(GETDATE())
                {$networkWhere}
                GROUP BY item_no, item_description
                ORDER BY TotalSales DESC;";

            $rows = $this->mssqlMade2design->executeQuery($query);

            $out = [];
            foreach ($rows as $r) {
                $itemNo = trim((string) ($r->ItemNo ?? ''));
                if ($itemNo === '') {
                    continue;
                }

                $articleBase      = explode('_', $itemNo)[0] ?? $itemNo;
                $safeArticleBase  = rawurlencode($articleBase);

                $out[] = [
                    'image' => "https://www.lecoqsportif.com/cdn/shop/files/{$safeArticleBase}_2.jpg",
                    'code'  => $itemNo,
                    'label' => (string) ($r->ItemDescription ?? ''),
                    'value' => (float)  ($r->TotalSales      ?? 0),
                ];
            }

            return $out;

        } catch (\Exception $e) {
            $this->graphMailer->notifyError('❌ Erreur Dashboard : Top Produits Ventes', $e);
            $this->logger->error('Erreur Top Produits Ventes', ['exception' => $e]);
            return [];
        }
    }

    public function getMonthlySalesEvolutionLast5Years(string $network = 'global'): array
    {
        try {
            $networkWhere = $this->buildNetworkWhereClause($network);
            $table        = $this->table('INTRANET_SALES_AGG_MONTH');

            $query = "SELECT
                    annee,
                    mois,
                    SUM(ca) as ca_mensuel
                FROM {$table}
                WHERE 1 = 1
                {$networkWhere}
                GROUP BY annee, mois
                ORDER BY annee, mois;";

            return $this->mssqlMade2design->executeQuery($query);

        } catch (\Exception $e) {
            $this->graphMailer->notifyError('❌ Erreur Dashboard : Évolution 5 ans', $e);
            $this->logger->error('Erreur CA mensuel 5 ans', ['exception' => $e]);
            return [];
        }
    }

    public function getSalesOfToday(string $network = 'global'): ?float
    {
        try {
            $networkWhere = $this->buildNetworkWhereClause($network);
            $table        = $this->table('INTRANET_SALES_DAILY');

            $j_1     = (new \DateTime('yesterday'))->format('Y-m-d');
            $j_1_n_1 = (new \DateTime('yesterday -1 year'))->format('Y-m-d');

            $query = "
            SELECT
                SUM(CASE
                        WHEN annee = YEAR('{$j_1}')
                         AND mois  = MONTH('{$j_1}')
                         AND jour  = DAY('{$j_1}')
                        THEN ca ELSE 0
                    END) AS ca_n_j_1,

                SUM(CASE
                        WHEN annee = YEAR('{$j_1_n_1}')
                         AND mois  = MONTH('{$j_1_n_1}')
                         AND jour  = DAY('{$j_1_n_1}')
                        THEN ca ELSE 0
                    END) AS ca_n_1_j_1

            FROM {$table}
            WHERE 1 = 1
            {$networkWhere}";

            $result = $this->mssqlMade2design->executeQuery($query);

            return $result[0]->ca_n_j_1 ?? 0;

        } catch (\Exception $e) {
            $this->graphMailer->notifyError('❌ LCS Erreur Dashboard : CA du jour', $e);
            $this->logger->error('LCS Erreur CA du jour', ['exception' => $e]);
            return 0;
        }
    }

    public function getBacklogClientDonut(string $network = 'global'): array
    {
        try {
            $query = "
            SELECT
                CASE
                    WHEN SOQ.DEMDLVDAT_0 <= EOMONTH(GETDATE())                        THEN 'MOIS'
                    WHEN SOQ.DEMDLVDAT_0 <= EOMONTH(DATEADD(MONTH, 1, GETDATE()))     THEN 'MOIS + 1'
                    ELSE '>MOIS + 2'
                END AS retard,
                SOH.CUR_0 AS DEVISE,
                SUM(CAST(ROUND(SOQ.QTY_0 - (SOQ.DLVQTY_0 + SOQ.ODLQTY_0), 0) AS INT)) AS QUANTITE,
                SUM(SOP.NETPRINOT_0 * (SOQ.QTY_0 - (SOQ.DLVQTY_0 + SOQ.ODLQTY_0)))    AS TOTAL_PRIX
            FROM X3_LCS.SORDERQ SOQ
            INNER JOIN X3_LCS.SORDER  SOH ON SOQ.SOHNUM_0 = SOH.SOHNUM_0
            INNER JOIN X3_LCS.SORDERP SOP ON SOQ.SOHNUM_0 = SOP.SOHNUM_0
                                          AND SOQ.ITMREF_0 = SOP.ITMREF_0
                                          AND SOQ.SOPLIN_0 = SOP.SOPLIN_0
            INNER JOIN X3_LCS.BPCUSTOMER BPC ON SOH.BPCORD_0 = BPC.BPCNUM_0
            WHERE SOQ.SOQSTA_0 <> 3
            AND BPC.BCGCOD_0 <> 'INTER'
            GROUP BY
                CASE
                    WHEN SOQ.DEMDLVDAT_0 <= EOMONTH(GETDATE())                    THEN 'MOIS'
                    WHEN SOQ.DEMDLVDAT_0 <= EOMONTH(DATEADD(MONTH, 1, GETDATE())) THEN 'MOIS + 1'
                    ELSE '>MOIS + 2'
                END,
                SOH.CUR_0";

            $rows = $this->mssqlLcs->executeQuery($query);

            $taux = $this->divers->getExchangeRatesValues();

            $buckets = [];
            foreach ($rows as $row) {
                $retard     = $row->retard;
                $devise     = $row->DEVISE;
                $quantite   = (int) $row->QUANTITE;
                $totalPrix  = (float) $row->TOTAL_PRIX;
                $rate       = $taux[$devise] ?? null;
                $montantEur = ($rate !== null && $rate > 0) ? ($totalPrix / $rate) : $totalPrix;

                if (!isset($buckets[$retard])) {
                    $buckets[$retard] = ['quantite' => 0, 'montant' => 0.0];
                }
                $buckets[$retard]['quantite'] += $quantite;
                $buckets[$retard]['montant']  += $montantEur;
            }

            $order = ['MOIS' => 1, 'MOIS + 1' => 2, '>MOIS + 2' => 3];
            uksort($buckets, fn($a, $b) => ($order[$a] ?? 99) <=> ($order[$b] ?? 99));

            $labels     = [];
            $values     = [];
            $quantities = [];

            foreach ($buckets as $retard => $data) {
                $labels[]     = $retard;
                $values[]     = round($data['montant'], 2);
                $quantities[] = $data['quantite'];
            }

            return [
                'labels'     => $labels,
                'values'     => $values,
                'quantities' => $quantities,
            ];

        } catch (\Exception $e) {
            $this->graphMailer->notifyError('❌ Dashboard : Backlog client donut live', $e);
            $this->logger->error('Erreur backlog client donut live', ['exception' => $e]);
            return ['labels' => [], 'values' => [], 'quantities' => []];
        }
    }

    public function refreshSalesAggMonth(): int
    {
        try {
            $table = $this->table('INTRANET_SALES_AGG_MONTH');

            $this->mssqlMade2design->executeDelete("DELETE FROM {$table}");

            $insertQuery = "
        INSERT INTO {$table} (annee, mois, mainnetwork, reportingdimension, ca)

        SELECT
            YEAR(I.DOCUMENTPOSTINGDATE)  AS annee,
            MONTH(I.DOCUMENTPOSTINGDATE) AS mois,
            CUST.MAINNETWORK             AS mainnetwork,
            CUST.REPORTINGDIMENSION      AS reportingdimension,
            SUM(I.AMOUNTEURTM)           AS ca
        FROM SEI_X3_LCS.CONSO_INVOICES I
        LEFT JOIN SEI_X3_LCS.LCS_COLLECTION C
            ON  I.ITEMNO   = C.ITEM_ID
            AND I.SERIESNO = C.SERIESCODE
        LEFT JOIN SEI_X3_LCS.LCS_CUSTOMER CUST
            ON  I.COMPANYCODE = CUST.COMPANY_ID
            AND I.CUSTOMERNO  = CUST.CUSTOMER_ID
        WHERE
            I.ISBOHPERIMETERPRODUCT = 1
            AND (I.DOCUMENTTYPE IN ('INVOICE', 'CREDITMEMO') OR (I.DOCUMENTTYPE IN ('ORDER') AND I.ORDERSTATUS = 3 AND I.DLVQTY > 0))
            AND C.ITEMFAMILYCODE IN ('FTW', 'HDW', 'APL')
            AND I.COMPANYCODE IN ('LCSI BV', 'LCSI')
            AND YEAR(I.DOCUMENTPOSTINGDATE) > YEAR(GETDATE()) - 5
            AND CUST.MAINNETWORK IS NOT NULL
        GROUP BY
            YEAR(I.DOCUMENTPOSTINGDATE),
            MONTH(I.DOCUMENTPOSTINGDATE),
            CUST.MAINNETWORK,
            CUST.REPORTINGDIMENSION;";

            return $this->mssqlMade2design->insertData($insertQuery);

        } catch (\Exception $e) {
            $this->graphMailer->notifyError('❌ Erreur recalcul cube ventes intranet', $e);
            $this->logger->error('Erreur recalcul cube ventes intranet', ['exception' => $e]);
            return 0;
        }
    }

    public function refreshSalesAggMonthClient(): int
    {
        try {
            $table = $this->table('INTRANET_SALES_AGG_YEAR');

            $this->mssqlMade2design->executeDelete("DELETE FROM {$table}");

            $insertQuery = "
        INSERT INTO {$table} (
            annee,
            customer_no,
            customer_name,
            item_family_code,
            item_no,
            item_description,
            ca,
            mainnetwork,
            reportingdimension
        )

        SELECT
            YEAR(I.DOCUMENTPOSTINGDATE) AS annee,
            I.CUSTOMERNO,
            CUST.BILLTONAME,
            COLL.ITEMFAMILYCODE,
            I.ITEMNO,
            COLL.ITEMDESC,
            SUM(I.AMOUNTEURTM)          AS ca,
            CUST.MAINNETWORK            AS mainnetwork,
            CUST.REPORTINGDIMENSION     AS reportingdimension

        FROM SEI_X3_LCS.CONSO_INVOICES I

        LEFT JOIN SEI_X3_LCS.LCS_CUSTOMER CUST
            ON  I.COMPANYCODE = CUST.COMPANY_ID
            AND I.CUSTOMERNO  = CUST.CUSTOMER_ID

        LEFT JOIN SEI_X3_LCS.LCS_COLLECTION COLL
            ON  I.ITEMNO   = COLL.ITEM_ID
            AND I.SERIESNO = COLL.SERIESCODE

        WHERE
            I.ISBOHPERIMETERPRODUCT = 1
            AND (I.DOCUMENTTYPE IN ('INVOICE', 'CREDITMEMO') OR (I.DOCUMENTTYPE IN ('ORDER') AND I.ORDERSTATUS = 3 AND I.DLVQTY > 0))
            AND COLL.ITEMFAMILYCODE IN ('FTW', 'HDW', 'APL')
            AND I.COMPANYCODE = 'LCSI'
            AND I.DOCUMENTPOSTINGDATE >= DATEFROMPARTS(YEAR(GETDATE()), 1, 1)
            AND CUST.MAINNETWORK IS NOT NULL

        GROUP BY
            YEAR(I.DOCUMENTPOSTINGDATE),
            I.CUSTOMERNO,
            CUST.BILLTONAME,
            COLL.ITEMFAMILYCODE,
            I.ITEMNO,
            COLL.ITEMDESC,
            CUST.MAINNETWORK,
            CUST.REPORTINGDIMENSION;";

            return $this->mssqlMade2design->insertData($insertQuery);

        } catch (\Exception $e) {
            $this->graphMailer->notifyError('❌ Erreur recalcul cube ventes client', $e);
            $this->logger->error('Erreur recalcul cube ventes client', ['exception' => $e]);
            return 0;
        }
    }

    public function refreshSalesDaily(): int
    {
        try {
            $table = $this->table('INTRANET_SALES_DAILY');

            $this->mssqlMade2design->executeDelete("DELETE FROM {$table}");

            $insertQuery = "
        INSERT INTO {$table} (date, annee, mois, jour, ca, mainnetwork, reportingdimension)

        SELECT
            CAST(I.DOCUMENTPOSTINGDATE AS DATE) AS [date],
            YEAR(I.DOCUMENTPOSTINGDATE)         AS annee,
            MONTH(I.DOCUMENTPOSTINGDATE)        AS mois,
            DAY(I.DOCUMENTPOSTINGDATE)          AS jour,
            SUM(I.AMOUNTEURTM)                  AS ca,
            CUST.MAINNETWORK,
            CUST.REPORTINGDIMENSION

        FROM SEI_X3_LCS.CONSO_INVOICES I

        LEFT JOIN SEI_X3_LCS.LCS_COLLECTION C
            ON  I.ITEMNO   = C.ITEM_ID
            AND I.SERIESNO = C.SERIESCODE

        LEFT JOIN SEI_X3_LCS.LCS_CUSTOMER CUST
            ON  I.COMPANYCODE = CUST.COMPANY_ID
            AND I.CUSTOMERNO  = CUST.CUSTOMER_ID

        WHERE
            I.ISBOHPERIMETERPRODUCT = 1
            AND (I.DOCUMENTTYPE IN ('INVOICE', 'CREDITMEMO') OR (I.DOCUMENTTYPE IN ('ORDER') AND I.ORDERSTATUS = 3 AND I.DLVQTY > 0))
            AND C.ITEMFAMILYCODE IN ('FTW', 'HDW', 'APL')
            AND I.COMPANYCODE IN ('LCSI BV', 'LCSI')
            AND I.DOCUMENTPOSTINGDATE >= DATEADD(YEAR, -2, GETDATE())
            AND CUST.MAINNETWORK IS NOT NULL

        GROUP BY
            CAST(I.DOCUMENTPOSTINGDATE AS DATE),
            YEAR(I.DOCUMENTPOSTINGDATE),
            MONTH(I.DOCUMENTPOSTINGDATE),
            DAY(I.DOCUMENTPOSTINGDATE),
            CUST.MAINNETWORK,
            CUST.REPORTINGDIMENSION

        ORDER BY [date];";

            return $this->mssqlMade2design->insertData($insertQuery);

        } catch (\Exception $e) {
            $this->graphMailer->notifyError('❌ Erreur recalcul cube ventes daily', $e);
            $this->logger->error('Erreur recalcul cube ventes daily', ['exception' => $e]);
            return 0;
        }
    }

    public function refreshAllSalesCubes(): array
    {
        return [
            'Cube CA mensuel'          => $this->refreshSalesAggMonth(),
            'Cube ventes client année' => $this->refreshSalesAggMonthClient(),
            'Cube ventes journalières' => $this->refreshSalesDaily(),
        ];
    }
}
