<?php

namespace App\Service\Dashboards;

use App\Factory\MssqlManagerFactory;
use App\Service\Tools\GraphMailer;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Psr\Log\LoggerInterface;
use App\Service\Tools\MssqlManager;

class MainDashboard
{
    private const BOUTIQUE_GROUPS = [
        'propres'  => ['2319', '10700'],
        'affilies' => ['20425', '20966', '7669', '7754'],
        'outlet'   => ['11026', '11159', '8606', '9735', '9822'],
    ];

    private MssqlManager $mssqlMade2design;
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
        $this->mssqlMade2design = $this->mssqlManagerFactory->create($dbLcsSei);
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
        string  $network,
        string  $mainNetworkCol      = 'mainnetwork',
        string  $reportingDimCol     = 'reportingdimension',
        ?string $distributionChanCol = 'distributionchannel'
    ): string {
        $excludeWeb = $distributionChanCol !== null
            ? " AND {$distributionChanCol} <> 'KEY ACCOUNT WEB'"
            : '';

        $includeWeb = $distributionChanCol !== null
            ? " OR ({$reportingDimCol} LIKE '%WHOLESALE%' AND {$distributionChanCol} = 'KEY ACCOUNT WEB')"
            : '';

        return match ($this->normalizeNetworkFilter($network)) {
            'boutique'      => " AND {$mainNetworkCol} IN ('RETAIL FO', 'CLEARANCE', 'RETAIL CS', 'CONCEPT STORE', 'FACTORY OUTLET')",
            'ecom'          => " AND ({$mainNetworkCol} IN ('RETAIL MARKET PLACE', 'RETAIL ESHOP', 'E BUSINESS DIRECT', 'E BUSINESS MARKET PL'){$includeWeb})",
            'wholesale_fr'  => " AND {$reportingDimCol} = 'WHOLESALE FRANCE'{$excludeWeb}",
            'wholesale_eu'  => " AND {$reportingDimCol} = 'WHOLESALE EUROPE'{$excludeWeb}",
            'wholesale_int' => " AND {$reportingDimCol} = 'WHOLESALE INTERNATIO'{$excludeWeb}",
            default         => '',
        };
    }

    public function getSalesComparaisonYears(string $network = 'global'): array
    {
        try {
            $networkWhere = $this->buildNetworkWhereClause($network);
            $table        = $this->table('INTRANET_SALES_DAILY');

            $query = "SELECT
                    -- YTD N : jusqu'à aujourd'hui
                    SUM(CASE WHEN annee = YEAR(GETDATE())
                              AND (mois < MONTH(GETDATE()) OR (mois = MONTH(GETDATE()) AND jour <= DAY(GETDATE())))
                             THEN ca ELSE 0 END) AS ca_n,

                    -- YTD N-1 : même période l'an dernier
                    SUM(CASE WHEN annee = YEAR(GETDATE()) - 1
                              AND (mois < MONTH(GETDATE()) OR (mois = MONTH(GETDATE()) AND jour <= DAY(GETDATE())))
                             THEN ca ELSE 0 END) AS ca_ytd_n1,

                    -- Total N-1 : année entière
                    SUM(CASE WHEN annee = YEAR(GETDATE()) - 1 THEN ca ELSE 0 END) AS ca_n_1,

                    ROUND(
                        CASE
                            WHEN SUM(CASE WHEN annee = YEAR(GETDATE()) - 1
                                          AND (mois < MONTH(GETDATE()) OR (mois = MONTH(GETDATE()) AND jour <= DAY(GETDATE())))
                                         THEN ca ELSE 0 END) = 0
                                THEN NULL
                            ELSE
                                (
                                    SUM(CASE WHEN annee = YEAR(GETDATE())
                                              AND (mois < MONTH(GETDATE()) OR (mois = MONTH(GETDATE()) AND jour <= DAY(GETDATE())))
                                             THEN ca ELSE 0 END)
                                    -
                                    SUM(CASE WHEN annee = YEAR(GETDATE()) - 1
                                              AND (mois < MONTH(GETDATE()) OR (mois = MONTH(GETDATE()) AND jour <= DAY(GETDATE())))
                                             THEN ca ELSE 0 END)
                                )
                                /
                                SUM(CASE WHEN annee = YEAR(GETDATE()) - 1
                                          AND (mois < MONTH(GETDATE()) OR (mois = MONTH(GETDATE()) AND jour <= DAY(GETDATE())))
                                         THEN ca ELSE 0 END)
                                * 100
                        END
                    , 2) AS variation_pourcent

                FROM {$table}
                WHERE 1=1
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
            $networkWhere = $this->buildNetworkWhereClause($network, 'd.mainnetwork', 'd.reportingdimension', 'd.distributionchannel');
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

            $nameExpr = $this->normalizeNetworkFilter($network) === 'ecom'
                ? "CASE WHEN mainnetwork = 'E BUSINESS DIRECT' THEN billtoname ELSE customer_name END"
                : 'billtoname';

            $query = "
            SELECT TOP 10
                {$nameExpr} AS CustomerName,
                SUM(ca) AS TotalCA_EUR
            FROM {$table}
            WHERE annee = YEAR(GETDATE())
            {$networkWhere}
            GROUP BY {$nameExpr}
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

    public function getTopProductsBySales(string $network = 'global', ?string $familyCode = null): array
    {
        try {
            $networkWhere = $this->buildNetworkWhereClause($network);
            $table        = $this->table('INTRANET_SALES_AGG_YEAR');

            $familyWhere = '';
            if ($familyCode !== null) {
                $allowed = ['FTW', 'APL', 'HDW'];
                if (in_array($familyCode, $allowed, true)) {
                    $familyWhere = " AND item_family_code = '{$familyCode}'";
                }
            }

            $query = "SELECT TOP 5
                    item_no as ItemNo,
                    item_description as ItemDescription,
                    SUM(ca) AS TotalSales
                FROM {$table}
                WHERE annee = YEAR(GETDATE())
                {$networkWhere}
                {$familyWhere}
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

    public function getBacklogClientDonut(string $network = 'global', string $mode = 'mois'): array
    {
        try {
            $normalized = $this->normalizeNetworkFilter($network);
            if (in_array($normalized, ['boutique', 'ecom'], true)) {
                return ['labels' => [], 'values' => [], 'quantities' => []];
            }

            $table        = $this->table('INTRANET_BACKLOG_CLI');
            $networkWhere = $normalized === 'wholesale_int'
                ? " AND reportingdimension = 'WHOLESALE INTERNATIONAL'"
                : $this->buildNetworkWhereClause($network, 'mainnetwork', 'reportingdimension', null);

            // Requête X3 pour COUNT(DISTINCT client) exact par bucket
            $x3NetworkWhere = $normalized === 'wholesale_int'
                ? " AND ATX.TEXTE_0 = 'WHOLESALE INTERNATIONAL'"
                : '';

            $retardCase = "
                CASE
                    WHEN SOQ.DEMDLVDAT_0 < DATEADD(MONTH, -2, DATEFROMPARTS(YEAR(GETDATE()), MONTH(GETDATE()), 1)) THEN 'MOIS - 3 et avant'
                    WHEN SOQ.DEMDLVDAT_0 < DATEADD(MONTH, -1, DATEFROMPARTS(YEAR(GETDATE()), MONTH(GETDATE()), 1)) THEN 'MOIS - 2'
                    WHEN SOQ.DEMDLVDAT_0 < DATEFROMPARTS(YEAR(GETDATE()), MONTH(GETDATE()), 1)                     THEN 'MOIS - 1'
                    WHEN SOQ.DEMDLVDAT_0 <= EOMONTH(GETDATE())                                                     THEN 'MOIS'
                    WHEN SOQ.DEMDLVDAT_0 <= EOMONTH(DATEADD(MONTH, 1, GETDATE()))                                  THEN 'MOIS + 1'
                    ELSE 'MOIS + 2 et après'
                END";

            if ($mode === 'collection') {
                $x3GroupField = "CASE WHEN SOQ.YCOLLECT_0 <= '2025-02-FW' THEN '2025-02-FW et precedentes' ELSE SOQ.YCOLLECT_0 END";
                $x3OrderBy    = "ORDER BY {$x3GroupField} DESC";
            } else {
                $x3GroupField = $retardCase;
                $x3OrderBy    = "ORDER BY CASE ({$retardCase}) WHEN 'MOIS - 3 et avant' THEN 1 WHEN 'MOIS - 2' THEN 2 WHEN 'MOIS - 1' THEN 3 WHEN 'MOIS' THEN 4 WHEN 'MOIS + 1' THEN 5 ELSE 6 END";
            }

            $x3Query = "
            SELECT
                {$x3GroupField} AS label,
                COUNT(DISTINCT SOH.BPCORD_0) AS nb_clients
            FROM X3_LCS.SORDERQ SOQ
            INNER JOIN X3_LCS.SORDER SOH ON SOQ.SOHNUM_0 = SOH.SOHNUM_0
            INNER JOIN X3_LCS.BPCUSTOMER BPC ON SOH.BPCORD_0 = BPC.BPCNUM_0
            LEFT JOIN X3_LCS.ATEXTRA ATX ON ATX.IDENT2_0 = BPC.TSCCOD_2
                                         AND ATX.CODFIC_0 = 'ATABDIV'
                                         AND ATX.LANGUE_0 = 'FRA'
                                         AND ATX.ZONE_0   = 'LNGDES'
                                         AND ATX.IDENT1_0 = '32'
            WHERE SOQ.SOQSTA_0 <> 3
              AND (SOQ.QTY_0 - SOQ.DLVQTY_0 - SOQ.ODLQTY_0) > 0
              AND BPC.BCGCOD_0 <> 'INTER'
              AND SOH.ZSOHVALSTA_0 <> 3
              {$x3NetworkWhere}
            GROUP BY {$x3GroupField}
            {$x3OrderBy}";

            $x3Results    = $this->mssqlMade2design->executeQuery($x3Query);
            $nbClientsMap = [];
            foreach ($x3Results as $r) {
                $nbClientsMap[(string) $r->label] = (int) $r->nb_clients;
            }

            if ($mode === 'collection') {
                $query = "
                SELECT
                    CASE WHEN collection <= '2025-02-FW' THEN '2025-02-FW et precedentes' ELSE collection END AS collection,
                    SUM(quantite)       AS quantite,
                    SUM(montant_ht_eur) AS montant
                FROM {$table}
                WHERE 1=1 {$networkWhere}
                GROUP BY CASE WHEN collection <= '2025-02-FW' THEN '2025-02-FW et precedentes' ELSE collection END
                ORDER BY collection DESC";
                $labelField = 'collection';
            } else {
                $query = "
                SELECT
                    retard,
                    SUM(quantite)       AS quantite,
                    SUM(montant_ht_eur) AS montant
                FROM {$table}
                WHERE 1=1 {$networkWhere}
                GROUP BY retard
                ORDER BY
                    CASE retard
                        WHEN 'MOIS - 3 et avant'  THEN 1
                        WHEN 'MOIS - 2' THEN 2
                        WHEN 'MOIS - 1' THEN 3
                        WHEN 'MOIS'     THEN 4
                        WHEN 'MOIS + 1' THEN 5
                        ELSE 6
                    END";
                $labelField = 'retard';
            }

            $results    = $this->mssqlMade2design->executeQuery($query);
            $labels     = [];
            $values     = [];
            $quantities = [];
            $nbClients  = [];

            foreach ($results as $row) {
                $label        = (string) $row->$labelField;
                $labels[]     = $label;
                $values[]     = (float) $row->montant;
                $quantities[] = (float) $row->quantite;
                $nbClients[]  = $nbClientsMap[$label] ?? 0;
            }

            // Total exact depuis BACKLOG_CLIENT (COUNT DISTINCT rapide sur table cube)
            $totalResult    = $this->mssqlMade2design->executeQuery("SELECT COUNT(DISTINCT CLIENT_COMMANDE) AS nb_clients_total FROM MASTER_TABLES.BACKLOG_CLIENT");
            $nbClientsTotal = (int) ($totalResult[0]->nb_clients_total ?? 0);

            return [
                'labels'           => $labels,
                'values'           => $values,
                'quantities'       => $quantities,
                'nb_clients'       => $nbClients,
                'nb_clients_total' => $nbClientsTotal,
            ];

        } catch (\Exception $e) {
            $this->graphMailer->notifyError('❌ Dashboard : Backlog client donut', $e);
            $this->logger->error('Erreur backlog client donut', ['exception' => $e]);
            return ['labels' => [], 'values' => [], 'quantities' => []];
        }
    }

    public function getBoutiqueGroupVentesYears(string $group, ?string $customer = null): array
    {
        $customers = self::BOUTIQUE_GROUPS[$group] ?? null;
        if ($customers === null) {
            return [];
        }

        if ($customer !== null && in_array($customer, $customers, true)) {
            $customers = [$customer];
        }

        try {
            $table  = $this->table('INTRANET_SALES_DAILY');
            $inList = implode(',', array_map(fn($n) => "'{$n}'", $customers));

            $query = "
            SELECT
                SUM(CASE WHEN annee = YEAR(GETDATE())
                          AND (mois < MONTH(GETDATE()) OR (mois = MONTH(GETDATE()) AND jour <= DAY(GETDATE())))
                         THEN ca ELSE 0 END) AS ca_n,

                SUM(CASE WHEN annee = YEAR(GETDATE()) - 1
                          AND (mois < MONTH(GETDATE()) OR (mois = MONTH(GETDATE()) AND jour <= DAY(GETDATE())))
                         THEN ca ELSE 0 END) AS ca_ytd_n1,

                SUM(CASE WHEN annee = YEAR(GETDATE()) - 1
                         THEN ca ELSE 0 END) AS ca_n_1,

                ROUND(
                    CASE
                        WHEN SUM(CASE WHEN annee = YEAR(GETDATE()) - 1
                                      AND (mois < MONTH(GETDATE()) OR (mois = MONTH(GETDATE()) AND jour <= DAY(GETDATE())))
                                     THEN ca ELSE 0 END) = 0
                            THEN NULL
                        ELSE
                            (
                                SUM(CASE WHEN annee = YEAR(GETDATE())
                                          AND (mois < MONTH(GETDATE()) OR (mois = MONTH(GETDATE()) AND jour <= DAY(GETDATE())))
                                         THEN ca ELSE 0 END)
                                -
                                SUM(CASE WHEN annee = YEAR(GETDATE()) - 1
                                          AND (mois < MONTH(GETDATE()) OR (mois = MONTH(GETDATE()) AND jour <= DAY(GETDATE())))
                                         THEN ca ELSE 0 END)
                            )
                            /
                            SUM(CASE WHEN annee = YEAR(GETDATE()) - 1
                                      AND (mois < MONTH(GETDATE()) OR (mois = MONTH(GETDATE()) AND jour <= DAY(GETDATE())))
                                     THEN ca ELSE 0 END)
                            * 100
                    END
                , 2) AS variation_pourcent

            FROM {$table}
            WHERE customer_no IN ({$inList})";

            return $this->mssqlMade2design->executeQuery($query);
        } catch (\Exception $e) {
            $this->graphMailer->notifyError('❌ Dashboard boutique groupe ventes années', $e);
            $this->logger->error('Erreur boutique groupe ventes années', ['exception' => $e]);
            return [];
        }
    }

    public function getBoutiqueGroupVentesByMonths(string $group, ?string $customer = null): array
    {
        $customers = self::BOUTIQUE_GROUPS[$group] ?? null;
        if ($customers === null) {
            return [];
        }

        if ($customer !== null && in_array($customer, $customers, true)) {
            $customers = [$customer];
        }

        try {
            $table  = $this->table('INTRANET_SALES_DAILY');
            $inList = implode(',', array_map(fn($n) => "'{$n}'", $customers));

            $query = "
            SELECT
                mois,
                SUM(CASE WHEN annee = YEAR(GETDATE())     THEN ca ELSE 0 END) AS ca_n,
                SUM(CASE WHEN annee = YEAR(GETDATE()) - 1 THEN ca ELSE 0 END) AS ca_n_1
            FROM {$table}
            WHERE customer_no IN ({$inList})
            GROUP BY mois
            ORDER BY mois";

            return $this->mssqlMade2design->executeQuery($query);
        } catch (\Exception $e) {
            $this->graphMailer->notifyError('❌ Dashboard boutique groupe ventes mensuel', $e);
            $this->logger->error('Erreur boutique groupe ventes mensuel', ['exception' => $e]);
            return [];
        }
    }

    public function getBoutiqueGroupTopProduits(string $group, ?string $familyCode = null, ?string $customer = null): array
    {
        $customers = self::BOUTIQUE_GROUPS[$group] ?? null;
        if ($customers === null) {
            return [];
        }

        if ($customer !== null && in_array($customer, $customers, true)) {
            $customers = [$customer];
        }

        try {
            $table   = $this->table('INTRANET_SALES_AGG_YEAR');
            $inList  = implode(',', array_map(fn($n) => "'{$n}'", $customers));

            $familyWhere = '';
            if ($familyCode !== null) {
                $allowed = ['FTW', 'APL', 'HDW'];
                if (in_array($familyCode, $allowed, true)) {
                    $familyWhere = " AND item_family_code = '{$familyCode}'";
                }
            }

            $query = "
            SELECT TOP 5
                item_no          AS ItemNo,
                item_description AS ItemDescription,
                SUM(ca)          AS TotalSales
            FROM {$table}
            WHERE annee = YEAR(GETDATE())
              AND customer_no IN ({$inList})
              {$familyWhere}
            GROUP BY item_no, item_description
            ORDER BY TotalSales DESC";

            $rows = $this->mssqlMade2design->executeQuery($query);

            $out = [];
            foreach ($rows as $r) {
                $itemNo = trim((string) ($r->ItemNo ?? ''));
                if ($itemNo === '') {
                    continue;
                }
                $base = explode('_', $itemNo)[0] ?? $itemNo;
                $out[] = [
                    'image' => "https://www.lecoqsportif.com/cdn/shop/files/" . rawurlencode($base) . "_2.jpg",
                    'code'  => $itemNo,
                    'label' => (string) ($r->ItemDescription ?? ''),
                    'value' => (float)  ($r->TotalSales ?? 0),
                ];
            }

            return $out;
        } catch (\Exception $e) {
            $this->graphMailer->notifyError('❌ Dashboard boutique groupe top produits', $e);
            $this->logger->error('Erreur boutique groupe top produits', ['exception' => $e]);
            return [];
        }
    }

    // ─── E-COM GROUPS ────────────────────────────────────────────────────────

    private const ECOM_GROUP_LABELS = [
        'lcs_shop'      => 'LCS Shop',
        'amazon_vendor' => 'Amazon Vendor',
        'amazon_seller' => 'Amazon Seller',
        'autres_mkp'    => 'Autres Marketplaces',
        'ecom_btb'      => 'E-commerce BTB',
    ];

    private function buildEcomGroupWhere(string $group, string $alias = ''): string
    {
        $p = fn(string $c) => $alias ? "$alias.$c" : $c;

        return match ($group) {
            'lcs_shop'      => " AND {$p('mainnetwork')} IN ('E BUSINESS DIRECT','RETAIL ESHOP')",
            'amazon_vendor' => " AND {$p('reportingdimension')} LIKE '%WHOLESALE%'"
                             . " AND {$p('distributionchannel')} = 'KEY ACCOUNT WEB'"
                             . " AND UPPER({$p('customer_name')}) LIKE '%AMAZON%'",
            'amazon_seller' => " AND {$p('mainnetwork')} IN ('E BUSINESS MARKET PL','RETAIL MARKET PLACE', 'E BUSINESS MKT')"
                             . " AND UPPER({$p('customer_name')}) LIKE '%AMAZON%'",
            'autres_mkp'    => " AND {$p('mainnetwork')} IN ('E BUSINESS MARKET PL','E BUSINESS MKT','RETAIL MARKET PLACE')"
                             . " AND UPPER({$p('customer_name')}) NOT LIKE '%AMAZON%'",
            'ecom_btb'      => " AND {$p('reportingdimension')} LIKE '%WHOLESALE%'"
                             . " AND {$p('distributionchannel')} = 'KEY ACCOUNT WEB'"
                             . " AND UPPER({$p('customer_name')}) NOT LIKE '%AMAZON%'",
            default         => '',
        };
    }

    private function buildEcomCustomerWhere(?array $customers, string $field = 'customer_no'): string
    {
        if (empty($customers)) {
            return '';
        }
        $list = implode(',', array_map(fn($c) => "'" . str_replace("'", "''", $c) . "'", $customers));
        return " AND {$field} IN ({$list})";
    }

    public function getEcomGroupVentesYears(string $group, ?array $customers = null): array
    {
        if ($group === 'global') {
            return $this->getSalesComparaisonYears('ecom');
        }

        if (!array_key_exists($group, self::ECOM_GROUP_LABELS)) {
            return [];
        }

        try {
            $table         = $this->table('INTRANET_SALES_DAILY');
            $groupWhere    = $this->buildEcomGroupWhere($group);
            $customerWhere = $this->buildEcomCustomerWhere($customers);

            $query = "
            SELECT
                SUM(CASE WHEN annee = YEAR(GETDATE())
                          AND (mois < MONTH(GETDATE()) OR (mois = MONTH(GETDATE()) AND jour <= DAY(GETDATE())))
                         THEN ca ELSE 0 END) AS ca_n,
                SUM(CASE WHEN annee = YEAR(GETDATE()) - 1
                          AND (mois < MONTH(GETDATE()) OR (mois = MONTH(GETDATE()) AND jour <= DAY(GETDATE())))
                         THEN ca ELSE 0 END) AS ca_ytd_n1,
                SUM(CASE WHEN annee = YEAR(GETDATE()) - 1 THEN ca ELSE 0 END) AS ca_n_1,
                ROUND(
                    CASE WHEN SUM(CASE WHEN annee = YEAR(GETDATE()) - 1
                                      AND (mois < MONTH(GETDATE()) OR (mois = MONTH(GETDATE()) AND jour <= DAY(GETDATE())))
                                     THEN ca ELSE 0 END) = 0 THEN NULL
                    ELSE (
                        SUM(CASE WHEN annee = YEAR(GETDATE())
                                  AND (mois < MONTH(GETDATE()) OR (mois = MONTH(GETDATE()) AND jour <= DAY(GETDATE())))
                                 THEN ca ELSE 0 END)
                        - SUM(CASE WHEN annee = YEAR(GETDATE()) - 1
                                    AND (mois < MONTH(GETDATE()) OR (mois = MONTH(GETDATE()) AND jour <= DAY(GETDATE())))
                                   THEN ca ELSE 0 END)
                    ) / SUM(CASE WHEN annee = YEAR(GETDATE()) - 1
                                  AND (mois < MONTH(GETDATE()) OR (mois = MONTH(GETDATE()) AND jour <= DAY(GETDATE())))
                                 THEN ca ELSE 0 END) * 100 END
                , 2) AS variation_pourcent
            FROM {$table}
            WHERE 1=1 {$groupWhere}{$customerWhere}";

            return $this->mssqlMade2design->executeQuery($query);
        } catch (\Exception $e) {
            $this->graphMailer->notifyError("❌ Dashboard ecom groupe ventes années [{$group}]", $e);
            $this->logger->error('Erreur ecom groupe ventes années', ['group' => $group, 'exception' => $e]);
            return [];
        }
    }

    public function getEcomGroupVentesByMonths(string $group, ?array $customers = null): array
    {
        if ($group === 'global') {
            return $this->getSalesComparaisonByMonths('ecom');
        }

        if (!array_key_exists($group, self::ECOM_GROUP_LABELS)) {
            return [];
        }

        try {
            $table         = $this->table('INTRANET_SALES_DAILY');
            $groupWhere    = $this->buildEcomGroupWhere($group);
            $customerWhere = $this->buildEcomCustomerWhere($customers);

            $query = "
            SELECT
                mois,
                SUM(CASE WHEN annee = YEAR(GETDATE())      THEN ca ELSE 0 END) AS ca_n,
                SUM(CASE WHEN annee = YEAR(GETDATE()) - 1  THEN ca ELSE 0 END) AS ca_n_1
            FROM {$table}
            WHERE 1=1 {$groupWhere}{$customerWhere}
            GROUP BY mois
            ORDER BY mois";

            return $this->mssqlMade2design->executeQuery($query);
        } catch (\Exception $e) {
            $this->graphMailer->notifyError("❌ Dashboard ecom groupe ventes mensuel [{$group}]", $e);
            $this->logger->error('Erreur ecom groupe ventes mensuel', ['group' => $group, 'exception' => $e]);
            return [];
        }
    }

    public function getEcomGlobalVentesByMonths(): array
    {
        $groups  = ['lcs_shop', 'amazon_vendor', 'amazon_seller', 'autres_mkp', 'ecom_btb'];
        $merged  = [];

        foreach ($groups as $group) {
            $rows = $this->getEcomGroupVentesByMonths($group);
            foreach ($rows as $row) {
                $mois = (int) $row->mois;
                if (!isset($merged[$mois])) {
                    $merged[$mois] = (object) ['mois' => $mois];
                }
                $merged[$mois]->{$group . '_n'}  = $row->ca_n;
                $merged[$mois]->{$group . '_n1'} = $row->ca_n_1;
            }
        }

        // Garantir les 12 mois avec zéros si absents
        for ($m = 1; $m <= 12; $m++) {
            if (!isset($merged[$m])) {
                $merged[$m] = (object) ['mois' => $m];
            }
            foreach ($groups as $g) {
                if (!isset($merged[$m]->{$g . '_n'}))  $merged[$m]->{$g . '_n'}  = 0;
                if (!isset($merged[$m]->{$g . '_n1'})) $merged[$m]->{$g . '_n1'} = 0;
            }
        }

        ksort($merged);
        return array_values($merged);
    }

    public function getEcomGroupTopProduits(string $group, ?string $familyCode = null, ?array $customers = null): array
    {
        try {
            $table    = $this->table('INTRANET_SALES_AGG_YEAR');

            $groupWhere = match ($group) {
                'global'        => $this->buildNetworkWhereClause('ecom'),
                'lcs_shop'      => " AND mainnetwork IN ('E BUSINESS DIRECT','RETAIL ESHOP')",
                'amazon_vendor' => " AND reportingdimension LIKE '%WHOLESALE%' AND distributionchannel = 'KEY ACCOUNT WEB' AND UPPER(customer_name) LIKE '%AMAZON%'",
                'amazon_seller' => " AND mainnetwork IN ('E BUSINESS MARKET PL','RETAIL MARKET PLACE','E BUSINESS MKT') AND UPPER(customer_name) LIKE '%AMAZON%'",
                'autres_mkp'    => " AND mainnetwork IN ('E BUSINESS MARKET PL','E BUSINESS MKT','RETAIL MARKET PLACE') AND UPPER(customer_name) NOT LIKE '%AMAZON%'",
                'ecom_btb'      => " AND reportingdimension LIKE '%WHOLESALE%' AND distributionchannel = 'KEY ACCOUNT WEB' AND UPPER(customer_name) NOT LIKE '%AMAZON%'",
                default         => ' AND 1=0',
            };

            $familyWhere   = '';
            if ($familyCode !== null && in_array($familyCode, ['FTW', 'APL', 'HDW'], true)) {
                $familyWhere = " AND item_family_code = '{$familyCode}'";
            }
            $customerWhere = $this->buildEcomCustomerWhere($customers);

            $query = "
            SELECT TOP 5
                item_no          AS ItemNo,
                item_description AS ItemDescription,
                SUM(ca)          AS TotalSales
            FROM {$table}
            WHERE annee = YEAR(GETDATE())
              {$groupWhere}
              {$familyWhere}
              {$customerWhere}
            GROUP BY item_no, item_description
            ORDER BY TotalSales DESC";

            $rows = $this->mssqlMade2design->executeQuery($query);

            $out = [];
            foreach ($rows as $r) {
                $itemNo = trim((string) ($r->ItemNo ?? ''));
                if ($itemNo === '') {
                    continue;
                }
                $base  = explode('_', $itemNo)[0] ?? $itemNo;
                $out[] = [
                    'image' => "https://www.lecoqsportif.com/cdn/shop/files/" . rawurlencode($base) . "_2.jpg",
                    'code'  => $itemNo,
                    'label' => (string) ($r->ItemDescription ?? ''),
                    'value' => (float)  ($r->TotalSales ?? 0),
                ];
            }

            return $out;
        } catch (\Exception $e) {
            $this->graphMailer->notifyError("❌ Dashboard ecom groupe top produits [{$group}]", $e);
            $this->logger->error('Erreur ecom groupe top produits', ['group' => $group, 'exception' => $e]);
            return [];
        }
    }

    public function getEcomGroupFamilySales(string $group, ?array $customers = null): array
    {
        try {
            $table = $this->table('INTRANET_SALES_AGG_YEAR');

            $groupWhere = match ($group) {
                'global'        => $this->buildNetworkWhereClause('ecom'),
                'lcs_shop'      => " AND mainnetwork IN ('E BUSINESS DIRECT','RETAIL ESHOP')",
                'amazon_vendor' => " AND reportingdimension LIKE '%WHOLESALE%' AND distributionchannel = 'KEY ACCOUNT WEB' AND UPPER(customer_name) LIKE '%AMAZON%'",
                'amazon_seller' => " AND mainnetwork IN ('E BUSINESS MARKET PL','RETAIL MARKET PLACE') AND UPPER(customer_name) LIKE '%AMAZON%'",
                'autres_mkp'    => " AND mainnetwork IN ('E BUSINESS MARKET PL','E BUSINESS MKT') AND UPPER(customer_name) NOT LIKE '%AMAZON%'",
                'ecom_btb'      => " AND reportingdimension LIKE '%WHOLESALE%' AND distributionchannel = 'KEY ACCOUNT WEB' AND UPPER(customer_name) NOT LIKE '%AMAZON%'",
                default         => ' AND 1=0',
            };

            $customerWhere = $this->buildEcomCustomerWhere($customers);

            $query = "
            SELECT
                CASE
                    WHEN item_family_code = 'FTW' THEN 'FOOTWEAR'
                    WHEN item_family_code = 'APL' THEN 'TEXTILE'
                    WHEN item_family_code = 'HDW' THEN 'HARDWARE'
                END AS ItemFamilyCode,
                SUM(ca) AS TotalSales
            FROM {$table}
            WHERE annee = YEAR(GETDATE())
              {$groupWhere}
              {$customerWhere}
            GROUP BY item_family_code
            ORDER BY TotalSales DESC";

            return $this->mssqlMade2design->executeQuery($query);
        } catch (\Exception $e) {
            $this->graphMailer->notifyError("❌ Dashboard ecom groupe famille CA [{$group}]", $e);
            $this->logger->error('Erreur ecom groupe famille CA', ['group' => $group, 'exception' => $e]);
            return [];
        }
    }

    // ─── REFRESH ─────────────────────────────────────────────────────────────

    public function refreshSalesAggMonth(): int
    {
        try {
            $table = $this->table('INTRANET_SALES_AGG_MONTH');

            $this->mssqlMade2design->executeDelete("DELETE FROM {$table}");

            $insertQuery = "
        INSERT INTO {$table} (annee, mois, mainnetwork, reportingdimension, distributionchannel, ca)

        SELECT
            YEAR(I.DOCUMENTPOSTINGDATE)  AS annee,
            MONTH(I.DOCUMENTPOSTINGDATE) AS mois,
            CUST.MAINNETWORK             AS mainnetwork,
            CUST.REPORTINGDIMENSION      AS reportingdimension,
            CUST.DISTRIBUTIONCHANNEL     AS distributionchannel,
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
            CUST.REPORTINGDIMENSION,
            CUST.DISTRIBUTIONCHANNEL;";

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
            billtoname,
            item_family_code,
            item_no,
            item_description,
            ca,
            mainnetwork,
            reportingdimension,
            distributionchannel
        )

        SELECT
            YEAR(I.DOCUMENTPOSTINGDATE) AS annee,
            I.CUSTOMERNO,
            CUST.CUSTOMER_NAME,
            CUST.BILLTONAME,
            COLL.ITEMFAMILYCODE,
            I.ITEMNO,
            COLL.ITEMDESC,
            SUM(I.AMOUNTEURTM)          AS ca,
            CUST.MAINNETWORK            AS mainnetwork,
            CUST.REPORTINGDIMENSION     AS reportingdimension,
            CUST.DISTRIBUTIONCHANNEL    AS distributionchannel

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
            AND YEAR(I.DOCUMENTPOSTINGDATE) >= YEAR(GETDATE()) - 1
            AND CUST.MAINNETWORK IS NOT NULL

        GROUP BY
            YEAR(I.DOCUMENTPOSTINGDATE),
            I.CUSTOMERNO,
            CUST.CUSTOMER_NAME,
            CUST.BILLTONAME,
            COLL.ITEMFAMILYCODE,
            I.ITEMNO,
            COLL.ITEMDESC,
            CUST.MAINNETWORK,
            CUST.REPORTINGDIMENSION,
            CUST.DISTRIBUTIONCHANNEL;";

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
        INSERT INTO {$table} (date, annee, mois, jour, ca, mainnetwork, reportingdimension, distributionchannel, customer_no, customer_name)

        SELECT
            CAST(I.DOCUMENTPOSTINGDATE AS DATE) AS [date],
            YEAR(I.DOCUMENTPOSTINGDATE)         AS annee,
            MONTH(I.DOCUMENTPOSTINGDATE)        AS mois,
            DAY(I.DOCUMENTPOSTINGDATE)          AS jour,
            SUM(I.AMOUNTEURTM)                  AS ca,
            CUST.MAINNETWORK,
            CUST.REPORTINGDIMENSION,
            CUST.DISTRIBUTIONCHANNEL,
            I.CUSTOMERNO                        AS customer_no,
            CUST.CUSTOMER_NAME                  AS customer_name

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
            CUST.REPORTINGDIMENSION,
            CUST.DISTRIBUTIONCHANNEL,
            I.CUSTOMERNO,
            CUST.CUSTOMER_NAME

        ORDER BY [date];";

            return $this->mssqlMade2design->insertData($insertQuery);

        } catch (\Exception $e) {
            $this->graphMailer->notifyError('❌ Erreur recalcul cube ventes daily', $e);
            $this->logger->error('Erreur recalcul cube ventes daily', ['exception' => $e]);
            return 0;
        }
    }

    public function refreshBacklogClient(): int
    {
        try {
            $table = $this->table('INTRANET_BACKLOG_CLI');

            $this->mssqlMade2design->executeDelete("DELETE FROM {$table}");

            $insertQuery = "
        INSERT INTO {$table} (retard, collection, quantite, montant_ht_eur, nb_clients, reportingdimension, date_refresh)

        SELECT
            retard,
            collection,
            SUM(quantite)       AS quantite,
            SUM(montant_eur)    AS montant_ht_eur,
            COUNT(DISTINCT bpcord) AS nb_clients,
            reportingdimension,
            GETDATE()
        FROM (
            SELECT
                CASE
                    WHEN SOQ.DEMDLVDAT_0 < DATEADD(MONTH, -2, DATEFROMPARTS(YEAR(GETDATE()), MONTH(GETDATE()), 1)) THEN 'MOIS - 3 et avant'
                    WHEN SOQ.DEMDLVDAT_0 < DATEADD(MONTH, -1, DATEFROMPARTS(YEAR(GETDATE()), MONTH(GETDATE()), 1)) THEN 'MOIS - 2'
                    WHEN SOQ.DEMDLVDAT_0 < DATEFROMPARTS(YEAR(GETDATE()), MONTH(GETDATE()), 1)                     THEN 'MOIS - 1'
                    WHEN SOQ.DEMDLVDAT_0 <= EOMONTH(GETDATE())                                                     THEN 'MOIS'
                    WHEN SOQ.DEMDLVDAT_0 <= EOMONTH(DATEADD(MONTH, 1, GETDATE()))                                  THEN 'MOIS + 1'
                    ELSE 'MOIS + 2 et après'
                END AS retard,

                SOQ.YCOLLECT_0  AS collection,

                CAST(ROUND(SOQ.QTY_0 - SOQ.DLVQTY_0 - SOQ.ODLQTY_0, 0) AS INT) AS quantite,

                CASE
                    WHEN SOH.CUR_0 = 'EUR' THEN SOP.NETPRINOT_0 * (SOQ.QTY_0 - SOQ.DLVQTY_0 - SOQ.ODLQTY_0)
                    ELSE (SOP.NETPRINOT_0 * (SOQ.QTY_0 - SOQ.DLVQTY_0 - SOQ.ODLQTY_0)) / NULLIF(TC.CHGRAT_0, 0)
                END AS montant_eur,

                SOH.BPCORD_0    AS bpcord,

                ATX.TEXTE_0     AS reportingdimension

            FROM X3_LCS.SORDERQ SOQ
            INNER JOIN X3_LCS.SORDER  SOH ON SOQ.SOHNUM_0 = SOH.SOHNUM_0
            INNER JOIN X3_LCS.SORDERP SOP ON SOQ.SOHNUM_0 = SOP.SOHNUM_0
                                          AND SOQ.ITMREF_0  = SOP.ITMREF_0
                                          AND SOQ.SOPLIN_0  = SOP.SOPLIN_0
            INNER JOIN X3_LCS.BPCUSTOMER BPC ON SOH.BPCORD_0 = BPC.BPCNUM_0
            LEFT JOIN X3_LCS.ATEXTRA ATX ON ATX.IDENT2_0 = BPC.TSCCOD_2
                                         AND ATX.CODFIC_0 = 'ATABDIV'
                                         AND ATX.LANGUE_0 = 'FRA'
                                         AND ATX.ZONE_0   = 'LNGDES'
                                         AND ATX.IDENT1_0 = '32'
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
              AND SOH.ZSOHVALSTA_0 <> 3
        ) t
        GROUP BY retard, collection, reportingdimension;";

            $result = $this->mssqlMade2design->insertData($insertQuery);

            // Refresh table totaux clients distincts par retard
            $tableTotal = $this->table('INTRANET_BACKLOG_CLI_TOTAL_CLIENTS');
            $this->mssqlMade2design->executeDelete("DELETE FROM {$tableTotal}");

            $insertTotal = "
            INSERT INTO {$tableTotal} (retard, nb_clients, date_refresh)
            SELECT
                CASE
                    WHEN SOQ.DEMDLVDAT_0 < DATEADD(MONTH, -2, DATEFROMPARTS(YEAR(GETDATE()), MONTH(GETDATE()), 1)) THEN 'MOIS - 3 et avant'
                    WHEN SOQ.DEMDLVDAT_0 < DATEADD(MONTH, -1, DATEFROMPARTS(YEAR(GETDATE()), MONTH(GETDATE()), 1)) THEN 'MOIS - 2'
                    WHEN SOQ.DEMDLVDAT_0 < DATEFROMPARTS(YEAR(GETDATE()), MONTH(GETDATE()), 1)                     THEN 'MOIS - 1'
                    WHEN SOQ.DEMDLVDAT_0 <= EOMONTH(GETDATE())                                                     THEN 'MOIS'
                    WHEN SOQ.DEMDLVDAT_0 <= EOMONTH(DATEADD(MONTH, 1, GETDATE()))                                  THEN 'MOIS + 1'
                    ELSE 'MOIS + 2 et après'
                END AS retard,
                COUNT(DISTINCT SOH.BPCORD_0) AS nb_clients,
                GETDATE()
            FROM X3_LCS.SORDERQ SOQ
            INNER JOIN X3_LCS.SORDER SOH ON SOQ.SOHNUM_0 = SOH.SOHNUM_0
            INNER JOIN X3_LCS.BPCUSTOMER BPC ON SOH.BPCORD_0 = BPC.BPCNUM_0
            WHERE SOQ.SOQSTA_0 <> 3
              AND (SOQ.QTY_0 - SOQ.DLVQTY_0 - SOQ.ODLQTY_0) > 0
              AND BPC.BCGCOD_0 <> 'INTER'
              AND SOH.ZSOHVALSTA_0 <> 3
            GROUP BY
                CASE
                    WHEN SOQ.DEMDLVDAT_0 < DATEADD(MONTH, -2, DATEFROMPARTS(YEAR(GETDATE()), MONTH(GETDATE()), 1)) THEN 'MOIS - 3 et avant'
                    WHEN SOQ.DEMDLVDAT_0 < DATEADD(MONTH, -1, DATEFROMPARTS(YEAR(GETDATE()), MONTH(GETDATE()), 1)) THEN 'MOIS - 2'
                    WHEN SOQ.DEMDLVDAT_0 < DATEFROMPARTS(YEAR(GETDATE()), MONTH(GETDATE()), 1)                     THEN 'MOIS - 1'
                    WHEN SOQ.DEMDLVDAT_0 <= EOMONTH(GETDATE())                                                     THEN 'MOIS'
                    WHEN SOQ.DEMDLVDAT_0 <= EOMONTH(DATEADD(MONTH, 1, GETDATE()))                                  THEN 'MOIS + 1'
                    ELSE 'MOIS + 2 et après'
                END";

            $this->mssqlMade2design->insertData($insertTotal);

            return $result;

        } catch (\Exception $e) {
            $this->graphMailer->notifyError('❌ Erreur recalcul cube backlog client', $e);
            $this->logger->error('Erreur recalcul cube backlog client', ['exception' => $e]);
            return 0;
        }
    }

    public function refreshAllSalesCubes(): array
    {
        return [
            'Cube CA mensuel'          => $this->refreshSalesAggMonth(),
            'Cube ventes client année' => $this->refreshSalesAggMonthClient(),
            'Cube ventes journalières' => $this->refreshSalesDaily(),
            'Cube backlog client'      => $this->refreshBacklogClient(),
        ];
    }
}
