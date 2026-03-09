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

    public function getSalesComparaisonYears(): array
    {
        try {
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
                        (mois < MONTH(GETDATE()))
                        OR (mois = MONTH(GETDATE()) AND jour <= DAY(GETDATE()));";

            $dataGraph = $this->mssqlMade2design->executeQuery($query);
            return $dataGraph;

        } catch (\Exception $e) {
            $this->graphMailer->notifyError('❌ OGIER Erreur Dashboard : Récupération de données Conversion Rate', $e);
            $this->logger->error('Error Récupération de données Conversion Rate', ['exception' => $e]);
        }
    }

    public function getSalesComparaisonByMonths(): array
    {
        try {
            $query = "SELECT
                    mois,
                    SUM(CASE WHEN annee = YEAR(GETDATE()) THEN ca ELSE 0 END) AS ca_n,
                    SUM(CASE WHEN annee = YEAR(GETDATE())-1 THEN ca ELSE 0 END) AS ca_n_1
                FROM MASTER_TABLES.INTRANET_SALES_DAILY
                GROUP BY mois
                ORDER BY mois;";

            $dataGraph = $this->mssqlMade2design->executeQuery($query);
            return $dataGraph;

        } catch (\Exception $e) {
            $this->graphMailer->notifyError('❌ OGIER Erreur Dashboard : Récupération de données Conversion Rate', $e);
            $this->logger->error('Error Récupération de données Conversion Rate', ['exception' => $e]);
        }
    }

    public function getSalesComparaisonCurrentMonthByDay(): array
    {
        try {
            $query = "SELECT
                        jour,
                        SUM(CASE
                                WHEN annee = YEAR(GETDATE())
                                 AND jour <= DAY(GETDATE())
                                THEN ca ELSE 0
                            END) AS ca_n,

                        SUM(CASE
                                WHEN annee = YEAR(GETDATE()) - 1
                                 AND jour <= DAY(GETDATE())
                                THEN ca ELSE 0
                            END) AS ca_n_1

                    FROM MASTER_TABLES.INTRANET_SALES_DAILY
                    WHERE
                        mois = MONTH(GETDATE())
                        AND jour <= DAY(GETDATE())
                    GROUP BY jour
                    ORDER BY jour;";

            return $this->mssqlMade2design->executeQuery($query);

        } catch (\Exception $e) {
            $this->graphMailer->notifyError('❌ OGIER Erreur Dashboard : CA MTD jour', $e);
            $this->logger->error('Erreur CA MTD jour', ['exception' => $e]);
            return [];
        }
    }

    public function getSalesComparaisonCurrentMonth(): array
    {
        try {
            $query = "WITH bornes AS (
    SELECT
        DATEFROMPARTS(YEAR(GETDATE()), MONTH(GETDATE()), 1) AS start_n,
        DATEADD(DAY, -1, CAST(GETDATE() AS DATE)) AS end_n,

        DATEFROMPARTS(YEAR(GETDATE()) - 1, MONTH(GETDATE()), 1) AS start_n1,
        DATEADD(YEAR, -1, DATEADD(DAY, -1, CAST(GETDATE() AS DATE))) AS end_n1
)

SELECT
    SUM(CASE WHEN d.date BETWEEN b.start_n AND b.end_n THEN d.ca ELSE 0 END) AS ca_n,

    SUM(CASE WHEN d.date BETWEEN b.start_n1 AND b.end_n1 THEN d.ca ELSE 0 END) AS ca_n_1,

    ROUND(
        CASE
            WHEN SUM(CASE WHEN d.date BETWEEN b.start_n1 AND b.end_n1 THEN d.ca ELSE 0 END) = 0
                THEN NULL
            ELSE
                (
                    SUM(CASE WHEN d.date BETWEEN b.start_n AND b.end_n THEN d.ca ELSE 0 END)
                    -
                    SUM(CASE WHEN d.date BETWEEN b.start_n1 AND b.end_n1 THEN d.ca ELSE 0 END)
                )
                /
                SUM(CASE WHEN d.date BETWEEN b.start_n1 AND b.end_n1 THEN d.ca ELSE 0 END)
                * 100
        END
    , 2) AS variation_pourcent

FROM MASTER_TABLES.INTRANET_SALES_DAILY d
CROSS JOIN bornes b;";

            return $this->mssqlMade2design->executeQuery($query);

        } catch (\Exception $e) {
            $this->graphMailer->notifyError('❌ OGIER Erreur Dashboard : CA MTD', $e);
            $this->logger->error('Erreur CA MTD', ['exception' => $e]);
            return [];
        }
    }

    public function getTopClients(): array
    {
        try {
            $query = "
                    SELECT TOP 10
                        customer_name as CustomerName,
                        SUM(ca) AS TotalCA_EUR
                    FROM MASTER_TABLES.INTRANET_SALES_AGG_YEAR
                    WHERE annee = YEAR(GETDATE())
                    GROUP BY customer_name
                    ORDER BY TotalCA_EUR DESC;";

            return $this->mssqlMade2design->executeQuery($query);

        } catch (\Exception $e) {
            $this->graphMailer->notifyError('❌ LCS Erreur Dashboard : Top Clients', $e);
            $this->logger->error(' LCS Erreur Top Clients', ['exception' => $e]);
            return [];
        }
    }

    public function getTopFamilySales(): array
    {
        try {
            $query = "SELECT
                    CASE
                        WHEN item_family_code = 'FTW' THEN 'FOOTWEAR'
                        WHEN item_family_code = 'APL' THEN 'TEXTILE'
                        WHEN item_family_code = 'HDW' THEN 'HARDWARE'
                    END AS ItemFamilyCode,
                    SUM(ca) AS TotalSales
                FROM MASTER_TABLES.INTRANET_SALES_AGG_YEAR
                WHERE annee = YEAR(GETDATE())
                GROUP BY item_family_code
                ORDER BY TotalSales DESC;";

            return $this->mssqlMade2design->executeQuery($query);

        } catch (\Exception $e) {
            $this->graphMailer->notifyError('❌ LCS Erreur Dashboard : Top familles CA', $e);
            $this->logger->error('LCS Erreur Top familles CA', ['exception' => $e]);
            return [];
        }
    }

    public function getTopProductsBySales(): array
    {
        try {
            $query = "SELECT TOP 5
                        item_no as ItemNo,
                        item_description as ItemDescription,
                        SUM(ca) AS TotalSales
                    FROM MASTER_TABLES.INTRANET_SALES_AGG_YEAR
                    WHERE annee = YEAR(GETDATE())
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
                    'image'  => "https://www.lecoqbiz.com/CMS/Images/Small/{$safeItemNo}.jpg",
                    'code'   => $itemNo,
                    'label'  => (string)($r->ItemDescription ?? ''),
                    'value'  => (float)($r->TotalSales ?? 0),
                ];
            }

            return $out;

        } catch (\Exception $e) {
            $this->graphMailer->notifyError('❌ Erreur Dashboard : Top Produits Ventes', $e);
            $this->logger->error('Erreur Top Produits Ventes', ['exception' => $e]);
            return [];
        }
    }

    public function getMonthlySalesEvolutionLast5Years(): array
    {
        try {
            $query = "SELECT annee, mois, ca as ca_mensuel
                    FROM MASTER_TABLES.INTRANET_SALES_AGG_MONTH
                    ORDER BY annee, mois;";

            return $this->mssqlMade2design->executeQuery($query);
        } catch (\Exception $e) {
            $this->graphMailer->notifyError('❌ Erreur Dashboard : Évolution 5 ans', $e);
            $this->logger->error('Erreur CA mensuel 5 ans', ['exception' => $e]);
            return [];
        }
    }

    public function getSalesOfToday(): ?float
    {
        try {
            $query = "
            SELECT
            SUM(CASE
                    WHEN annee = YEAR(GETDATE())
                     AND mois = MONTH(GETDATE())
                     AND jour = DAY(GETDATE()) - 1
                    THEN ca ELSE 0
                END) AS ca_n_j_1,

            SUM(CASE
                    WHEN annee = YEAR(GETDATE()) - 1
                     AND mois = MONTH(GETDATE())
                     AND jour = DAY(GETDATE()) - 1
                    THEN ca ELSE 0
                END) AS ca_n_1_j_1

        FROM MASTER_TABLES.INTRANET_SALES_DAILY;";

            $result = $this->mssqlMade2design->executeQuery($query);
            //dd($result[0]->ca_jour);

            return $result[0]->ca_n_j_1 ?? 0;
        } catch (\Exception $e) {
            $this->graphMailer->notifyError('❌ LCS Erreur Dashboard : CA du jour', $e);
            $this->logger->error('LCS Erreur CA du jour', ['exception' => $e]);
            return 0;
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
        INSERT INTO [MASTER_TABLES].[INTRANET_SALES_AGG_MONTH] (annee, mois, ca)

        SELECT
            YEAR(I.DOCUMENTPOSTINGDATE) AS annee,
            MONTH(I.DOCUMENTPOSTINGDATE) AS mois,
            SUM(I.AMOUNTEURTM) AS ca

        FROM SEI_X3_LCS.CONSO_INVOICES I

        LEFT JOIN SEI_X3_LCS.LCS_COLLECTION C
            ON I.ITEMNO = C.ITEM_ID
            AND I.SERIESNO = C.SERIESCODE

        WHERE
            I.ISBOHPERIMETERPRODUCT = 1
            AND I.DOCUMENTTYPE IN ('INVOICE', 'CREDITMEMO')
            AND C.ITEMFAMILYCODE IN ('FTW', 'HDW', 'APL')
            AND I.SOURCE = 'LCS'
            AND I.COMPANYCODE IN ('LCSI BV', 'LCSI')
            AND YEAR(I.DOCUMENTPOSTINGDATE) > YEAR(GETDATE()) - 5

        GROUP BY
            YEAR(I.DOCUMENTPOSTINGDATE),
            MONTH(I.DOCUMENTPOSTINGDATE)
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
            ca
        )

        SELECT
            YEAR(I.DOCUMENTPOSTINGDATE) AS annee,
            I.CUSTOMERNO,
            CUST.BILLTONAME,
            COLL.ITEMFAMILYCODE,
            I.ITEMNO,
            COLL.ITEMDESC,
            SUM(I.AMOUNTEURTM) AS ca

        FROM SEI_X3_LCS.CONSO_INVOICES I

        LEFT JOIN SEI_X3_LCS.LCS_CUSTOMER CUST
            ON I.COMPANYCODE = CUST.COMPANY_ID
            AND I.CUSTOMERNO = CUST.CUSTOMER_ID

        LEFT JOIN SEI_X3_LCS.LCS_COLLECTION COLL
            ON I.ITEMNO = COLL.ITEM_ID
            AND I.SERIESNO = COLL.SERIESCODE

        WHERE
            I.ISBOHPERIMETERPRODUCT = 1
            AND I.DOCUMENTTYPE IN ('INVOICE', 'CREDITMEMO')
            AND COLL.ITEMFAMILYCODE IN ('FTW', 'HDW', 'APL')
            AND I.SOURCE = 'LCS'
            AND I.COMPANYCODE = 'LCSI'
            AND I.DOCUMENTPOSTINGDATE >= DATEFROMPARTS(YEAR(GETDATE()),1,1)

        GROUP BY
            YEAR(I.DOCUMENTPOSTINGDATE),
            I.CUSTOMERNO,
            CUST.BILLTONAME,
            COLL.ITEMFAMILYCODE,
            I.ITEMNO,
            COLL.ITEMDESC
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
            ca
        )

        SELECT
            CAST(I.DOCUMENTPOSTINGDATE AS DATE) AS [date],
            YEAR(I.DOCUMENTPOSTINGDATE) AS annee,
            MONTH(I.DOCUMENTPOSTINGDATE) AS mois,
            DAY(I.DOCUMENTPOSTINGDATE) AS jour,
            SUM(I.AMOUNTEURTM) AS ca

        FROM SEI_X3_LCS.CONSO_INVOICES I

        LEFT JOIN SEI_X3_LCS.LCS_COLLECTION C
            ON I.ITEMNO = C.ITEM_ID
            AND I.SERIESNO = C.SERIESCODE

        WHERE
            I.ISBOHPERIMETERPRODUCT = 1
            AND I.DOCUMENTTYPE IN ('INVOICE', 'CREDITMEMO')
            AND C.ITEMFAMILYCODE IN ('FTW', 'HDW', 'APL')
            AND I.SOURCE = 'LCS'
            AND I.COMPANYCODE IN ('LCSI BV', 'LCSI')
            AND I.DOCUMENTPOSTINGDATE >= DATEADD(YEAR, -2, GETDATE())

        GROUP BY
            CAST(I.DOCUMENTPOSTINGDATE AS DATE),
            YEAR(I.DOCUMENTPOSTINGDATE),
            MONTH(I.DOCUMENTPOSTINGDATE),
            DAY(I.DOCUMENTPOSTINGDATE)

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

    public function refreshAllSalesCubes(): array
    {
        return [
            'Cube CA mensuel' => $this->refreshSalesAggMonth(),
            'Cube ventes client année' => $this->refreshSalesAggMonthClient(),
            'Cube ventes journalières' => $this->refreshSalesDaily(),
        ];
    }
}
