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
            $query = "SELECT
                    SUM(CASE
                            WHEN annee = YEAR(GETDATE())
                             AND mois = MONTH(GETDATE())
                             AND jour <= DAY(GETDATE())
                            THEN ca ELSE 0
                        END) AS ca_n,

                    SUM(CASE
                            WHEN annee = YEAR(GETDATE()) - 1
                             AND mois = MONTH(GETDATE())
                             AND jour <= DAY(GETDATE())
                            THEN ca ELSE 0
                        END) AS ca_n_1,

                    ROUND(
                        CASE
                            WHEN SUM(CASE
                                        WHEN annee = YEAR(GETDATE()) - 1
                                         AND mois = MONTH(GETDATE())
                                         AND jour <= DAY(GETDATE())
                                        THEN ca ELSE 0
                                     END) = 0
                                THEN NULL
                            ELSE
                                (
                                    SUM(CASE
                                            WHEN annee = YEAR(GETDATE())
                                             AND mois = MONTH(GETDATE())
                                             AND jour <= DAY(GETDATE())
                                            THEN ca ELSE 0
                                        END)
                                    -
                                    SUM(CASE
                                            WHEN annee = YEAR(GETDATE()) - 1
                                             AND mois = MONTH(GETDATE())
                                             AND jour <= DAY(GETDATE())
                                            THEN ca ELSE 0
                                        END)
                                )
                                /
                                SUM(CASE
                                        WHEN annee = YEAR(GETDATE()) - 1
                                         AND mois = MONTH(GETDATE())
                                         AND jour <= DAY(GETDATE())
                                        THEN ca ELSE 0
                                    END)
                                * 100
                        END
                    , 2) AS variation_pourcent
                FROM MASTER_TABLES.INTRANET_SALES_DAILY";

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
}
