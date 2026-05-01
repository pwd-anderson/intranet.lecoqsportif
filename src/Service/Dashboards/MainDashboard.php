<?php

namespace App\Service\Dashboards;

use App\Factory\MssqlManagerFactory;
use App\Service\Tools\GraphMailer;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mime\Email;
use Psr\Log\LoggerInterface;
use App\Service\Tools\MssqlManager;

class MainDashboard
{

    private MssqlManager $mssqlMade2design;

    public function __construct(
        private  MssqlManagerFactory $mssqlManagerFactory,
        private LoggerInterface $logger,
        private GraphMailer $graphMailer,
        #[Autowire('%db.lcs_sei%')]
        string $dbLcsSei,
    )
    {
        $this->mssqlMade2design = $this->mssqlManagerFactory->create($dbLcsSei);
    }

    private function normalizeNetworkFilter(?string $network): string
    {
        return in_array($network, ['global', 'boutique', 'ecom'], true)
            ? $network
            : 'global';
    }

    private function buildMainNetworkWhereClause(string $network, string $column = 'mainnetwork'): string
    {
        return match ($this->normalizeNetworkFilter($network)) {
            'boutique' => " AND {$column} IN ('RETAIL FO', 'CLEARANCE', 'RETAIL CS', 'CONCEPT STORE', 'FACTORY OUTLET')",
            'ecom' => " AND {$column} IN ('RETAIL MARKET PLACE', 'RETAIL ESHOP','E BUSINESS DIRECT','E BUSINESS MARKET PLACE')",
            default => '',
        };
    }

    public function getSalesComparaisonYears(string $network = 'global'): array
    {
        try {
            $params = [];
            $networkWhere = $this->buildMainNetworkWhereClause($network);

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

                FROM MASTER_TABLES.INTRANET_SALES_DAILY
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
            $params = [];
            $networkWhere = $this->buildMainNetworkWhereClause($network);

            $query = "SELECT
                    mois,
                    SUM(CASE WHEN annee = YEAR(GETDATE()) THEN ca ELSE 0 END) AS ca_n,
                    SUM(CASE WHEN annee = YEAR(GETDATE())-1 THEN ca ELSE 0 END) AS ca_n_1
                FROM MASTER_TABLES.INTRANET_SALES_DAILY
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
            $networkWhere = $this->buildMainNetworkWhereClause($network);

            $j_1   = new \DateTime('yesterday');
            $year  = (int) $j_1->format('Y');
            $month = (int) $j_1->format('n');
            $day   = (int) $j_1->format('j');
            $year_n_1 = $year - 1;

            $query = "
            SELECT
                jour,
                SUM(CASE
                        WHEN annee = {$year}
                        THEN ca ELSE 0
                    END) AS ca_n,

                SUM(CASE
                        WHEN annee = {$year_n_1}
                        THEN ca ELSE 0
                    END) AS ca_n_1

            FROM MASTER_TABLES.INTRANET_SALES_DAILY
            WHERE mois = {$month}
              AND jour <= {$day}
              AND annee IN ({$year}, {$year_n_1})
              {$networkWhere}
            GROUP BY jour
            ORDER BY jour;
        ";

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
            $networkWhere = $this->buildMainNetworkWhereClause($network, 'd.mainnetwork');

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

            FROM MASTER_TABLES.INTRANET_SALES_DAILY d
            CROSS JOIN bornes b
            WHERE 1 = 1
            {$networkWhere};
        ";

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
            $params = [];
            $networkWhere = $this->buildMainNetworkWhereClause($network);

            $query = "
            SELECT TOP 10
                customer_name as CustomerName,
                SUM(ca) AS TotalCA_EUR
            FROM MASTER_TABLES.INTRANET_SALES_AGG_YEAR
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
            $params = [];
            $networkWhere = $this->buildMainNetworkWhereClause($network);

            $query = "SELECT
                    CASE
                        WHEN item_family_code = 'FTW' THEN 'FOOTWEAR'
                        WHEN item_family_code = 'APL' THEN 'TEXTILE'
                        WHEN item_family_code = 'HDW' THEN 'HARDWARE'
                    END AS ItemFamilyCode,
                    SUM(ca) AS TotalSales
                FROM MASTER_TABLES.INTRANET_SALES_AGG_YEAR
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
            $params = [];
            $networkWhere = $this->buildMainNetworkWhereClause($network);

            $query = "SELECT TOP 5
                    item_no as ItemNo,
                    item_description as ItemDescription,
                    SUM(ca) AS TotalSales
                FROM MASTER_TABLES.INTRANET_SALES_AGG_YEAR
                WHERE annee = YEAR(GETDATE())
                {$networkWhere}
                GROUP BY item_no, item_description
                ORDER BY TotalSales DESC;";

            $rows = $this->mssqlMade2design->executeQuery($query);

            $out = [];
            foreach ($rows as $r) {
                $itemNo = trim((string)($r->ItemNo ?? ''));
                if ($itemNo === '') {
                    continue;
                }

                $safeItemNo = rawurlencode($itemNo);

                $out[] = [
                    'image' => "https://www.lecoqbiz.com/CMS/Images/Small/{$safeItemNo}.jpg",
                    'code'  => $itemNo,
                    'label' => (string)($r->ItemDescription ?? ''),
                    'value' => (float)($r->TotalSales ?? 0),
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
            $params = [];
            $networkWhere = $this->buildMainNetworkWhereClause($network);

            $query = "SELECT
                    annee,
                    mois,
                    SUM(ca) as ca_mensuel
                FROM MASTER_TABLES.INTRANET_SALES_AGG_MONTH
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
            $networkWhere = $this->buildMainNetworkWhereClause($network);

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

            FROM MASTER_TABLES.INTRANET_SALES_DAILY
            WHERE 1 = 1
            {$networkWhere}
        ";

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
            $networkWhere = $this->buildMainNetworkWhereClause($network);
            $query = "
            SELECT
                retard,
                SUM(quantite) AS quantite,
                SUM(montant_ht_eur) AS montant
            FROM MASTER_TABLES.INTRANET_BACKLOG_CLI
            where 1 = 1
            {$networkWhere}
            GROUP BY retard
            ORDER BY
                CASE retard
                    WHEN 'MOIS' THEN 1
                    WHEN 'MOIS + 1' THEN 2
                    ELSE 3
                END
        ";

            $results = $this->mssqlMade2design->executeQuery($query);

            $labels = [];
            $values = [];
            $quantities = [];

            foreach ($results as $row) {
                $labels[] = $row->retard;
                $values[] = (float)$row->montant;
                $quantities[] = (float)$row->quantite;
            }

            return [
                'labels' => $labels,
                'values' => $values,
                'quantities' => $quantities
            ];

        } catch (\Exception $e) {

            $this->graphMailer->notifyError('❌ Dashboard : Backlog client donut', $e);

            $this->logger->error('Erreur backlog client donut', [
                'exception' => $e
            ]);

            return [
                'labels' => [],
                'values' => [],
                'quantities' => []
            ];
        }
    }

    public function refreshSalesAggMonth(): int
    {
        try {

            // 1️⃣ suppression des anciennes données
            $deleteQuery = "DELETE FROM [MASTER_TABLES].[INTRANET_SALES_AGG_MONTH]";
            $this->mssqlMade2design->executeDelete($deleteQuery);

            // 2️⃣ recalcul des données
            $insertQuery = "
        INSERT INTO [MASTER_TABLES].[INTRANET_SALES_AGG_MONTH] (annee, mois, mainnetwork, ca)

        SELECT
            YEAR(I.DOCUMENTPOSTINGDATE) AS annee,
            MONTH(I.DOCUMENTPOSTINGDATE) AS mois,
            CUST.MAINNETWORK AS mainnetwork,
            SUM(I.AMOUNTEURTM) AS ca
        FROM SEI_X3_LCS.CONSO_INVOICES I
        LEFT JOIN SEI_X3_LCS.LCS_COLLECTION C
            ON I.ITEMNO = C.ITEM_ID
            AND I.SERIESNO = C.SERIESCODE
        LEFT JOIN SEI_X3_LCS.LCS_CUSTOMER CUST
            ON I.COMPANYCODE = CUST.COMPANY_ID
            AND I.CUSTOMERNO = CUST.CUSTOMER_ID
        WHERE
            I.ISBOHPERIMETERPRODUCT = 1
            AND (I.DOCUMENTTYPE IN ('INVOICE', 'CREDITMEMO') OR (I.DOCUMENTTYPE IN ('ORDER') AND I.ORDERSTATUS = 3 AND I.ALLSTA IN (2,3)))
            AND C.ITEMFAMILYCODE IN ('FTW', 'HDW', 'APL')
            AND I.COMPANYCODE IN ('LCSI BV', 'LCSI')
            AND YEAR(I.DOCUMENTPOSTINGDATE) > YEAR(GETDATE()) - 5
            AND CUST.MAINNETWORK IS NOT NULL
        GROUP BY
            YEAR(I.DOCUMENTPOSTINGDATE),
            MONTH(I.DOCUMENTPOSTINGDATE),
            CUST.MAINNETWORK;
                ";

            return $this->mssqlMade2design->insertData($insertQuery);

        } catch (\Exception $e) {

            $this->graphMailer->notifyError(
                '❌ Erreur recalcul cube ventes intranet',
                $e
            );

            $this->logger->error(
                'Erreur recalcul cube ventes intranet',
                ['exception' => $e]
            );

            return 0;
        }
    }

    public function refreshSalesAggMonthClient(): int
    {
        try {

            // suppression
            $deleteQuery = "DELETE FROM [MASTER_TABLES].[INTRANET_SALES_AGG_YEAR]";
            $this->mssqlMade2design->executeDelete($deleteQuery);

            // insertion
            $insertQuery = "
        INSERT INTO [MASTER_TABLES].[INTRANET_SALES_AGG_YEAR] (
            annee,
            customer_no,
            customer_name,
            item_family_code,
            item_no,
            item_description,
            ca,
            mainnetwork
        )

        SELECT
            YEAR(I.DOCUMENTPOSTINGDATE) AS annee,
            I.CUSTOMERNO,
            CUST.BILLTONAME,
            COLL.ITEMFAMILYCODE,
            I.ITEMNO,
            COLL.ITEMDESC,
            SUM(I.AMOUNTEURTM) AS ca,
            CUST.MAINNETWORK AS mainnetwork

        FROM SEI_X3_LCS.CONSO_INVOICES I

        LEFT JOIN SEI_X3_LCS.LCS_CUSTOMER CUST
            ON I.COMPANYCODE = CUST.COMPANY_ID
            AND I.CUSTOMERNO = CUST.CUSTOMER_ID

        LEFT JOIN SEI_X3_LCS.LCS_COLLECTION COLL
            ON I.ITEMNO = COLL.ITEM_ID
            AND I.SERIESNO = COLL.SERIESCODE

        WHERE
            I.ISBOHPERIMETERPRODUCT = 1
            AND (I.DOCUMENTTYPE IN ('INVOICE', 'CREDITMEMO') OR (I.DOCUMENTTYPE IN ('ORDER') AND I.ORDERSTATUS = 3 AND I.ALLSTA IN (2,3)))
            AND COLL.ITEMFAMILYCODE IN ('FTW', 'HDW', 'APL')
            AND I.COMPANYCODE = 'LCSI'
            AND I.DOCUMENTPOSTINGDATE >= DATEFROMPARTS(YEAR(GETDATE()),1,1)
            AND CUST.MAINNETWORK IS NOT NULL

        GROUP BY
            YEAR(I.DOCUMENTPOSTINGDATE),
            I.CUSTOMERNO,
            CUST.BILLTONAME,
            COLL.ITEMFAMILYCODE,
            I.ITEMNO,
            COLL.ITEMDESC,
            CUST.MAINNETWORK
        ";

            return $this->mssqlMade2design->insertData($insertQuery);

        } catch (\Exception $e) {

            $this->graphMailer->notifyError(
                '❌ Erreur recalcul cube ventes client',
                $e
            );

            $this->logger->error(
                'Erreur recalcul cube ventes client',
                ['exception' => $e]
            );

            return 0;
        }
    }

    public function refreshSalesDaily(): int
    {
        try {

            // suppression
            $deleteQuery = "DELETE FROM MASTER_TABLES.INTRANET_SALES_DAILY";
            $this->mssqlMade2design->executeDelete($deleteQuery);

            // insertion
            $insertQuery = "
        INSERT INTO MASTER_TABLES.INTRANET_SALES_DAILY (
            date,
            annee,
            mois,
            jour,
            ca,
            mainnetwork
        )

        SELECT
            CAST(I.DOCUMENTPOSTINGDATE AS DATE) AS [date],
            YEAR(I.DOCUMENTPOSTINGDATE) AS annee,
            MONTH(I.DOCUMENTPOSTINGDATE) AS mois,
            DAY(I.DOCUMENTPOSTINGDATE) AS jour,
            SUM(I.AMOUNTEURTM) AS ca,
            CUST.MAINNETWORK

        FROM SEI_X3_LCS.CONSO_INVOICES I

        LEFT JOIN SEI_X3_LCS.LCS_COLLECTION C
            ON I.ITEMNO = C.ITEM_ID
            AND I.SERIESNO = C.SERIESCODE

        LEFT JOIN SEI_X3_LCS.LCS_CUSTOMER CUST
    ON I.COMPANYCODE = CUST.COMPANY_ID
    AND I.CUSTOMERNO = CUST.CUSTOMER_ID

        WHERE
            I.ISBOHPERIMETERPRODUCT = 1
            AND (I.DOCUMENTTYPE IN ('INVOICE', 'CREDITMEMO') OR (I.DOCUMENTTYPE IN ('ORDER') AND I.ORDERSTATUS = 3 AND I.ALLSTA IN (2,3)))
            AND C.ITEMFAMILYCODE IN ('FTW', 'HDW', 'APL')
            AND I.COMPANYCODE IN ('LCSI BV', 'LCSI')
            AND I.DOCUMENTPOSTINGDATE >= DATEADD(YEAR, -2, GETDATE())
            AND CUST.MAINNETWORK IS NOT NULL

        GROUP BY
            CAST(I.DOCUMENTPOSTINGDATE AS DATE),
            YEAR(I.DOCUMENTPOSTINGDATE),
            MONTH(I.DOCUMENTPOSTINGDATE),
            DAY(I.DOCUMENTPOSTINGDATE),
            CUST.MAINNETWORK

        ORDER BY [date]
        ";

            return $this->mssqlMade2design->insertData($insertQuery);

        } catch (\Exception $e) {

            $this->graphMailer->notifyError(
                '❌ Erreur recalcul cube ventes daily',
                $e
            );

            $this->logger->error(
                'Erreur recalcul cube ventes daily',
                ['exception' => $e]
            );

            return 0;
        }
    }

    public function refreshBacklogClient(): int
    {
        try {

            // suppression
            $deleteQuery = "DELETE FROM MASTER_TABLES.INTRANET_BACKLOG_CLI";
            $this->mssqlMade2design->executeDelete($deleteQuery);

            // insertion
            $insertQuery = "
        INSERT INTO MASTER_TABLES.INTRANET_BACKLOG_CLI (
            retard,
            quantite,
            montant_ht_eur,
            date_refresh,
            mainnetwork
        )
        SELECT
            retard,
            SUM(OUT_Quantity) AS quantite,
            SUM(OUT_AmountEur) AS montant_ht_eur,
            getdate(),
            mainnetwork
        FROM (
            SELECT
                s.OUT_Quantity,
                s.OUT_AmountEur,
                s.CustomerNo,
                CUST.MAINNETWORK as mainnetwork,

                CASE
                    WHEN o.RequestedDeliveryDate_L <= EOMONTH(GETDATE()) THEN 'MOIS'
                    WHEN o.RequestedDeliveryDate_L > EOMONTH(GETDATE())
                         AND o.RequestedDeliveryDate_L <= EOMONTH(DATEADD(MONTH, 1, GETDATE())) THEN 'MOIS + 1'
                    ELSE '>MOIS + 2'
                END AS retard

            FROM DWH_LCS.F_Sales s

            LEFT JOIN DWH_LCS.F_Sales_Orders o
                ON s.OrderDocumentNo = o.OrderDocumentNo
                AND s.CompanyCode = o.CompanyCode
                AND s.OrderDocumentLineNo = o.OrderDocumentLineNo
                AND s.VariantCode = o.VariantCode

            LEFT JOIN SEI_X3_LCS.LCS_CUSTOMER CUST
    ON s.CompanyCode = CUST.COMPANY_ID
    AND s.CustomerNo = CUST.CUSTOMER_ID

            WHERE s.CompanyCode = 'LCSI BV'
              AND s.OUT_Quantity <> 0
              AND s.IsBohPerimeter = 1
              AND s.LocationCode IN ('DIRECT', 'DT-WHS-TH', 'LOGTXM-1', 'SF-WHS-CN1')
              AND s.SalesOrderType IN ('CO', 'OP', 'PS', 'RE')
            ) t
        GROUP BY retard, mainnetwork
        ";

            return $this->mssqlMade2design->insertData($insertQuery);

        } catch (\Exception $e) {

            $this->graphMailer->notifyError(
                '❌ Erreur recalcul cube backlog client',
                $e
            );

            $this->logger->error(
                'Erreur recalcul cube backlog client',
                ['exception' => $e]
            );

            return 0;
        }
    }

    public function refreshAllSalesCubes(): array
    {
        return [
            'Cube CA mensuel' => $this->refreshSalesAggMonth(),
            'Cube ventes client année' => $this->refreshSalesAggMonthClient(),
            'Cube ventes journalières' => $this->refreshSalesDaily(),
            'Cube backlog client' => $this->refreshBacklogClient(),
        ];
    }
}
