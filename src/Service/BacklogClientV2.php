<?php

namespace App\Service;

use App\Factory\MssqlManagerFactory;
use App\Infrastructure\Sql\SqlFileLoader;
use App\Service\AgGrid\Ssrm\AgGridSqlBuilder;
use App\Service\AgGrid\Ssrm\SsrmRequest;
use App\Service\AgGrid\Ssrm\SsrmResponse;
use App\Service\Tools\GraphMailer;
use App\Service\Tools\Helpers;
use App\Service\Tools\MssqlManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Backlog Client v2 (SSRM) — destiné à remplacer le Backlog Client X3.
 *
 * Mêmes colonnes, mêmes intitulés et mêmes calculs que backlog_client_x3, mais bâti sur la
 * requête d'État des commandes clients, bien plus rapide. Le bloc stock / transit / backlog
 * fournisseur n'est chargé que si l'utilisateur coche "Inclure le stock".
 *
 * Toute jointure ajoutée à backlog_client_v2.sql doit aussi l'être dans buildAggregateSql(),
 * sinon le filtre sur la colonne correspondante fait échouer les totaux.
 */
class BacklogClientV2
{
    private const string BASE_WHERE = "SOQ.SOQSTA_0 <> 3 AND SOH.ZSOHVALSTA_0 <> 3 AND BPC.BCGCOD_0 <> 'INTER'";

    /** Sites dont le stock est remonté quand l'option "Inclure le stock" est cochée. */
    private const array STOCK_SITES = ['WLOGM', 'WSFCN', 'WTAKH', 'WDTTH'];

    private MssqlManager $mssqlSei;

    public function __construct(
        MssqlManagerFactory $mssqlManagerFactory,
        private SqlFileLoader $sqlFileLoader,
        private Divers $divers,
        private Helpers $helpers,
        private GraphMailer $graphMailer,
        private LoggerInterface $logger,
        #[Autowire('%db.lcs_sei%')]
        string $dbLcsSei,
    ) {
        $this->mssqlSei = $mssqlManagerFactory->create($dbLcsSei);
    }

    /**
     * Field Ag-Grid → expression SQL (whitelist pour filtres et tris SSRM).
     * Alias ATEXTRA : ATX = MAINNETWORK (32), ATX4 = INDEPENDANT_GROUPMENT (6021),
     * ATX5 = AGE (TABLINCFG), ATX6 = GROUP_CODE (6028).
     */
    private function getFieldMap(bool $includeStock = false): array
    {
        $map = [
            'SITE'                  => 'SOH.STOFCY_0',
            'MAINNETWORK'           => 'ATX.TEXTE_0',
            'CLIENT'                => 'SOH.BPCINV_0',
            'NOM_CLIENT'            => 'BPC_INV.BPCNAM_0',
            'CLIENT_COMMANDE'       => 'SOH.BPCORD_0',
            'NOM_CLIENT_COMMANDE'   => 'SOH.BPCNAM_0',
            'NUM_COMMANDE'          => 'SOQ.SOHNUM_0',
            'REF_CLIENT'            => "CASE WHEN SOH.CUSORDREF_0 <> '' THEN SOH.CUSORDREF_0 ELSE SOH.ZNORIGIN_0 END",
            'REFERENCE_INTERNE'     => 'SOH.ZNORIGIN_0',
            'GENRE'                 => 'ITM.TSICOD_0',
            'AGE'                   => 'ATX5.TEXTE_0',
            'ADRESSE_LIVRAISON'     => 'SOH.BPAADD_0',
            'FAMILLE'               => 'ITM.TCLCOD_0',
            'COLLECTION'            => 'SOQ.YCOLLECT_0',
            'SKU'                   => 'ITM.ITMREF_0',
            'ARTICLE'               => 'SPLIT.ARTICLE_BASE',
            'VARIANT'               => 'SPLIT.VARIANT_VAL',
            'ITMDES1_0'             => 'ITM.ITMDES1_0',
            'EAN'                   => 'ITM.EANCOD_0',
            'DROPPE'                => "CASE WHEN ITC.ZDROPPED_0 = 2 THEN 'OUI' ELSE 'NON' END",
            'NOOS'                  => "CASE WHEN ITM.ZNOOSFLG_0 = 2 THEN 'Oui' ELSE 'Non' END",
            'GROUP_CODE'            => 'ATX6.TEXTE_0',
            'DATE_COMMANDE'         => 'CONVERT(varchar(10), SOH.ORDDAT_0, 23)',
            'DATE_LIVRAISON'        => 'CONVERT(varchar(10), SOQ.DEMDLVDAT_0, 23)',
            'REP1'                  => 'REP2.REPNAM_0',
            'REP2'                  => 'REP1.REPNAM_0',
            'QUANTITE'              => '(SOQ.QTY_0 - (SOQ.DLVQTY_0 + SOQ.ODLQTY_0))',
            'PRIX'                  => 'SOP.NETPRINOT_0 * (SOQ.QTY_0 - (SOQ.DLVQTY_0 + SOQ.ODLQTY_0)) * (1 - (ISNULL(SVT.DTAAMT_0, 0)/100))',
            'CUR_0'                 => 'SOH.CUR_0',
            'REMISE_AUTO'           => 'SOP.DISCRGVAL1_0',
            'REMISE_MANU'           => 'SOP.DISCRGVAL2_0',
            'INDEPENDANT_GROUPMENT' => 'ATX4.TEXTE_0',
            'CODE_POSTAL'           => 'BPA.POSCOD_0',
            'VILLE'                 => 'BPA.CTY_0',
            'ZCLASSE_0'             => 'SOH.ZCLASSE_0',
            'PO_EN_COURS'           => 'ISNULL(PO.PO_EN_COURS, 0)',
            'DATE_COMMANDE_FOURNISSEUR' => 'COALESCE(PO.DATE_COMMANDE_FOURNISSEUR, CI.DATE_COMMANDE_FOURNISSEUR)',
        ];

        // Les colonnes stock sont volontairement absentes de la whitelist : à l'écran elles
        // sont calculées après coup, article par article (voir enrichWithStock()), donc SQL
        // Server ne peut ni filtrer ni trier dessus. Elles sont déclarées non filtrables et
        // non triables dans la config AG Grid.
        if ($includeStock) {
            foreach (self::STOCK_SITES as $site) {
                $map["STOCK_INTERNE_{$site}"] = "ISNULL(STK.STOCK_INTERNE_{$site}, 0)";
                $map["STOCK_REEL_{$site}"]    = "ISNULL(STK.STOCK_REEL_{$site}, 0)";
                $map["EN_TRANSIT_{$site}"]    = $this->transitSql($site);
                $map["STOCK_A_TERME_TRANSIT_{$site}"] =
                    "(ISNULL(STK.STOCK_REEL_{$site}, 0) + " . $this->transitSql($site) . ')';
                $map["STOCK_A_TERME_BACKLOG_FOURNISSEUR_{$site}"] =
                    "(ISNULL(STK.STOCK_REEL_{$site}, 0) + " . $this->transitSql($site)
                    . ' + ' . $this->supplierBacklogSql($site) . ')';
            }
        }

        return $map;
    }

    /** Quantité encore attendue des transferts intersites vers un site. */
    private function transitSql(string $site): string
    {
        return "ISNULL((SELECT SUM(c.QTE_RESTANTE) FROM MASTER_TABLES.COMMANDES_INTERSITES c"
            . " WHERE c.SITE_RECEPTION = '{$site}' AND c.ITMREF_0 = SOQ.ITMREF_0), 0)";
    }

    /** Quantité encore attendue des commandes fournisseur d'un site. */
    private function supplierBacklogSql(string $site): string
    {
        return "ISNULL((SELECT SUM(poq2.QTYUOM_0 - poq2.RCPQTYSTU_0)"
            . ' FROM X3_LCS.PORDERQ poq2'
            . ' INNER JOIN X3_LCS.PORDER poh2 ON poq2.POHNUM_0 = poh2.POHNUM_0'
            . " WHERE poq2.LINCLEFLG_0 = 1 AND poh2.BETFCY_0 <> 2"
            . " AND poq2.PRHFCY_0 = '{$site}' AND poq2.ITMREF_0 = SOQ.ITMREF_0), 0)";
    }

    /**
     * Colonnes stock injectées dans {{STOCK_COLUMNS}} : 5 mesures par site, mêmes expressions
     * que le Backlog Client X3. Le bloc commence par une virgule, la dernière colonne fixe
     * de la requête n'en ayant pas.
     */
    private function stockColumnsSql(): string
    {
        $parts = [];

        foreach (self::STOCK_SITES as $site) {
            $transit  = $this->transitSql($site);
            $supplier = $this->supplierBacklogSql($site);

            $parts[] = "ISNULL(STK.STOCK_INTERNE_{$site}, 0) AS STOCK_INTERNE_{$site}";
            $parts[] = "ISNULL(STK.STOCK_REEL_{$site}, 0) AS STOCK_REEL_{$site}";
            $parts[] = "{$transit} AS EN_TRANSIT_{$site}";
            $parts[] = "(ISNULL(STK.STOCK_REEL_{$site}, 0) + {$transit}) AS STOCK_A_TERME_TRANSIT_{$site}";
            $parts[] = "(ISNULL(STK.STOCK_REEL_{$site}, 0) + {$transit} + {$supplier})"
                . " AS STOCK_A_TERME_BACKLOG_FOURNISSEUR_{$site}";
        }

        return ",\n    " . implode(",\n    ", $parts);
    }

    /** Agrégat de stock par article, injecté dans {{STOCK_JOINS}}. */
    private function stockJoinSql(): string
    {
        $sums = [];

        foreach (self::STOCK_SITES as $site) {
            $sums[] = "SUM(CASE WHEN SITE = '{$site}' THEN STOCK_REEL ELSE 0 END) AS STOCK_REEL_{$site}";
            $sums[] = "SUM(CASE WHEN SITE = '{$site}' THEN STOCK_INTERNE ELSE 0 END) AS STOCK_INTERNE_{$site}";
        }

        $sites = "'" . implode("','", self::STOCK_SITES) . "'";

        return "
LEFT  JOIN (
    SELECT ARTICLE,
           " . implode(",\n           ", $sums) . "
    FROM MASTER_TABLES.STOCK_ALLOCATION
    WHERE STATUS_STOCK = 'A1' AND SITE IN ({$sites})
    GROUP BY ARTICLE
) STK ON STK.ARTICLE = ITM.ITMREF_0";
    }

    private function buildCollectionsClause(array $collections): string
    {
        $collections = array_values(array_filter(array_map('strval', $collections), fn(string $c) => $c !== ''));

        if ($collections === []) {
            return '';
        }

        $escaped = implode(',', array_map(fn(string $c) => "'" . str_replace("'", "''", $c) . "'", $collections));

        return " AND SOQ.YCOLLECT_0 IN ($escaped)";
    }

    /**
     * Clause collection de la requête : '' si l'option "Tout" est choisie (allCollections),
     * null si aucune collection n'est sélectionnée (rien à charger).
     */
    private function resolveCollectionsClause(SsrmRequest $request): ?string
    {
        if ($request->getOption('allCollections', false)) {
            return '';
        }

        $collections = (array) $request->getOption('collections', []);

        return $collections === [] ? null : $this->buildCollectionsClause($collections);
    }

    /**
     * Complète un bloc de lignes avec le stock, le transit et le backlog fournisseur.
     *
     * Plutôt qu'une jointure qui agrège tout le stock avant même de savoir quelles lignes
     * seront affichées, on interroge les trois sources uniquement pour les articles du bloc
     * (200 au plus). Contrepartie : ces colonnes ne sont ni filtrables ni triables.
     *
     * @param array<int, object> $rows
     */
    private function enrichWithStock(array $rows): void
    {
        $sites = self::STOCK_SITES;

        // Valeurs par défaut : une colonne absente casserait l'affichage de la grille
        foreach ($rows as $row) {
            foreach ($sites as $site) {
                foreach (['STOCK_INTERNE', 'STOCK_REEL', 'EN_TRANSIT',
                          'STOCK_A_TERME_TRANSIT', 'STOCK_A_TERME_BACKLOG_FOURNISSEUR'] as $mesure) {
                    $row->{"{$mesure}_{$site}"} = 0;
                }
            }
        }

        $articles = [];
        foreach ($rows as $row) {
            $sku = trim((string) ($row->SKU ?? ''));
            if ($sku !== '') {
                $articles[$sku] = true;
            }
        }

        if ($articles === []) {
            return;
        }

        $inArticles = "'" . implode("','", array_map(
            fn(string $a) => str_replace("'", "''", $a),
            array_keys($articles)
        )) . "'";
        $inSites = "'" . implode("','", $sites) . "'";

        $sums = [];
        foreach ($sites as $site) {
            $sums[] = "SUM(CASE WHEN SITE = '{$site}' THEN STOCK_REEL ELSE 0 END) AS STOCK_REEL_{$site}";
            $sums[] = "SUM(CASE WHEN SITE = '{$site}' THEN STOCK_INTERNE ELSE 0 END) AS STOCK_INTERNE_{$site}";
        }

        $stock = [];
        foreach ($this->mssqlSei->executeQuery("
            SELECT ARTICLE, " . implode(', ', $sums) . "
            FROM MASTER_TABLES.STOCK_ALLOCATION
            WHERE STATUS_STOCK = 'A1' AND SITE IN ({$inSites}) AND ARTICLE IN ({$inArticles})
            GROUP BY ARTICLE
        ") as $r) {
            $stock[trim((string) $r->ARTICLE)] = $r;
        }

        $transit = [];
        foreach ($this->mssqlSei->executeQuery("
            SELECT ITMREF_0, SITE_RECEPTION, SUM(QTE_RESTANTE) AS QTE
            FROM MASTER_TABLES.COMMANDES_INTERSITES
            WHERE SITE_RECEPTION IN ({$inSites}) AND ITMREF_0 IN ({$inArticles})
            GROUP BY ITMREF_0, SITE_RECEPTION
        ") as $r) {
            $transit[trim((string) $r->ITMREF_0)][trim((string) $r->SITE_RECEPTION)] = (float) $r->QTE;
        }

        $fournisseur = [];
        foreach ($this->mssqlSei->executeQuery("
            SELECT POQ.ITMREF_0, POQ.PRHFCY_0, SUM(POQ.QTYUOM_0 - POQ.RCPQTYSTU_0) AS QTE
            FROM X3_LCS.PORDERQ POQ
            INNER JOIN X3_LCS.PORDER POH ON POQ.POHNUM_0 = POH.POHNUM_0
            WHERE POQ.LINCLEFLG_0 = 1 AND POH.BETFCY_0 <> 2
              AND POQ.PRHFCY_0 IN ({$inSites}) AND POQ.ITMREF_0 IN ({$inArticles})
            GROUP BY POQ.ITMREF_0, POQ.PRHFCY_0
        ") as $r) {
            $fournisseur[trim((string) $r->ITMREF_0)][trim((string) $r->PRHFCY_0)] = (float) $r->QTE;
        }

        foreach ($rows as $row) {
            $sku = trim((string) ($row->SKU ?? ''));

            foreach ($sites as $site) {
                $interne = (float) ($stock[$sku]->{"STOCK_INTERNE_{$site}"} ?? 0);
                $reel    = (float) ($stock[$sku]->{"STOCK_REEL_{$site}"} ?? 0);
                $enRoute = $transit[$sku][$site] ?? 0.0;
                $enCours = $fournisseur[$sku][$site] ?? 0.0;

                $row->{"STOCK_INTERNE_{$site}"} = $interne;
                $row->{"STOCK_REEL_{$site}"}    = $reel;
                $row->{"EN_TRANSIT_{$site}"}    = $enRoute;
                $row->{"STOCK_A_TERME_TRANSIT_{$site}"} = $reel + $enRoute;
                $row->{"STOCK_A_TERME_BACKLOG_FOURNISSEUR_{$site}"} = $reel + $enRoute + $enCours;
            }
        }
    }

    /** Remplace les deux marqueurs stock selon que l'option est cochée ou non. */
    private function applyStockPlaceholders(string $sql, bool $includeStock): string
    {
        return str_replace(
            ['{{STOCK_COLUMNS}}', '{{STOCK_JOINS}}'],
            $includeStock ? [$this->stockColumnsSql(), $this->stockJoinSql()] : ['', ''],
            $sql
        );
    }

    public function getDistinctValues(string $field, array $filterModel = [], array $collections = [], bool $includeStock = false): array
    {
        $fieldMap = $this->getFieldMap($includeStock);

        if (!isset($fieldMap[$field])) {
            return [];
        }

        try {
            $baseSql = $this->sqlFileLoader->load('Sei/backlog_client_v2.sql');

            $fromPos = stripos($baseSql, 'FROM X3_LCS');
            if ($fromPos === false) {
                return [];
            }

            $fromClause = str_replace(['{{ORDER_BY}}', '{{PAGINATION}}'], '', substr($baseSql, $fromPos));
            $fromClause = $this->applyStockPlaceholders($fromClause, $includeStock);

            $builder     = new AgGridSqlBuilder(SsrmRequest::fromArray(['filterModel' => $filterModel]), $fieldMap);
            $whereClause = $builder->buildWhereClause() . $this->buildCollectionsClause($collections);
            $fromClause  = str_replace('{{WHERE_CLAUSE}}', $whereClause, $fromClause);

            $rows = $this->mssqlSei->executeQuery("SELECT DISTINCT {$fieldMap[$field]} AS val\n{$fromClause}\nORDER BY val");

            return array_values(array_filter(
                array_map(fn($r) => $r->val ?? null, $rows),
                fn($v) => $v !== null && $v !== ''
            ));
        } catch (\Throwable $e) {
            $this->logger->error('BacklogClientV2::getDistinctValues', ['field' => $field, 'error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Un bloc de lignes + total pour la scrollbar. Il faut au moins une collection, ou l'option "Tout".
     */
    public function getPaginated(SsrmRequest $request): SsrmResponse
    {
        try {
            $isExport          = (bool) $request->getOption('isExport', false);
            $includeStock      = (bool) $request->getOption('includeStock', false);
            $collectionsClause = $this->resolveCollectionsClause($request);

            if ($collectionsClause === null) {
                return new SsrmResponse(rows: [], lastRow: 0, totals: []);
            }

            // À l'écran, le stock n'est jamais joint : il est ajouté après coup pour les seuls
            // articles du bloc. La whitelist reste donc celle des colonnes de commande.
            $builder = new AgGridSqlBuilder($request, $this->getFieldMap());

            $whereClause = $builder->buildWhereClause() . $collectionsClause;
            $orderBy     = $builder->buildOrderByClause('SOQ.SOHNUM_0 ASC');
            $pagination  = $builder->buildPaginationClause();

            $totalRows = 0;
            $totals    = [];

            if ($request->getOffset() === 0 && !$isExport) {
                [$totalRows, $totals] = $this->computeTotals($whereClause);

                if ($totalRows === 0) {
                    return new SsrmResponse(rows: [], lastRow: 0, totals: []);
                }
            }

            $sql = $this->sqlFileLoader->load('Sei/backlog_client_v2.sql');
            $sql = str_replace(['{{WHERE_CLAUSE}}', '{{ORDER_BY}}', '{{PAGINATION}}'], [$whereClause, $orderBy, $pagination], $sql);
            $sql = $this->applyStockPlaceholders($sql, false);

            $rows = $this->mssqlSei->executeQuery($sql);
            $this->addEurAmounts($rows);

            if ($includeStock) {
                $this->enrichWithStock($rows);
            }

            return new SsrmResponse(
                rows: $rows,
                lastRow: $request->getOffset() === 0 ? $totalRows : null,
                totals: $totals,
            );
        } catch (\Throwable $e) {
            $this->graphMailer->notifyError('❌ LCS Erreur Backlog Client v2 SSRM', $e);
            $this->logger->error('LCS Erreur Backlog Client v2 SSRM', ['exception' => $e]);
            return new SsrmResponse(rows: [], lastRow: 0, totals: []);
        }
    }

    /**
     * Écrit toutes les lignes filtrées/triées en CSV (UTF-8 avec BOM, ligne "sep=;", séparateur ";"),
     * en lisant les lignes une par une : pas de limite de volume et peu de mémoire.
     *
     * @param resource $out
     * @param array<string, array{header: string, type: string}> $columns field => en-tête et type, dans l'ordre d'export
     */
    /**
     * TEST DE PERFORMANCE (stat v3) — écrit toutes les lignes en JSON, sans pagination
     * ni stock, pour comparer un chargement client-side au mode SSRM.
     *
     * Les lignes sont diffusées une par une : un executeQuery() sur l'ensemble du jeu
     * de résultats demanderait environ 1,4 Go de mémoire PHP.
     *
     * @param resource $out
     */
    public function writeAllRowsAsJson($out, SsrmRequest $request): void
    {
        $collectionsClause = $this->resolveCollectionsClause($request);

        if ($collectionsClause === null) {
            fwrite($out, '[]');

            return;
        }

        // Ici le stock passe par la jointure, contrairement a la grille paginee : on veut
        // toutes les lignes, donc l'agregat STOCK_ALLOCATION est calcule une fois et sert
        // a l'ensemble. La methode par requetes ciblees supposerait un WHERE ARTICLE IN
        // de plusieurs milliers de valeurs, et interdirait la diffusion en flux.
        $includeStock = (bool) $request->getOption('includeStock', false);

        $builder = new AgGridSqlBuilder($request, $this->getFieldMap($includeStock));
        $sql = $this->sqlFileLoader->load('Sei/backlog_client_v2.sql');

        // Aucun ORDER BY : la grille est client-side, c'est AG Grid qui trie. Un tri
        // serveur obligerait SQL Server a trier les 172 000 lignes avant de rendre la
        // premiere, ce qui fait depasser le delai de connexion sans rien apporter.
        $sql = str_replace(
            ['{{WHERE_CLAUSE}}', '{{ORDER_BY}}', '{{PAGINATION}}'],
            [$builder->buildWhereClause() . $collectionsClause, '', ''],
            $sql
        );
        $sql = $this->applyStockPlaceholders($sql, $includeStock);

        $taux = $this->divers->getExchangeRatesValues();

        fwrite($out, '[');
        $premiere = true;

        foreach ($this->mssqlSei->iterateQuery($sql) as $row) {
            $row  = $this->helpers->convertArrayToUtf8($row);
            $rate = $taux[trim((string) $row['CUR_0'])] ?? null;

            $row['PRIX_EUR'] = $rate !== null && (float) $rate > 0
                ? round((float) $row['PRIX'] / (float) $rate, 2)
                : 0.0;

            fwrite($out, ($premiere ? '' : ',') . json_encode($row, JSON_UNESCAPED_UNICODE));
            $premiere = false;
        }

        fwrite($out, ']');
    }

    public function writeCsv($out, SsrmRequest $request, array $columns): void
    {
        $collectionsClause = $this->resolveCollectionsClause($request);
        if ($collectionsClause === null) {
            throw new \InvalidArgumentException('Aucune collection sélectionnée.');
        }

        $includeStock = (bool) $request->getOption('includeStock', false);

        $builder = new AgGridSqlBuilder($request, $this->getFieldMap($includeStock));
        $sql = $this->sqlFileLoader->load('Sei/backlog_client_v2.sql');
        // Tri uniquement si l'utilisateur en a demandé un : sinon SQL Server devrait
        // ordonner les 172 000 lignes avant de rendre la première, ce qui retarde tout
        // l'export sans aucun bénéfice.
        $sql = str_replace(
            ['{{WHERE_CLAUSE}}', '{{ORDER_BY}}', '{{PAGINATION}}'],
            [$builder->buildWhereClause() . $collectionsClause, $builder->buildOrderByClause(''), ''],
            $sql
        );
        $sql = $this->applyStockPlaceholders($sql, $includeStock);

        $taux = $this->divers->getExchangeRatesValues();

        fwrite($out, "\xEF\xBB\xBF" . 'sep=;' . PHP_EOL);
        fputcsv($out, array_column($columns, 'header'), ';', '"', '');

        foreach ($this->mssqlSei->iterateQuery($sql) as $row) {
            $row  = $this->helpers->convertArrayToUtf8($row);
            $rate = $taux[trim((string) $row['CUR_0'])] ?? null;
            $row['PRIX_EUR'] = $rate !== null && (float) $rate > 0
                ? (float) $row['PRIX'] / (float) $rate
                : 0.0;

            $line = [];
            foreach ($columns as $field => $column) {
                $line[] = $this->formatCsvValue($row[$field] ?? '', $column['type']);
            }
            fputcsv($out, $line, ';', '"', '');
        }
    }

    /**
     * DIAGNOSTIC TEMPORAIRE — mesure le transport pur depuis le SEI Cube.
     *
     * Requête volontairement triviale (ni jointure, ni tri, ni lecture de table métier) :
     * des lignes de taille fixe générées par le serveur. Le seul coût mesuré est donc
     * celui du tuyau entre PHP et MSSQL. À comparer entre deux environnements.
     *
     * @return array{total: float, first_row: float, rows: int, bytes: int}
     */
    public function benchRaw(int $rows, int $rowSize): array
    {
        $start = microtime(true);

        $sql = sprintf(
            'SELECT TOP (%d) REPLICATE(CAST(\'x\' AS varchar(max)), %d) AS D
             FROM sys.all_objects a CROSS JOIN sys.all_objects b',
            $rows,
            $rowSize
        );

        $iterator = $this->mssqlSei->iterateQuery($sql);
        $iterator->rewind();
        $firstRow = microtime(true) - $start;

        $count = 0;
        $bytes = 0;

        while ($iterator->valid()) {
            $bytes += strlen((string) $iterator->current()['D']);
            ++$count;
            $iterator->next();
        }

        return [
            'total'     => microtime(true) - $start,
            'first_row' => $firstRow,
            'rows'      => $count,
            'bytes'     => $bytes,
        ];
    }

    /**
     * DIAGNOSTIC TEMPORAIRE — copie instrumentée de writeCsv(), sans HTTP ni réseau client.
     * Sépare le temps passé à attendre les lignes de MSSQL de celui passé à les formater,
     * pour identifier lequel des deux plafonne sur un environnement donné.
     *
     * @param array<string, array{header: string, type: string}> $columns
     * @return array{total: float, first_row: float, fetch: float, format: float, rows: int, bytes: int}
     */
    public function benchCsv(SsrmRequest $request, array $columns, ?string $outPath, int $limit = 0): array
    {
        $start = microtime(true);

        $collectionsClause = $this->resolveCollectionsClause($request);
        if ($collectionsClause === null) {
            throw new \InvalidArgumentException('Aucune collection sélectionnée.');
        }

        $includeStock = (bool) $request->getOption('includeStock', false);

        $builder = new AgGridSqlBuilder($request, $this->getFieldMap($includeStock));
        $sql = $this->sqlFileLoader->load('Sei/backlog_client_v2.sql');
        // Tri uniquement si l'utilisateur en a demandé un : sinon SQL Server devrait
        // ordonner les 172 000 lignes avant de rendre la première, ce qui retarde tout
        // l'export sans aucun bénéfice.
        $sql = str_replace(
            ['{{WHERE_CLAUSE}}', '{{ORDER_BY}}', '{{PAGINATION}}'],
            [$builder->buildWhereClause() . $collectionsClause, $builder->buildOrderByClause(''), ''],
            $sql
        );
        $sql = $this->applyStockPlaceholders($sql, $includeStock);

        $taux = $this->divers->getExchangeRatesValues();
        $out  = $outPath === null ? fopen('/dev/null', 'w') : fopen($outPath, 'w');

        fwrite($out, "\xEF\xBB\xBF" . 'sep=;' . PHP_EOL);
        fputcsv($out, array_column($columns, 'header'), ';', '"', '');

        $fetch = 0.0;
        $format = 0.0;
        $rows = 0;

        // rewind() déclenche la requête et attend la première ligne
        $iterator = $this->mssqlSei->iterateQuery($sql);
        $t = microtime(true);
        $iterator->rewind();
        $fetch += microtime(true) - $t;
        $firstRow = microtime(true) - $start;

        while ($iterator->valid()) {
            $row = $iterator->current();

            $t = microtime(true);
            $row  = $this->helpers->convertArrayToUtf8($row);
            $rate = $taux[trim((string) $row['CUR_0'])] ?? null;
            $row['PRIX_EUR'] = $rate !== null && (float) $rate > 0
                ? (float) $row['PRIX'] / (float) $rate
                : 0.0;

            $line = [];
            foreach ($columns as $field => $column) {
                $line[] = $this->formatCsvValue($row[$field] ?? '', $column['type']);
            }
            fputcsv($out, $line, ';', '"', '');
            $format += microtime(true) - $t;

            ++$rows;
            if ($limit > 0 && $rows >= $limit) {
                break;
            }

            $t = microtime(true);
            $iterator->next();
            $fetch += microtime(true) - $t;
        }

        $bytes = (int) ftell($out);
        fclose($out);

        return [
            'total'     => microtime(true) - $start,
            'first_row' => $firstRow,
            'fetch'     => $fetch,
            'format'    => $format,
            'rows'      => $rows,
            'bytes'     => $bytes,
        ];
    }

    // Décimales avec virgule pour qu'Excel en français les reconnaisse comme des nombres
    private function formatCsvValue(mixed $value, string $type): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        return match ($type) {
            'decimal', 'percent' => number_format((float) $value, 2, ',', ''),
            'integer'            => (string) (int) round((float) $value),
            default              => trim((string) $value),
        };
    }

    /**
     * @return array{0: int, 1: array<string, int|float>}
     */
    private function computeTotals(string $whereClause): array
    {
        $result = $this->mssqlSei->executeQuery($this->buildAggregateSql($whereClause));
        if ($result === []) {
            return [0, []];
        }

        $taux     = $this->divers->getExchangeRatesValues();
        $nbRows   = 0;
        $quantite = 0.0;
        $prixEur  = 0.0;

        foreach ($result as $row) {
            $nbRows   += (int) ($row->NB_ROWS ?? 0);
            $quantite += (float) ($row->QUANTITE ?? 0);

            $rate = $taux[$row->DEVISE ?? ''] ?? null;
            if ($rate !== null && (float) $rate > 0) {
                $prixEur += (float) ($row->TOTAL_PRIX ?? 0) / (float) $rate;
            }
        }

        // Mêmes totaux que le Backlog Client X3 : quantité à livrer et montant EUR.
        return [$nbRows, [
            'QUANTITE' => (int) round($quantite),
            'PRIX_EUR' => round($prixEur, 2),
        ]];
    }

    private function buildAggregateSql(string $whereClause): string
    {
        return "
        SELECT
            SOH.CUR_0 AS DEVISE,
            COUNT(*) AS NB_ROWS,
            SUM(SOQ.QTY_0 - (SOQ.DLVQTY_0 + SOQ.ODLQTY_0)) AS QUANTITE,
            SUM(SOP.NETPRINOT_0 * (SOQ.QTY_0 - (SOQ.DLVQTY_0 + SOQ.ODLQTY_0)) * (1 - (ISNULL(SVT.DTAAMT_0, 0)/100))) AS TOTAL_PRIX
        FROM X3_LCS.SORDERQ SOQ
        INNER JOIN X3_LCS.SORDER  SOH ON SOQ.SOHNUM_0 = SOH.SOHNUM_0
        INNER JOIN X3_LCS.SORDERP SOP ON SOQ.SOHNUM_0 = SOP.SOHNUM_0 AND SOQ.ITMREF_0 = SOP.ITMREF_0 AND SOQ.SOPLIN_0 = SOP.SOPLIN_0
        INNER JOIN X3_LCS.ITMMASTER ITM ON SOQ.ITMREF_0 = ITM.ITMREF_0
        CROSS APPLY (
            SELECT
                CASE WHEN CHARINDEX('_', ITM.ITMREF_0) > 0 THEN LEFT(ITM.ITMREF_0, CHARINDEX('_', ITM.ITMREF_0) - 1) ELSE ITM.ITMREF_0 END AS ARTICLE_BASE,
                CASE WHEN CHARINDEX('_', ITM.ITMREF_0) > 0 THEN SUBSTRING(ITM.ITMREF_0, CHARINDEX('_', ITM.ITMREF_0) + 1, 50) ELSE NULL END AS VARIANT_VAL
        ) AS SPLIT
        INNER JOIN X3_LCS.BPCUSTOMER BPC ON SOH.BPCORD_0 = BPC.BPCNUM_0
        LEFT  JOIN X3_LCS.BPCUSTOMER BPC_INV ON SOH.BPCINV_0 = BPC_INV.BPCNUM_0
        INNER JOIN X3_LCS.BPADDRESS BPA ON BPC.BPCNUM_0 = BPA.BPANUM_0 AND BPA.BPAADD_0 = SOH.BPAADD_0
        LEFT  JOIN X3_LCS.SALESREP REP1 ON BPC.REP_0 = REP1.REPNUM_0
        LEFT  JOIN X3_LCS.SALESREP REP2 ON BPC.REP_1 = REP2.REPNUM_0
        LEFT  JOIN X3_LCS.ATEXTRA ATX  ON ATX.IDENT2_0  = BPC.TSCCOD_2    AND ATX.CODFIC_0  = 'ATABDIV' AND ATX.LANGUE_0  = 'FRA' AND ATX.ZONE_0  = 'LNGDES' AND ATX.IDENT1_0  = '32'
        LEFT  JOIN X3_LCS.ATEXTRA ATX4 ON ATX4.IDENT2_0 = BPC.ZGROUPIND_0 AND ATX4.CODFIC_0 = 'ATABDIV' AND ATX4.LANGUE_0 = 'FRA' AND ATX4.ZONE_0 = 'LNGDES' AND ATX4.IDENT1_0 = '6021'
        LEFT  JOIN X3_LCS.ATEXTRA ATX5 ON ATX5.CODFIC_0 = 'TABLINCFG' AND ATX5.LANGUE_0 = 'FRA' AND ATX5.IDENT1_0 = ITM.CFGLIN_0
        LEFT  JOIN X3_LCS.ATEXTRA ATX6 ON ATX6.IDENT2_0 = BPC.ZGRPCOD_0   AND ATX6.CODFIC_0 = 'ATABDIV' AND ATX6.LANGUE_0 = 'FRA' AND ATX6.ZONE_0 = 'LNGDES' AND ATX6.IDENT1_0 = '6028'
        LEFT  JOIN X3_LCS.ZITMCOL ITC ON ITC.ITMREF_0 = SPLIT.ARTICLE_BASE AND ITC.YCOLLECT_0 = SOQ.YCOLLECT_0
        LEFT  JOIN X3_LCS.SVCRFOOT SVT ON SOH.SOHNUM_0 = SVT.VCRNUM_0 AND SVT.DTA_0 = 1
        " . $this->supplierJoins($whereClause) . "
        WHERE " . self::BASE_WHERE . "
            $whereClause
        GROUP BY SOH.CUR_0
        ";
    }

    /**
     * Jointures PO / CI des totaux, ajoutées seulement si le filtre porte sur PO EN COURS ou
     * Date arrivée prévue (mêmes sous-requêtes que backlog_client_v2.sql) : sinon inutiles et coûteuses.
     */
    private function supplierJoins(string $whereClause): string
    {
        $joins = '';

        if (str_contains($whereClause, 'PO.PO_EN_COURS') || str_contains($whereClause, 'PO.DATE_COMMANDE_FOURNISSEUR')) {
            $joins .= "
        LEFT  JOIN (
            SELECT POQ.ITMREF_0, POQ.PRHFCY_0,
                   SUM(POQ.QTYUOM_0 - POQ.RCPQTYSTU_0) AS PO_EN_COURS,
                   CONVERT(varchar(10), MIN(POQ.EXTRCPDAT_0), 23) AS DATE_COMMANDE_FOURNISSEUR
            FROM X3_LCS.PORDERQ POQ
            INNER JOIN X3_LCS.PORDER POH ON POQ.POHNUM_0 = POH.POHNUM_0
            WHERE POQ.LINCLEFLG_0 = 1 AND POH.BETFCY_0 <> 2
            GROUP BY POQ.ITMREF_0, POQ.PRHFCY_0
        ) PO ON PO.ITMREF_0 = SOQ.ITMREF_0 AND PO.PRHFCY_0 = SOH.STOFCY_0";
        }

        if (str_contains($whereClause, 'CI.DATE_COMMANDE_FOURNISSEUR')) {
            $joins .= "
        LEFT  JOIN (
            SELECT ITMREF_0, SITE_RECEPTION,
                   CONVERT(varchar(10), MIN(EXTRCPDAT_0), 23) AS DATE_COMMANDE_FOURNISSEUR
            FROM MASTER_TABLES.COMMANDES_INTERSITES
            GROUP BY ITMREF_0, SITE_RECEPTION
        ) CI ON CI.ITMREF_0 = SOQ.ITMREF_0 AND CI.SITE_RECEPTION = SOH.STOFCY_0";
        }

        // Un filtre sur une colonne stock référence STK.* : sans la jointure, les totaux échouent
        // avec "multi-part identifier could not be bound".
        if (str_contains($whereClause, 'STK.')) {
            $joins .= $this->stockJoinSql();
        }

        return $joins;
    }

    /**
     * Montant EUR = montant devise / taux de la devise (même règle qu'État des commandes clients).
     */
    private function addEurAmounts(array $rows): void
    {
        if ($rows === []) {
            return;
        }

        $taux = $this->divers->getExchangeRatesValues();

        foreach ($rows as $row) {
            $rate = $taux[$row->CUR_0] ?? null;

            $row->PRIX_EUR = $rate !== null && (float) $rate > 0
                ? round((float) $row->PRIX / (float) $rate, 2)
                : 0.0;
        }
    }
}
