<?php

namespace App\Service\Dashboards;

use App\Factory\MssqlManagerFactory;
use App\Service\Tools\GraphMailer;
use App\Service\Tools\MssqlManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class SellOutDashboard
{
    private MssqlManager $mssql;
    private bool $isDev;

    public function __construct(
        private MssqlManagerFactory $mssqlManagerFactory,
        private LoggerInterface $logger,
        private GraphMailer $graphMailer,
        #[Autowire('%db.lcs_sei%')]
        string $dbLcsSei,
        #[Autowire('%kernel.environment%')]
        string $environment,
    ) {
        $this->mssql  = $this->mssqlManagerFactory->create($dbLcsSei);
        $this->isDev  = ($environment === 'dev');
    }

    private function table(string $name): string
    {
        return 'MASTER_TABLES.' . $name . ($this->isDev ? '_DEV' : '');
    }

    // ─── Refresh ────────────────────────────────────────────────────────────

    public function refreshWeekly(): int
    {
        try {
            $table = $this->table('INTRANET_SELLOUT_WEEKLY');
            $this->mssql->executeDelete("DELETE FROM {$table}");

            return $this->mssql->insertData("
                INSERT INTO {$table} (week_code, annee, semaine, sourcename, salesamt, salesqty)
                SELECT week_code, annee, semaine, sourcename, SUM(salesamt), SUM(salesqty)
                FROM (
                    SELECT
                        WEEK_CODE                                                        AS week_code,
                        CAST(LEFT(CAST(WEEK_CODE AS VARCHAR(6)), 4) AS INT)              AS annee,
                        CAST(RIGHT(CAST(WEEK_CODE AS VARCHAR(6)), 2) AS INT)             AS semaine,
                        SOURCENAME                                                       AS sourcename,
                        ISNULL(SALESAMT, 0)                                              AS salesamt,
                        ISNULL(SALESQTY, 0)                                              AS salesqty
                    FROM SEI_X3_LCS.LCS_SELLOUT_SALES
                    WHERE SOURCENAME IN ('SPORT2000', 'INTERSPORT')
                      AND WEEK_CODE IS NOT NULL
                    AND CUSTOMER_ID IS NOT NULL
                ) t
                GROUP BY week_code, annee, semaine, sourcename
            ");
        } catch (\Exception $e) {
            $this->graphMailer->notifyError('❌ Erreur refresh INTRANET_SELLOUT_WEEKLY', $e);
            $this->logger->error('Erreur refresh sellout weekly', ['exception' => $e]);
            return 0;
        }
    }

    public function refreshFamily(): int
    {
        try {
            $table = $this->table('INTRANET_SELLOUT_FAMILY');
            $this->mssql->executeDelete("DELETE FROM {$table}");

            return $this->mssql->insertData("
                INSERT INTO {$table} (annee, sourcename, famille, salesamt, salesqty)
                SELECT annee, sourcename, famille, SUM(salesamt), SUM(salesqty)
                FROM (
                    SELECT
                        CAST(LEFT(CAST(S.WEEK_CODE AS VARCHAR(6)), 4) AS INT) AS annee,
                        S.SOURCENAME                                           AS sourcename,
                        ISNULL(ITM.TCLCOD_0, 'INCONNU')                       AS famille,
                        ISNULL(S.SALESAMT, 0)                                  AS salesamt,
                        ISNULL(S.SALESQTY, 0)                                  AS salesqty
                    FROM SEI_X3_LCS.LCS_SELLOUT_SALES S
                    LEFT JOIN X3_LCS.ITMMASTER ITM
                        ON  LEFT(ITM.ITMREF_0, CHARINDEX('_', ITM.ITMREF_0 + '_') - 1)
                            = S.ITEMN
                        AND SUBSTRING(ITM.ITMREF_0, CHARINDEX('_', ITM.ITMREF_0 + '_') + 1, LEN(ITM.ITMREF_0))
                            = S.VARIANTCODE
                    WHERE S.SOURCENAME IN ('SPORT2000', 'INTERSPORT')
                      AND S.WEEK_CODE IS NOT NULL
                      AND S.CUSTOMER_ID IS NOT NULL
                ) t
                WHERE annee = YEAR(GETDATE())
                  AND famille IN ('FTW', 'HDW', 'APL')
                GROUP BY annee, sourcename, famille
            ");
        } catch (\Exception $e) {
            $this->graphMailer->notifyError('❌ Erreur refresh INTRANET_SELLOUT_FAMILY', $e);
            $this->logger->error('Erreur refresh sellout family', ['exception' => $e]);
            return 0;
        }
    }

    public function refreshTopItems(): int
    {
        try {
            $table = $this->table('INTRANET_SELLOUT_TOP_ITEMS');
            $this->mssql->executeDelete("DELETE FROM {$table}");

            return $this->mssql->insertData("
                INSERT INTO {$table} (annee, sourcename, itemn, itemdes, salesamt, salesqty)
                SELECT annee, sourcename, itemn, itemdes, SUM(salesamt), SUM(salesqty)
                FROM (
                    SELECT
                        CAST(LEFT(CAST(S.WEEK_CODE AS VARCHAR(6)), 4) AS INT) AS annee,
                        S.SOURCENAME                                           AS sourcename,
                        S.ITEMN                                                AS itemn,
                        ISNULL(ITM.ITMDES1_0, '')                              AS itemdes,
                        ISNULL(S.SALESAMT, 0)                                  AS salesamt,
                        ISNULL(S.SALESQTY, 0)                                  AS salesqty
                    FROM SEI_X3_LCS.LCS_SELLOUT_SALES S
                    LEFT JOIN X3_LCS.ITMMASTER ITM
                        ON  LEFT(ITM.ITMREF_0, CHARINDEX('_', ITM.ITMREF_0 + '_') - 1)
                            = S.ITEMN
                        AND SUBSTRING(ITM.ITMREF_0, CHARINDEX('_', ITM.ITMREF_0 + '_') + 1, LEN(ITM.ITMREF_0))
                            = S.VARIANTCODE
                    WHERE S.SOURCENAME IN ('SPORT2000', 'INTERSPORT')
                      AND S.WEEK_CODE IS NOT NULL
                      AND S.ITEMN IS NOT NULL
                      AND S.ITEMN <> ''
                      AND S.CUSTOMER_ID IS NOT NULL
                ) t
                WHERE annee = YEAR(GETDATE())
                GROUP BY annee, sourcename, itemn, itemdes
            ");
        } catch (\Exception $e) {
            $this->graphMailer->notifyError('❌ Erreur refresh INTRANET_SELLOUT_TOP_ITEMS', $e);
            $this->logger->error('Erreur refresh sellout top items', ['exception' => $e]);
            return 0;
        }
    }

    public function refreshBacklogSellOut(): int
    {
        try {
            $table = $this->table('INTRANET_SELLOUT_BACKLOG');
            $this->mssql->executeDelete("DELETE FROM {$table}");

            // Filtre sur INTERSPORT et SPORT 2000 via le groupe client X3 (ZGRPCOD_0).
            return $this->mssql->insertData("
                INSERT INTO {$table} (retard, quantite, montant_ht_eur, date_refresh)
                SELECT
                    retard,
                    SUM(quantite)    AS quantite,
                    SUM(montant_eur) AS montant_ht_eur,
                    GETDATE()
                FROM (
                    SELECT
                        CASE
                            WHEN SOQ.DEMDLVDAT_0 <= EOMONTH(GETDATE())                    THEN 'MOIS'
                            WHEN SOQ.DEMDLVDAT_0 <= EOMONTH(DATEADD(MONTH, 1, GETDATE())) THEN 'MOIS + 1'
                            ELSE '>MOIS + 2'
                        END AS retard,
                        CAST(ROUND(SOQ.QTY_0 - SOQ.DLVQTY_0 - SOQ.ODLQTY_0, 0) AS INT) AS quantite,
                        CASE
                            WHEN SOH.CUR_0 = 'EUR'
                                THEN SOP.NETPRINOT_0 * (SOQ.QTY_0 - SOQ.DLVQTY_0 - SOQ.ODLQTY_0)
                            ELSE (SOP.NETPRINOT_0 * (SOQ.QTY_0 - SOQ.DLVQTY_0 - SOQ.ODLQTY_0))
                                 / NULLIF(TC.CHGRAT_0, 0)
                        END AS montant_eur
                    FROM X3_LCS.SORDERQ SOQ
                    INNER JOIN X3_LCS.SORDER  SOH ON SOQ.SOHNUM_0 = SOH.SOHNUM_0
                    INNER JOIN X3_LCS.SORDERP SOP ON SOQ.SOHNUM_0 = SOP.SOHNUM_0
                                                  AND SOQ.ITMREF_0  = SOP.ITMREF_0
                                                  AND SOQ.SOPLIN_0  = SOP.SOPLIN_0
                    INNER JOIN X3_LCS.BPCUSTOMER BPC ON SOH.BPCORD_0 = BPC.BPCNUM_0
                    LEFT JOIN (
                        SELECT CURDEN_0, CHGRAT_0
                        FROM X3_LCS.TABCHANGE
                        WHERE CUR_0       = 'EUR'
                          AND CHGTYP_0    = 1
                          AND CHGSTRDAT_0 = (
                              SELECT MAX(CHGSTRDAT_0) FROM X3_LCS.TABCHANGE
                              WHERE CUR_0 = 'EUR' AND CHGTYP_0 = 1
                          )
                    ) TC ON TC.CURDEN_0 = SOH.CUR_0
                    WHERE SOQ.SOQSTA_0 <> 3
                      AND (SOQ.QTY_0 - SOQ.DLVQTY_0 - SOQ.ODLQTY_0) > 0
                      AND BPC.BCGCOD_0 <> 'INTER'
                      AND BPC.ZGRPCOD_0 IN ('081', '043')
                ) t
                GROUP BY retard
            ");
        } catch (\Exception $e) {
            $this->graphMailer->notifyError('❌ Erreur refresh INTRANET_SELLOUT_BACKLOG', $e);
            $this->logger->error('Erreur refresh sellout backlog', ['exception' => $e]);
            return 0;
        }
    }

    public function refreshAll(): array
    {
        return [
            'Sell-out hebdomadaire' => $this->refreshWeekly(),
            'Sell-out famille'      => $this->refreshFamily(),
            'Sell-out top articles' => $this->refreshTopItems(),
            'Sell-out backlog'      => $this->refreshBacklogSellOut(),
        ];
    }

    // ─── Lecture ────────────────────────────────────────────────────────────

    public function getVentesAnnuelles(): array
    {
        try {
            $table = $this->table('INTRANET_SELLOUT_WEEKLY');

            return $this->mssql->executeQuery("
                SELECT annee, semaine, SUM(salesqty) AS salesqty
                FROM {$table}
                WHERE semaine <= (
                    SELECT MAX(semaine) FROM {$table} WHERE annee = YEAR(GETDATE())
                )
                  AND annee IN (YEAR(GETDATE()), YEAR(GETDATE()) - 1)
                GROUP BY annee, semaine
                ORDER BY annee, semaine
            ");
        } catch (\Exception $e) {
            $this->graphMailer->notifyError('❌ Erreur Dashboard Sell-out : ventes annuelles', $e);
            $this->logger->error('Erreur sellout ventes annuelles', ['exception' => $e]);
            return [];
        }
    }

    public function getFullYearN1Total(): float
    {
        try {
            $table  = $this->table('INTRANET_SELLOUT_WEEKLY');
            $result = $this->mssql->executeQuery("
                SELECT SUM(salesqty) AS total
                FROM {$table}
                WHERE annee = YEAR(GETDATE()) - 1
            ");
            return (float) ($result[0]->total ?? 0);
        } catch (\Exception $e) {
            $this->graphMailer->notifyError('❌ Erreur Dashboard Sell-out : total N-1 annuel', $e);
            $this->logger->error('Erreur sellout total n1 annuel', ['exception' => $e]);
            return 0.0;
        }
    }

    public function getLastWeekNumber(): int
    {
        try {
            $table  = $this->table('INTRANET_SELLOUT_WEEKLY');
            $result = $this->mssql->executeQuery(
                "SELECT MAX(semaine) AS last_week FROM {$table} WHERE annee = YEAR(GETDATE())"
            );
            return (int) ($result[0]->last_week ?? 0);
        } catch (\Exception $e) {
            $this->logger->error('Erreur sellout last week', ['exception' => $e]);
            return 0;
        }
    }

    public function getVentesSemaine(): array
    {
        try {
            $table = $this->table('INTRANET_SELLOUT_WEEKLY');

            return $this->mssql->executeQuery("
                SELECT annee, sourcename, SUM(salesamt) AS salesamt, SUM(salesqty) AS salesqty
                FROM {$table}
                WHERE semaine = (
                    SELECT MAX(semaine) FROM {$table} WHERE annee = YEAR(GETDATE())
                )
                  AND annee IN (YEAR(GETDATE()), YEAR(GETDATE()) - 1)
                GROUP BY annee, sourcename
            ");
        } catch (\Exception $e) {
            $this->graphMailer->notifyError('❌ Erreur Dashboard Sell-out : ventes semaine', $e);
            $this->logger->error('Erreur sellout ventes semaine', ['exception' => $e]);
            return [];
        }
    }

    public function getTopClients(): array
    {
        try {
            $table = $this->table('INTRANET_SELLOUT_WEEKLY');

            return $this->mssql->executeQuery("
                SELECT sourcename, SUM(salesqty) AS salesqty
                FROM {$table}
                WHERE annee = YEAR(GETDATE())
                GROUP BY sourcename
                ORDER BY salesqty DESC
            ");
        } catch (\Exception $e) {
            $this->graphMailer->notifyError('❌ Erreur Dashboard Sell-out : top clients', $e);
            $this->logger->error('Erreur sellout top clients', ['exception' => $e]);
            return [];
        }
    }

    public function getFamilyCa(): array
    {
        try {
            $table = $this->table('INTRANET_SELLOUT_FAMILY');

            return $this->mssql->executeQuery("
                SELECT famille, SUM(salesqty) AS salesqty
                FROM {$table}
                WHERE annee = YEAR(GETDATE())
                GROUP BY famille
                ORDER BY salesqty DESC
            ");
        } catch (\Exception $e) {
            $this->graphMailer->notifyError('❌ Erreur Dashboard Sell-out : famille CA', $e);
            $this->logger->error('Erreur sellout famille CA', ['exception' => $e]);
            return [];
        }
    }

    public function getTopProduits(?string $family = null): array
    {
        try {
            $table = $this->table('INTRANET_SELLOUT_TOP_ITEMS');
            $familyFilter = '';
            if ($family !== null) {
                $familyFilter = "AND EXISTS (
                    SELECT 1 FROM X3_LCS.ITMMASTER ITM
                    WHERE LEFT(ITM.ITMREF_0, CHARINDEX('_', ITM.ITMREF_0 + '_') - 1) = t.itemn
                      AND ITM.TCLCOD_0 = '{$family}'
                )";
            }

            return $this->mssql->executeQuery("
                SELECT TOP 5
                    t.itemn,
                    MAX(t.itemdes)  AS itemdes,
                    SUM(t.salesqty) AS salesqty
                FROM {$table} t
                WHERE t.annee = YEAR(GETDATE())
                {$familyFilter}
                GROUP BY t.itemn
                ORDER BY SUM(t.salesqty) DESC
            ");
        } catch (\Exception $e) {
            $this->graphMailer->notifyError('❌ Erreur Dashboard Sell-out : top produits', $e);
            $this->logger->error('Erreur sellout top produits', ['exception' => $e]);
            return [];
        }
    }

    public function getBacklogSellOut(): array
    {
        try {
            $table = $this->table('INTRANET_SELLOUT_BACKLOG');

            $results = $this->mssql->executeQuery("
                SELECT
                    retard,
                    SUM(quantite)       AS quantite,
                    SUM(montant_ht_eur) AS montant
                FROM {$table}
                GROUP BY retard
                ORDER BY
                    CASE retard
                        WHEN 'MOIS'   THEN 1
                        WHEN 'MOIS + 1' THEN 2
                        ELSE 3
                    END
            ");

            $labels = []; $values = []; $quantities = [];
            foreach ($results as $row) {
                $labels[]     = $row->retard;
                $values[]     = (float) $row->montant;
                $quantities[] = (float) $row->quantite;
            }

            return ['labels' => $labels, 'values' => $values, 'quantities' => $quantities];
        } catch (\Exception $e) {
            $this->graphMailer->notifyError('❌ Erreur Dashboard Sell-out : backlog', $e);
            $this->logger->error('Erreur sellout backlog', ['exception' => $e]);
            return ['labels' => [], 'values' => [], 'quantities' => []];
        }
    }

    public function getEvolution12Semaines(): array
    {
        try {
            $table = $this->table('INTRANET_SELLOUT_WEEKLY');

            return $this->mssql->executeQuery("
                WITH last_week AS (
                    SELECT MAX(semaine) AS max_sem
                    FROM {$table}
                    WHERE annee = YEAR(GETDATE())
                )
                SELECT annee, semaine, SUM(salesqty) AS salesqty
                FROM {$table}
                CROSS JOIN last_week
                WHERE semaine BETWEEN (last_week.max_sem - 11) AND last_week.max_sem
                  AND annee IN (YEAR(GETDATE()), YEAR(GETDATE()) - 1)
                GROUP BY annee, semaine
                ORDER BY annee, semaine
            ");
        } catch (\Exception $e) {
            $this->graphMailer->notifyError('❌ Erreur Dashboard Sell-out : évolution 12 semaines', $e);
            $this->logger->error('Erreur sellout évolution 12 semaines', ['exception' => $e]);
            return [];
        }
    }
}
