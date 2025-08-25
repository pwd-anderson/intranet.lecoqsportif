<?php

namespace App\Service\Dashboards;

use App\Service\Tools\GraphMailer;
use Symfony\Component\Mime\Email;
use Psr\Log\LoggerInterface;
use App\Service\Tools\MssqlManager;

class MainDashboard
{

    public function __construct(
        private  MssqlManager $mssqlManager,
        private LoggerInterface $logger,
        private GraphMailer $graphMailer
    ) {}

    public function getSalesComparaisonYears(): array
    {
        try {
            $query = "WITH ventes_annuelles AS (
                        SELECT
                            CAST(I.ExpectedInvoicingDate AS DATE) AS jour,
                            I.AmountEurTM
                        FROM BI.DWH.F_Invoices I
                        WHERE
                            I.IsBohPerimeter_Product = 1
                            AND I.IsBohPerimeter_IR = 1
                            AND I.DocumentType = 'INVOICE'
                            AND YEAR(I.ExpectedInvoicingDate) IN (YEAR(GETDATE()), YEAR(DATEADD(YEAR, -1, GETDATE())))
                            AND (
                                (YEAR(I.ExpectedInvoicingDate) = YEAR(GETDATE()) AND I.ExpectedInvoicingDate <= CAST(GETDATE() AS DATE))
                                OR
                                (YEAR(I.ExpectedInvoicingDate) = YEAR(DATEADD(YEAR, -1, GETDATE())) AND
                                 (MONTH(I.ExpectedInvoicingDate) < MONTH(GETDATE()) OR
                                 (MONTH(I.ExpectedInvoicingDate) = MONTH(GETDATE()) AND DAY(I.ExpectedInvoicingDate) <= DAY(GETDATE())))
                                )
                            )
                    )
                    SELECT
                        SUM(CASE WHEN YEAR(jour) = YEAR(GETDATE()) THEN AmountEurTM ELSE 0 END) AS ca_n,
                        SUM(CASE WHEN YEAR(jour) = YEAR(DATEADD(YEAR, -1, GETDATE())) THEN AmountEurTM ELSE 0 END) AS ca_n_1,
                        ROUND(
                            CASE
                                WHEN SUM(CASE WHEN YEAR(jour) = YEAR(DATEADD(YEAR, -1, GETDATE())) THEN AmountEurTM ELSE 0 END) = 0
                                    THEN NULL
                                ELSE
                                    (SUM(CASE WHEN YEAR(jour) = YEAR(GETDATE()) THEN AmountEurTM ELSE 0 END)
                                    - SUM(CASE WHEN YEAR(jour) = YEAR(DATEADD(YEAR, -1, GETDATE())) THEN AmountEurTM ELSE 0 END))
                                    / SUM(CASE WHEN YEAR(jour) = YEAR(DATEADD(YEAR, -1, GETDATE())) THEN AmountEurTM ELSE 0 END) * 100
                            END, 2
                        ) AS variation_pourcent
                    FROM ventes_annuelles;";

            $dataGraph = $this->mssqlManager->executeQuery($query);
            return $dataGraph;

        } catch (\Exception $e) {
            $this->graphMailer->notifyError('❌ OGIER Erreur Dashboard : Récupération de données Conversion Rate', $e);
            $this->logger->error('Error Récupération de données Conversion Rate', ['exception' => $e]);
        }
    }

    public function getSalesComparaisonByMonths(): array
    {
        try {
            $query = "WITH ventes_par_mois AS (
                        SELECT
                            YEAR(I.ExpectedInvoicingDate) AS annee,
                            MONTH(I.ExpectedInvoicingDate) AS mois,
                            SUM(I.AmountEurTM) AS ca_mensuel
                        FROM BI.DWH.F_Invoices I
                        WHERE
                            I.IsBohPerimeter_Product = 1
                            AND I.IsBohPerimeter_IR = 1
                            AND I.DocumentType = 'INVOICE'
                            AND YEAR(I.ExpectedInvoicingDate) IN (YEAR(GETDATE()), YEAR(DATEADD(YEAR, -1, GETDATE())))
                        GROUP BY YEAR(I.ExpectedInvoicingDate), MONTH(I.ExpectedInvoicingDate)
                    )
                    SELECT
                        mois,
                        MAX(CASE WHEN annee = YEAR(GETDATE()) THEN ca_mensuel ELSE 0 END) AS ca_n,
                        MAX(CASE WHEN annee = YEAR(DATEADD(YEAR, -1, GETDATE())) THEN ca_mensuel ELSE 0 END) AS ca_n_1
                    FROM ventes_par_mois
                    GROUP BY mois
                    ORDER BY mois;";

            $dataGraph = $this->mssqlManager->executeQuery($query);
            return $dataGraph;

        } catch (\Exception $e) {
            $this->graphMailer->notifyError('❌ OGIER Erreur Dashboard : Récupération de données Conversion Rate', $e);
            $this->logger->error('Error Récupération de données Conversion Rate', ['exception' => $e]);
        }
    }

    public function getSalesComparaisonCurrentMonthByDay(): array
    {
        try {
            $query = "WITH jours_mois_complet AS (
                        SELECT TOP (DAY(EOMONTH(GETDATE())))
                            ROW_NUMBER() OVER (ORDER BY (SELECT NULL)) AS jour
                        FROM sys.all_objects
                    ),
                    ventes_journalieres AS (
                        SELECT
                            DAY(I.ExpectedInvoicingDate) AS jour,
                            YEAR(I.ExpectedInvoicingDate) AS annee,
                            SUM(I.AmountEurTM) AS ca_jour
                        FROM BI.DWH.F_Invoices I
                        WHERE
                            MONTH(I.ExpectedInvoicingDate) = MONTH(GETDATE())
                            AND YEAR(I.ExpectedInvoicingDate) IN (YEAR(GETDATE()), YEAR(DATEADD(YEAR, -1, GETDATE())))
                            AND (
                                (YEAR(I.ExpectedInvoicingDate) = YEAR(GETDATE()) AND DAY(I.ExpectedInvoicingDate) <= DAY(GETDATE()))
                                OR YEAR(I.ExpectedInvoicingDate) = YEAR(DATEADD(YEAR, -1, GETDATE()))
                            )
                            AND I.IsBohPerimeter_Product = 1
                            AND I.IsBohPerimeter_IR = 1
                            AND I.DocumentType = 'INVOICE'
                        GROUP BY DAY(I.ExpectedInvoicingDate), YEAR(I.ExpectedInvoicingDate)
                    ),
                    fusion AS (
                        SELECT
                            j.jour,
                            ISNULL(MAX(CASE WHEN v.annee = YEAR(GETDATE()) THEN v.ca_jour END), 0) AS ca_n,
                            ISNULL(MAX(CASE WHEN v.annee = YEAR(DATEADD(YEAR, -1, GETDATE())) THEN v.ca_jour END), 0) AS ca_n_1
                        FROM jours_mois_complet j
                        LEFT JOIN ventes_journalieres v ON j.jour = v.jour
                        GROUP BY j.jour
                    )
                    SELECT *
                    FROM fusion
                    ORDER BY jour;";

            return $this->mssqlManager->executeQuery($query);

        } catch (\Exception $e) {
            $this->graphMailer->notifyError('❌ OGIER Erreur Dashboard : CA jour mois courant', $e);
            $this->logger->error('Erreur CA jour mois courant', ['exception' => $e]);
            return [];
        }
    }

    public function getSalesComparaisonCurrentMonth(): array
    {
        try {
            $query = "WITH ventes_filtrees AS (
                        SELECT
                            CAST(I.ExpectedInvoicingDate AS DATE) AS jour,
                            I.AmountEurTM
                        FROM BI.DWH.F_Invoices I
                        WHERE
                            YEAR(I.ExpectedInvoicingDate) IN (YEAR(GETDATE()), YEAR(DATEADD(YEAR, -1, GETDATE())))
                            AND MONTH(I.ExpectedInvoicingDate) = MONTH(GETDATE())
                            AND (
                                (YEAR(I.ExpectedInvoicingDate) = YEAR(GETDATE()) AND DAY(I.ExpectedInvoicingDate) <= DAY(GETDATE()))
                                OR YEAR(I.ExpectedInvoicingDate) = YEAR(DATEADD(YEAR, -1, GETDATE()))
                            )
                            AND I.IsBohPerimeter_Product = 1
                            AND I.IsBohPerimeter_IR = 1
                            AND I.DocumentType = 'INVOICE'
                    )
                    SELECT
                        SUM(CASE WHEN YEAR(jour) = YEAR(GETDATE()) THEN AmountEurTM ELSE 0 END) AS ca_n,
                        SUM(CASE WHEN YEAR(jour) = YEAR(DATEADD(YEAR, -1, GETDATE())) THEN AmountEurTM ELSE 0 END) AS ca_n_1,
                        ROUND(
                            CASE
                                WHEN SUM(CASE WHEN YEAR(jour) = YEAR(DATEADD(YEAR, -1, GETDATE())) THEN AmountEurTM ELSE 0 END) = 0
                                    THEN NULL
                                ELSE
                                    (SUM(CASE WHEN YEAR(jour) = YEAR(GETDATE()) THEN AmountEurTM ELSE 0 END)
                                    - SUM(CASE WHEN YEAR(jour) = YEAR(DATEADD(YEAR, -1, GETDATE())) THEN AmountEurTM ELSE 0 END))
                                    / SUM(CASE WHEN YEAR(jour) = YEAR(DATEADD(YEAR, -1, GETDATE())) THEN AmountEurTM ELSE 0 END) * 100
                            END, 2
                        ) AS variation_pourcent
                    FROM ventes_filtrees;";

            return $this->mssqlManager->executeQuery($query);

        } catch (\Exception $e) {
            $this->graphMailer->notifyError('❌ OGIER Erreur Dashboard : CA mois courant', $e);
            $this->logger->error('Erreur CA mois courant', ['exception' => $e]);
            return [];
        }
    }

    public function getTopClients(): array
    {
        try {
            $query = "
            SELECT TOP 10
                I.CustomerNo,
                C.Name AS CustomerName,
                SUM(I.AmountEurTM) AS TotalCA_EUR
            FROM BI.DWH.F_Invoices I
            LEFT JOIN BI.DWH.D_Customer C
                ON C.Code = I.CustomerNo AND C.CompanyCode = I.CompanyCode
            WHERE
                I.IsBohPerimeter_Product = 1
                AND I.IsBohPerimeter_IR = 1
                AND I.DocumentType = 'INVOICE'
                AND YEAR(I.ExpectedInvoicingDate) = YEAR(GETDATE())
            GROUP BY I.CustomerNo, C.Name
            ORDER BY TotalCA_EUR DESC;
        ";

            return $this->mssqlManager->executeQuery($query);

        } catch (\Exception $e) {
            $this->graphMailer->notifyError('❌ LCS Erreur Dashboard : Top Clients', $e);
            $this->logger->error(' LCS Erreur Top Clients', ['exception' => $e]);
            return [];
        }
    }

    public function getTopCompanySales(): array
    {
        try {
            $query = "
            SELECT TOP 10
                I.CompanyCode,
                SUM(I.AmountEurTM) AS TotalSales
            FROM BI.DWH.F_Invoices I
            WHERE
                I.IsBohPerimeter_Product = 1
                AND I.IsBohPerimeter_IR = 1
                AND I.DocumentType = 'INVOICE'
                AND YEAR(I.ExpectedInvoicingDate) = YEAR(GETDATE())
            GROUP BY I.CompanyCode
            ORDER BY TotalSales DESC
        ";

            return $this->mssqlManager->executeQuery($query);
        } catch (\Exception $e) {
            $this->graphMailer->notifyError('❌ LCS Erreur Dashboard : Top sociétés CA', $e);
            $this->logger->error('LCS Erreur Top sociétés CA', ['exception' => $e]);
            return [];
        }
    }

    public function getTopProductsBySales(): array
    {
        try {
            $query = "SELECT TOP 10
                        I.ItemNo,
                        IT.Description AS ItemDescription,
                        SUM(I.AmountEurTM) AS TotalSales
                    FROM BI.DWH.F_Invoices I
                    LEFT JOIN BI.DWH.D_Item IT ON IT.ItemNo = I.ItemNo
                    WHERE
                        I.IsBohPerimeter_Product = 1
                        AND I.IsBohPerimeter_IR = 1
                        AND I.DocumentType = 'INVOICE'
                        AND YEAR(I.ExpectedInvoicingDate) = YEAR(GETDATE())
                    GROUP BY I.ItemNo, IT.Description
                    ORDER BY TotalSales DESC;"; // Mets la requête ici

            return $this->mssqlManager->executeQuery($query);

        } catch (\Exception $e) {
            $this->graphMailer->notifyError('❌ Erreur Dashboard : Top Produits Ventes', $e);
            $this->logger->error('Erreur Top Produits Ventes', ['exception' => $e]);
            return [];
        }
    }

    public function getMonthlySalesEvolutionLast5Years(): array
    {
        try {
            $query = "WITH ventes_par_mois AS (
                SELECT
                    YEAR(I.ExpectedInvoicingDate) AS annee,
                    MONTH(I.ExpectedInvoicingDate) AS mois,
                    SUM(I.AmountEurTM) AS ca_mensuel
                FROM BI.DWH.F_Invoices I
                WHERE
                    I.IsBohPerimeter_Product = 1
                    AND I.IsBohPerimeter_IR = 1
                    AND I.DocumentType = 'INVOICE'
                    AND YEAR(I.ExpectedInvoicingDate) BETWEEN YEAR(GETDATE()) - 4 AND YEAR(GETDATE())
                GROUP BY YEAR(I.ExpectedInvoicingDate), MONTH(I.ExpectedInvoicingDate)
            )
            SELECT annee, mois, ca_mensuel
            FROM ventes_par_mois
            ORDER BY annee, mois;";

            return $this->mssqlManager->executeQuery($query);
        } catch (\Exception $e) {
            $this->graphMailer->notifyError('❌ Erreur Dashboard : Évolution 5 ans', $e);
            $this->logger->error('Erreur CA mensuel 5 ans', ['exception' => $e]);
            return [];
        }
    }
}
