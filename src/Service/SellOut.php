<?php

namespace App\Service;

use App\Factory\MssqlManagerFactory;
use App\Service\Tools\GraphMailer;
use App\Service\Tools\MssqlManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Stat "Sell Out par Client" : ventes magasin par semaine, vue globale par client
 * et vue détail par collection / article.
 *
 * S'appuie sur ses propres tables d'agrégats, distinctes de celles du dashboard
 * Sell Out : le grain n'est pas le même (le client entre dans la clé), et le
 * dashboard ne doit pas être impacté. DDL : Sei/create_tables_sellout_client.sql.
 */
class SellOut
{
    /** Enseignes couvertes, identiques au dashboard Sell Out. */
    private const string SOURCES = "'SPORT2000', 'INTERSPORT'";

    private MssqlManager $mssql;
    private bool $isDev;

    public function __construct(
        MssqlManagerFactory $mssqlManagerFactory,
        private LoggerInterface $logger,
        private GraphMailer $graphMailer,
        #[Autowire('%db.lcs_sei%')]
        string $dbLcsSei,
        #[Autowire('%kernel.environment%')]
        string $environment,
    ) {
        $this->mssql = $mssqlManagerFactory->create($dbLcsSei);
        $this->isDev = ($environment === 'dev');
    }

    private function table(string $name): string
    {
        return 'MASTER_TABLES.' . $name . ($this->isDev ? '_DEV' : '');
    }

    /**
     * Lignes de vente retenues : les deux enseignes, semaine et client renseignés,
     * et une quantité non nulle — les lignes à 0 ne portent que du stock magasin
     * et représentent l'essentiel des 21,9 millions de lignes de la table source.
     */
    private const string BASE_WHERE = "
        S.SOURCENAME IN (" . self::SOURCES . ")
        AND S.WEEK_CODE IS NOT NULL
        AND S.CUSTOMER_ID IS NOT NULL
        AND ISNULL(S.SALESQTY, 0) <> 0";

    /**
     * Groupement indépendant du client, via X3 (table diverse 6021).
     * Alias ATX4, conformément à la convention ATEXTRA du projet.
     */
    private const string JOIN_CLIENT = "
        LEFT JOIN X3_LCS.BPCUSTOMER BPC
               ON BPC.BPCNUM_0 = S.CUSTOMER_ID
        LEFT JOIN X3_LCS.ATEXTRA ATX4
               ON ATX4.IDENT2_0 = BPC.ZGROUPIND_0
              AND ATX4.CODFIC_0 = 'ATABDIV'
              AND ATX4.LANGUE_0 = 'FRA'
              AND ATX4.ZONE_0   = 'LNGDES'
              AND ATX4.IDENT1_0 = '6021'
        LEFT JOIN X3_LCS.BPADDRESS BPA
               ON BPA.BPATYP_0 = 1
              AND BPA.BPANUM_0 = BPC.BPCNUM_0
              AND BPA.BPAADD_0 = BPC.BPAADD_0
        LEFT JOIN X3_LCS.SALESREP REP1 ON BPC.REP_0 = REP1.REPNUM_0
        LEFT JOIN X3_LCS.SALESREP REP2 ON BPC.REP_1 = REP2.REPNUM_0";

    // ─── Rafraîchissement ───────────────────────────────────────────────────

    public function refreshClientWeek(): int
    {
        try {
            $table = $this->table('INTRANET_SELLOUT_CLIENT_WEEK');
            $this->mssql->executeDelete("DELETE FROM {$table}");

            return $this->mssql->insertData("
                INSERT INTO {$table}
                    (sourcename, customer_id, customer_name, groupe_name, ville, rep1, rep2, annee, semaine, salesqty)
                SELECT
                    S.SOURCENAME,
                    S.CUSTOMER_ID,
                    MAX(BPC.BPCNAM_0),
                    MAX(NULLIF(LTRIM(RTRIM(ATX4.TEXTE_0)), '')),
                    MAX(BPA.CTY_0),
                    -- Meme convention que le Backlog Client : REP1 affiche BPC.REP_1
                    MAX(REP2.REPNAM_0),
                    MAX(REP1.REPNAM_0),
                    CAST(LEFT(CAST(S.WEEK_CODE AS VARCHAR(6)), 4) AS INT),
                    CAST(RIGHT(CAST(S.WEEK_CODE AS VARCHAR(6)), 2) AS INT),
                    SUM(S.SALESQTY)
                FROM SEI_X3_LCS.LCS_SELLOUT_SALES S
                " . self::JOIN_CLIENT . "
                WHERE " . self::BASE_WHERE . "
                GROUP BY
                    S.SOURCENAME,
                    S.CUSTOMER_ID,
                    CAST(LEFT(CAST(S.WEEK_CODE AS VARCHAR(6)), 4) AS INT),
                    CAST(RIGHT(CAST(S.WEEK_CODE AS VARCHAR(6)), 2) AS INT)
            ");
        } catch (\Throwable $e) {
            $this->graphMailer->notifyError('❌ Erreur refresh INTRANET_SELLOUT_CLIENT_WEEK', $e);
            $this->logger->error('Erreur refresh sellout client week', ['exception' => $e]);
            return 0;
        }
    }

    public function refreshClientItemWeek(): int
    {
        try {
            $table = $this->table('INTRANET_SELLOUT_CLIENT_ITEM_WEEK');
            $this->mssql->executeDelete("DELETE FROM {$table}");

            // Collection : SERIESNO de la ligne de vente (toujours une collection valide).
            // Désignation et genre : ITMMASTER via ITEMN + VARIANTCODE, qui apparie 94 %
            // des articles — la jointure par collection n'en apparie que 35 %.
            // Segment d'offre : ZITMCOL, d'abord sur la collection déclarée, sinon sur la
            // collection la plus récente de l'article. Le champ reste très peu renseigné
            // dans X3 (cf. note dans la doc de la stat).
            return $this->mssql->insertData("
                WITH CollectionRecente AS (
                    SELECT YIL.ITMREF_0, YIL.YCOLLECT_0,
                           ROW_NUMBER() OVER (
                               PARTITION BY YIL.ITMREF_0 ORDER BY YCO.YDATDEB_0 DESC
                           ) AS rn
                    FROM X3_LCS.YITMCOLLECT YIL
                    INNER JOIN X3_LCS.YCOLLECTION YCO ON YIL.YCOLLECT_0 = YCO.YCOLLECT_0
                )
                INSERT INTO {$table}
                    (sourcename, customer_id, customer_name, collection, itemn, itemdes,
                     genre, segment_offre, annee, semaine, salesqty)
                SELECT
                    S.SOURCENAME,
                    S.CUSTOMER_ID,
                    MAX(BPC.BPCNAM_0),
                    S.SERIESNO,
                    S.ITEMN,
                    MAX(ITM.ITMDES1_0),
                    MAX(ITM.TSICOD_0),
                    MAX(COALESCE(
                        NULLIF(LTRIM(RTRIM(ITC.ZSEGOFF_0)), ''),
                        NULLIF(LTRIM(RTRIM(ITC_RECENT.ZSEGOFF_0)), '')
                    )),
                    CAST(LEFT(CAST(S.WEEK_CODE AS VARCHAR(6)), 4) AS INT),
                    CAST(RIGHT(CAST(S.WEEK_CODE AS VARCHAR(6)), 2) AS INT),
                    SUM(S.SALESQTY)
                FROM SEI_X3_LCS.LCS_SELLOUT_SALES S
                " . self::JOIN_CLIENT . "
                LEFT JOIN X3_LCS.ITMMASTER ITM
                       ON ITM.ITMREF_0 = S.ITEMN + '_' + S.VARIANTCODE
                LEFT JOIN X3_LCS.ZITMCOL ITC
                       ON ITC.ITMREF_0   = S.ITEMN
                      AND ITC.YCOLLECT_0 = S.SERIESNO
                LEFT JOIN CollectionRecente CR
                       ON CR.ITMREF_0 = S.ITEMN + '_' + S.VARIANTCODE
                      AND CR.rn = 1
                LEFT JOIN X3_LCS.ZITMCOL ITC_RECENT
                       ON ITC_RECENT.ITMREF_0   = S.ITEMN
                      AND ITC_RECENT.YCOLLECT_0 = CR.YCOLLECT_0
                WHERE " . self::BASE_WHERE . "
                  AND S.ITEMN IS NOT NULL
                  AND S.ITEMN <> ''
                GROUP BY
                    S.SOURCENAME,
                    S.CUSTOMER_ID,
                    S.SERIESNO,
                    S.ITEMN,
                    CAST(LEFT(CAST(S.WEEK_CODE AS VARCHAR(6)), 4) AS INT),
                    CAST(RIGHT(CAST(S.WEEK_CODE AS VARCHAR(6)), 2) AS INT)
            ");
        } catch (\Throwable $e) {
            $this->graphMailer->notifyError('❌ Erreur refresh INTRANET_SELLOUT_CLIENT_ITEM_WEEK', $e);
            $this->logger->error('Erreur refresh sellout client item week', ['exception' => $e]);
            return 0;
        }
    }

    /** @return array<string, int> libellé => nombre de lignes insérées */
    public function refreshAll(): array
    {
        return [
            'Sell-out par client'          => $this->refreshClientWeek(),
            'Sell-out par client/article'  => $this->refreshClientItemWeek(),
        ];
    }

    // ─── Lecture ────────────────────────────────────────────────────────────

    /**
     * Années disponibles, la plus récente d'abord (alimente le sélecteur d'année).
     *
     * @return array<int, int>
     */
    public function getAnneesDisponibles(): array
    {
        try {
            $table = $this->table('INTRANET_SELLOUT_CLIENT_WEEK');
            $rows  = $this->mssql->executeQuery(
                "SELECT DISTINCT annee FROM {$table} WHERE annee IS NOT NULL ORDER BY annee DESC"
            );

            return array_map(fn($r) => (int) $r->annee, $rows);
        } catch (\Throwable $e) {
            $this->logger->error('Erreur sellout années disponibles', ['exception' => $e]);
            return [(int) date('Y')];
        }
    }

    /**
     * Pivote des lignes « une par semaine » en « une par entité, une colonne par semaine ».
     *
     * @param array<int, object>          $rows
     * @param array<int, string>          $cles    champs identifiant une ligne du résultat
     * @return array{weeks: array<int, int>, rows: array<int, array<string, mixed>>}
     */
    private function pivoterParSemaine(array $rows, array $cles): array
    {
        $semaines = [];
        $pivot    = [];

        foreach ($rows as $row) {
            $semaine = (int) ($row->semaine ?? 0);
            if ($semaine < 1 || $semaine > 53) {
                continue;
            }
            $semaines[$semaine] = true;

            $identite = [];
            foreach ($cles as $cle) {
                $identite[$cle] = (string) ($row->$cle ?? '');
            }
            $key = implode('||', $identite);

            if (!isset($pivot[$key])) {
                $pivot[$key] = $identite + ['QTE_TOTAL' => 0];
            }

            $qte = (int) round((float) ($row->salesqty ?? 0));
            $col = 'QTE_S' . str_pad((string) $semaine, 2, '0', STR_PAD_LEFT);

            $pivot[$key][$col]         = ($pivot[$key][$col] ?? 0) + $qte;
            $pivot[$key]['QTE_TOTAL'] += $qte;
        }

        ksort($semaines);
        $semaines = array_keys($semaines);

        // Chaque ligne porte toutes les semaines : une cellule absente afficherait un vide
        foreach ($pivot as &$ligne) {
            foreach ($semaines as $semaine) {
                $col = 'QTE_S' . str_pad((string) $semaine, 2, '0', STR_PAD_LEFT);
                $ligne[$col] ??= 0;
            }
        }
        unset($ligne);

        return ['weeks' => $semaines, 'rows' => array_values($pivot)];
    }

    /**
     * Vue globale : une ligne par source × client, une colonne par semaine.
     *
     * @return array{weeks: array<int, int>, rows: array<int, array<string, mixed>>}
     */
    public function getVentesParClient(int $annee): array
    {
        try {
            $table = $this->table('INTRANET_SELLOUT_CLIENT_WEEK');
            $annee = (int) $annee;

            $rows = $this->mssql->executeQuery("
                SELECT sourcename, customer_id, customer_name, rep1, rep2, groupe_name, ville,
                       semaine, SUM(salesqty) AS salesqty
                FROM {$table}
                WHERE annee = {$annee}
                GROUP BY sourcename, customer_id, customer_name, rep1, rep2, groupe_name, ville, semaine
            ");

            $resultat = $this->pivoterParSemaine(
                $rows,
                ['sourcename', 'customer_id', 'customer_name', 'rep1', 'rep2', 'groupe_name', 'ville']
            );

            // Les clients sans groupement (environ 20 %) sont renvoyés en fin de liste :
            // un tri alphabétique brut les remonterait tous en tête.
            usort($resultat['rows'], function (array $a, array $b): int {
                $groupeA = trim((string) $a['groupe_name']);
                $groupeB = trim((string) $b['groupe_name']);

                return (($groupeA === '') <=> ($groupeB === ''))
                    ?: ($a['sourcename'] <=> $b['sourcename'])
                    ?: ($groupeA <=> $groupeB)
                    ?: ($a['customer_id'] <=> $b['customer_id']);
            });

            return $resultat;
        } catch (\Throwable $e) {
            $this->graphMailer->notifyError('❌ Erreur Sell Out par Client : vue globale', $e);
            $this->logger->error('Erreur sellout ventes par client', ['exception' => $e]);
            return ['weeks' => [], 'rows' => []];
        }
    }

    /**
     * Vue détail d'un client : une ligne par collection × article, une colonne par semaine.
     *
     * @return array{weeks: array<int, int>, rows: array<int, array<string, mixed>>}
     */
    public function getVentesParClientDetail(int $annee, string $customerId): array
    {
        try {
            $table      = $this->table('INTRANET_SELLOUT_CLIENT_ITEM_WEEK');
            $annee      = (int) $annee;
            $customerId = str_replace("'", "''", trim($customerId));

            if ($customerId === '') {
                return ['weeks' => [], 'rows' => []];
            }

            $rows = $this->mssql->executeQuery("
                SELECT collection, itemn, itemdes, genre, segment_offre,
                       semaine, SUM(salesqty) AS salesqty
                FROM {$table}
                WHERE annee = {$annee}
                  AND customer_id = '{$customerId}'
                GROUP BY collection, itemn, itemdes, genre, segment_offre, semaine
            ");

            $resultat = $this->pivoterParSemaine(
                $rows,
                ['collection', 'itemn', 'itemdes', 'genre', 'segment_offre']
            );

            usort($resultat['rows'], fn($a, $b) =>
                ($b['collection'] <=> $a['collection']) ?: ($a['itemn'] <=> $b['itemn'])
            );

            return $resultat;
        } catch (\Throwable $e) {
            $this->graphMailer->notifyError('❌ Erreur Sell Out par Client : vue détail', $e);
            $this->logger->error('Erreur sellout détail client', ['exception' => $e]);
            return ['weeks' => [], 'rows' => []];
        }
    }

    /** Libellé du client, pour le titre de la page de détail. */
    public function getNomClient(string $customerId): string
    {
        try {
            $table      = $this->table('INTRANET_SELLOUT_CLIENT_WEEK');
            $customerId = str_replace("'", "''", trim($customerId));
            $rows       = $this->mssql->executeQuery(
                "SELECT TOP 1 customer_name FROM {$table} WHERE customer_id = '{$customerId}'"
            );

            return (string) ($rows[0]->customer_name ?? $customerId);
        } catch (\Throwable $e) {
            $this->logger->error('Erreur sellout nom client', ['exception' => $e]);
            return $customerId;
        }
    }
}
