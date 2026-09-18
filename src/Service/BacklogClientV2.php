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
 * Backlog Client v2 (SSRM) : requête d'État des commandes clients avec le filtre du Backlog Client
 * (lignes non soldées, hors brouillons, hors intersites), sans stock, backlog fournisseur ni transit.
 *
 * Toute jointure ajoutée à backlog_client_v2.sql doit aussi l'être dans buildAggregateSql(),
 * sinon le filtre sur la colonne correspondante fait échouer les totaux.
 */
class BacklogClientV2
{
    private const string BASE_WHERE = "SOQ.SOQSTA_0 <> 3 AND SOH.ZSOHVALSTA_0 <> 3 AND BPC.BCGCOD_0 <> 'INTER'";

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
     * ATX5 = AGE (TABLINCFG), ATX6 = GROUP_CODE (6028), ATX7 = DISTRIBUTION_CHANNEL (34).
     */
    private function getFieldMap(): array
    {
        return [
            'PAYS'                  => 'SOH.BPCCRYNAM_0',
            'LIGNE'                 => 'SOQ.SOPLIN_0',
            'SITE'                  => 'SOH.STOFCY_0',
            'MAINNETWORK'           => 'ATX.TEXTE_0',
            'DISTRIBUTION_CHANNEL'  => 'ATX7.TEXTE_0',
            'CLIENT'                => 'SOH.BPCINV_0',
            'NOM_CLIENT'            => 'BPC_INV.BPCNAM_0',
            'CLIENT_COMMANDE'       => 'SOH.BPCORD_0',
            'NOM_CLIENT_COMMANDE'   => 'SOH.BPCNAM_0',
            'PAIEMENT'              => 'SOH.PTE_0',
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
            'STATUT_ARTICLE'        => 'ITM.ITMSTA_0',
            'QUANTITE_COMMANDE'     => 'SOQ.QTY_0',
            'QUANTITE_LIVREE'       => '(SOQ.DLVQTY_0 + SOQ.ODLQTY_0)',
            'QUANTITE'              => '(SOQ.QTY_0 - (SOQ.DLVQTY_0 + SOQ.ODLQTY_0))',
            'QUANTITE_ALLOUEE'      => 'SOQ.ALLQTY_0',
            'QUANTITE_EN_RUPTURE'   => 'SOQ.SHTQTY_0',
            'RESTE_A_ALLOUER'       => '(SOQ.QTY_0 - SOQ.ALLQTY_0 - SOQ.DLVQTY_0 - SOQ.ODLQTY_0)',
            'CUR_0'                 => 'SOH.CUR_0',
            'PRICE_HT'              => 'SOP.NETPRINOT_0',
            'GROSS_PRICE_HT'        => 'SOP.GROPRI_0',
            'REMISE_AUTO'           => 'SOP.DISCRGVAL1_0',
            'REMISE_MANU'           => 'SOP.DISCRGVAL2_0',
            'REMISE_GLOBAL'         => 'ISNULL(SVT.DTAAMT_0, 0)',
            'CLIENT_LIVRE'          => 'SOH.BPDNAM_0',
            'INDEPENDANT_GROUPMENT' => 'ATX4.TEXTE_0',
            'CODE_POSTAL'           => 'BPA.POSCOD_0',
            'VILLE'                 => 'BPA.CTY_0',
            'ZCLASSE_0'             => 'SOH.ZCLASSE_0',
            'PRIX_NET_UNITAIRE_HT'  => 'SOP.NETPRINOT_0 * (1 - (ISNULL(SVT.DTAAMT_0, 0)/100))',
        ];
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

    public function getDistinctValues(string $field, array $filterModel = [], array $collections = []): array
    {
        $fieldMap = $this->getFieldMap();

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
            $collectionsClause = $this->resolveCollectionsClause($request);

            if ($collectionsClause === null) {
                return new SsrmResponse(rows: [], lastRow: 0, totals: []);
            }

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

            $rows = $this->mssqlSei->executeQuery($sql);
            $this->addEurAmounts($rows);

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
    public function writeCsv($out, SsrmRequest $request, array $columns): void
    {
        $collectionsClause = $this->resolveCollectionsClause($request);
        if ($collectionsClause === null) {
            throw new \InvalidArgumentException('Aucune collection sélectionnée.');
        }

        $builder = new AgGridSqlBuilder($request, $this->getFieldMap());
        $sql = $this->sqlFileLoader->load('Sei/backlog_client_v2.sql');
        $sql = str_replace(
            ['{{WHERE_CLAUSE}}', '{{ORDER_BY}}', '{{PAGINATION}}'],
            [$builder->buildWhereClause() . $collectionsClause, $builder->buildOrderByClause('SOQ.SOHNUM_0 ASC'), ''],
            $sql
        );

        $taux = $this->divers->getExchangeRatesValues();

        fwrite($out, "\xEF\xBB\xBF" . 'sep=;' . PHP_EOL);
        fputcsv($out, array_column($columns, 'header'), ';', '"', '');

        foreach ($this->mssqlSei->iterateQuery($sql) as $row) {
            $row  = $this->helpers->convertArrayToUtf8($row);
            $rate = $taux[trim((string) $row['CUR_0'])] ?? null;
            foreach (['COMMANDE', 'LIVREE', 'A_LIVRER'] as $k) {
                $row["MONTANT_{$k}_EUR"] = $rate !== null && (float) $rate > 0
                    ? (float) $row["MONTANT_{$k}_DEVISE"] / (float) $rate
                    : 0.0;
            }

            $line = [];
            foreach ($columns as $field => $column) {
                $line[] = $this->formatCsvValue($row[$field] ?? '', $column['type']);
            }
            fputcsv($out, $line, ';', '"', '');
        }
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

        $taux = $this->divers->getExchangeRatesValues();
        $sum  = [
            'NB_ROWS' => 0, 'QUANTITE_COMMANDE' => 0.0, 'QUANTITE_LIVREE' => 0.0, 'QUANTITE' => 0.0,
            'QUANTITE_ALLOUEE' => 0.0, 'QUANTITE_EN_RUPTURE' => 0.0, 'RESTE_A_ALLOUER' => 0.0,
            'MONTANT_COMMANDE_DEVISE' => 0.0, 'MONTANT_LIVREE_DEVISE' => 0.0, 'MONTANT_A_LIVRER_DEVISE' => 0.0,
            'MONTANT_COMMANDE_EUR' => 0.0, 'MONTANT_LIVREE_EUR' => 0.0, 'MONTANT_A_LIVRER_EUR' => 0.0,
        ];

        foreach ($result as $row) {
            $sum['NB_ROWS'] += (int) ($row->NB_ROWS ?? 0);
            foreach (['QUANTITE_COMMANDE', 'QUANTITE_LIVREE', 'QUANTITE', 'QUANTITE_ALLOUEE', 'QUANTITE_EN_RUPTURE', 'RESTE_A_ALLOUER'] as $k) {
                $sum[$k] += (float) ($row->$k ?? 0);
            }

            $rate = $taux[$row->DEVISE ?? ''] ?? null;
            foreach (['COMMANDE', 'LIVREE', 'A_LIVRER'] as $k) {
                $montant = (float) ($row->{"MONTANT_{$k}_DEVISE"} ?? 0);
                $sum["MONTANT_{$k}_DEVISE"] += $montant;
                if ($rate !== null && (float) $rate > 0) {
                    $sum["MONTANT_{$k}_EUR"] += $montant / (float) $rate;
                }
            }
        }

        $totals = [];
        foreach ($sum as $k => $v) {
            if ($k === 'NB_ROWS') {
                continue;
            }
            $totals[$k] = str_starts_with($k, 'MONTANT_') ? round($v, 2) : (int) round($v);
        }

        return [$sum['NB_ROWS'], $totals];
    }

    private function buildAggregateSql(string $whereClause): string
    {
        return "
        SELECT
            SOH.CUR_0 AS DEVISE,
            COUNT(*) AS NB_ROWS,
            SUM(SOQ.QTY_0) AS QUANTITE_COMMANDE,
            SUM(SOQ.DLVQTY_0 + SOQ.ODLQTY_0) AS QUANTITE_LIVREE,
            SUM(SOQ.QTY_0 - (SOQ.DLVQTY_0 + SOQ.ODLQTY_0)) AS QUANTITE,
            SUM(SOQ.ALLQTY_0) AS QUANTITE_ALLOUEE,
            SUM(SOQ.SHTQTY_0) AS QUANTITE_EN_RUPTURE,
            SUM(SOQ.QTY_0 - SOQ.ALLQTY_0 - SOQ.DLVQTY_0 - SOQ.ODLQTY_0) AS RESTE_A_ALLOUER,
            SUM(SOP.NETPRINOT_0 * SOQ.QTY_0 * (1 - (ISNULL(SVT.DTAAMT_0, 0)/100))) AS MONTANT_COMMANDE_DEVISE,
            SUM(SOP.NETPRINOT_0 * (SOQ.DLVQTY_0 + SOQ.ODLQTY_0) * (1 - (ISNULL(SVT.DTAAMT_0, 0)/100))) AS MONTANT_LIVREE_DEVISE,
            SUM(SOP.NETPRINOT_0 * (SOQ.QTY_0 - (SOQ.DLVQTY_0 + SOQ.ODLQTY_0)) * (1 - (ISNULL(SVT.DTAAMT_0, 0)/100))) AS MONTANT_A_LIVRER_DEVISE
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
        LEFT  JOIN X3_LCS.ATEXTRA ATX7 ON ATX7.IDENT2_0 = BPC.TSCCOD_4    AND ATX7.CODFIC_0 = 'ATABDIV' AND ATX7.LANGUE_0 = 'FRA' AND ATX7.ZONE_0 = 'LNGDES' AND ATX7.IDENT1_0 = '34'
        LEFT  JOIN X3_LCS.ZITMCOL ITC ON ITC.ITMREF_0 = SPLIT.ARTICLE_BASE AND ITC.YCOLLECT_0 = SOQ.YCOLLECT_0
        LEFT  JOIN X3_LCS.SVCRFOOT SVT ON SOH.SOHNUM_0 = SVT.VCRNUM_0 AND SVT.DTA_0 = 1
        WHERE " . self::BASE_WHERE . "
            $whereClause
        GROUP BY SOH.CUR_0
        ";
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
            $rate   = $taux[$row->CUR_0] ?? null;
            $rateOk = $rate !== null && (float) $rate > 0;

            foreach (['COMMANDE', 'LIVREE', 'A_LIVRER'] as $k) {
                $row->{"MONTANT_{$k}_EUR"} = $rateOk
                    ? round((float) $row->{"MONTANT_{$k}_DEVISE"} / (float) $rate, 2)
                    : 0.0;
            }
        }
    }
}
