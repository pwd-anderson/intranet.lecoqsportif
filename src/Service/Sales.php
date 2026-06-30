<?php

namespace App\Service;

use App\Factory\MssqlManagerFactory;
use App\Infrastructure\Sql\SqlFileLoader;
use App\Service\AgGrid\Ssrm\AgGridSqlBuilder;
use App\Service\AgGrid\Ssrm\SsrmRequest;
use App\Service\AgGrid\Ssrm\SsrmResponse;
use App\Service\Tools\GraphMailer;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Mime\Email;
use Psr\Log\LoggerInterface;
use App\Service\Tools\MssqlManager;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class Sales
{
    private MssqlManager $mssqlLcs;
    private MssqlManager $mssqlSei;

    public function __construct(
        private MssqlManagerFactory $mssqlManagerFactory,
        private LoggerInterface $logger,
        private GraphMailer $graphMailer,
        private SqlFileLoader $sqlFileLoader,
        private UrlGeneratorInterface $urlGenerator,
        private RequestStack $requestStack,
        private Divers $divers,
        #[Autowire('%db.lcs%')]
        string $dbLcs,
        #[Autowire('%db.lcs_sei%')]
        string $dbLcsSei,
    )
    {
        $this->mssqlLcs = $this->mssqlManagerFactory->create($dbLcs);
        $this->mssqlSei = $this->mssqlManagerFactory->create($dbLcsSei);
    }

    public function getLivraisonsNonFacturees(): array
    {
        try {
            $query = "select
                        s.CompanyCode
                        ,c.No_
                        ,c.Name
                        ,s.[Document No_]
                        ,s.[Shipment Date]
                        ,s.No_
                        , s.[Variant Code]
                        ,SUM(S.[Qty_ Shipped Not Invoiced]) QtyShippedNotInvoiced,
                        sum(s.[Shipped Not Invoiced]) as montant_ttc, sum(s.[Shipped Not Invoiced HT]) as montant_ht
                        FROM [DB_Datalake].[nav].[Sales Line] s
                        left join [DB_Datalake].[nav].[Customer] as c on s.CompanyCode = c.CompanyCode and s.[Bill-to Customer No_] = c.No_
                        where s.[Qty_ Shipped Not Invoiced] <> 0
                        and year(s.[Created Date Time]) > 2016
                        GROUP BY s.No_, s.[Variant Code],s.[Document No_],s.CompanyCode,c.No_,c.Name,s.[Shipment Date]";

            $data = $this->mssqlLcs->executeQuery($query);
            return $data;

        } catch (\Exception $e) {
            $this->graphMailer->notifyError('❌ LCS Erreur Sales : Récupération de données Livraison non facturées', $e);
            $this->logger->error('LCS Erreur Sales : Récupération de données Livraison non facturées', ['exception' => $e]);
        }
    }

    public function getBacklogClients(): array
    {
        try {

            $query = "select s.CompanyCode AS CODE_COMPANY,
                        s.OrderSeriesNo as COLLECTION,
                        i.ItemFamilyCode as FAMILLE,
                        s.ItemNo as ARTICLE,
                        s.VariantCode AS CODE_VARIANT,
                        c.BillToNo AS CODE_CLIENT,
                        c.BillToName AS NOM_CLIENT,
                        s.OrderDocumentNo AS NO_COMMANDE,
                        s.OrderCreationDate AS DATE_COMMANDE,
                        o.RequestedDeliveryDate_L AS DATE_LIVRAISON_DEMANDEE,
                        s.OUT_Quantity AS QUANTITE,
                        s.OUT_AmountEur AS MONTANT_HT_EUR,
                        0 as STOCK_REEL
                        from BI.DWH.F_Sales s
                        left join BI.DWH.F_Sales_Orders o on s.OrderDocumentNo = o.OrderDocumentNo and s.CompanyCode = o.CompanyCode and s.OrderDocumentLineNo = o.OrderDocumentLineNo and s.VariantCode = o.VariantCode
                        left join BI.DWH.D_Item i on s.ItemNo = i.ItemNo
                        left join BI.DWH.D_Customer c on s.CustomerNo = c.Code and s.CompanyCode = c.CompanyCode
                        where 1=1
                        and s.CompanyCode = 'LCSI BV'
                        and s.OUT_Quantity <> 0
                        and s.IsBohPerimeter = 1
                        and s.LocationCode in ('DIRECT', 'DT-WHS-TH', 'LOGTXM-1', 'SF-WHS-CN1')
                        and s.SalesOrderType in ('CO', 'OP', 'PS', 'RE')";

            $backlog = $this->mssqlLcs->executeQuery($query);

            /* ======================
               Récupération stock réel
               ====================== */

            $stocks = $this->getStockReel();

            /* ======================
               Indexation du stock
               ====================== */

            $stockIndex = [];

            foreach ($stocks as $stock) {

                $key = $stock->LastSeriesNo . '|' .
                    $stock->ItemFamilyCode . '|' .
                    $stock->ItemNo . '|' .
                    $stock->VariantCode;

                $stockIndex[$key] = $stock->AvailableInventory_Deducted_NA_Quantity ?? 0;
            }

            /* ======================
               Injection stock backlog
               ====================== */

            foreach ($backlog as $row) {

                $key = $row->COLLECTION . '|' .
                    $row->FAMILLE . '|' .
                    $row->ARTICLE . '|' .
                    $row->CODE_VARIANT;

                $row->STOCK_REEL = $stockIndex[$key] ?? 0;
            }
            return $backlog;

        } catch (\Exception $e) {

            $this->graphMailer->notifyError(
                '❌ LCS Erreur Sales : Récupération de données Backlog clients',
                $e
            );

            $this->logger->error(
                'LCS Erreur Sales : Récupération de données Backlog clients',
                ['exception' => $e]
            );
        }
    }

    public function getCommandesAFacturer(): array
    {
        try {
            $query = $this->sqlFileLoader->load('Navision/commandes_a_facturer_tmp.sql');
            $data = $this->mssqlLcs->executeQuery($query);
            return $data;

        } catch (\Exception $e) {
            $this->graphMailer->notifyError('❌ LCS Erreur Commandes à Facturer : Récupération de données Ventes LCS', $e);
            $this->logger->error('LCS Erreur Commandes à Facturer : Récupération de données Ventes LCS', ['exception' => $e]);
        }
    }

    public function getStockReel(): array
    {
        try {
            $query = $this->sqlFileLoader->load('Navision/stock_reel_tmp.sql');
            $data = $this->mssqlLcs->executeQuery($query);
            return $data;
        }catch (\Exception $e) {
            $this->graphMailer->notifyError('❌ LCS Erreur Stock : Récupération de données Stock Reel LCS', $e);
            $this->logger->error('LCS Erreur Stock : Récupération de données Stock Reel LCS', ['exception' => $e]);
        }
    }

    public function getReassort(): array
    {
        try {
            $query = $this->sqlFileLoader->load('Navision/reassort_tmp.sql');
            $data = $this->mssqlLcs->executeQuery($query);

            return $data;

        } catch (\Exception $e) {
            $this->graphMailer->notifyError(
                '❌ LCS Erreur Sales : Récupération de données Reassort clients',
                $e
            );

            $this->logger->error(
                'LCS Erreur Sales : Récupération de données Reassort clients',
                ['exception' => $e]
            );

            return [];
        }
    }

    public function getCommandesAFacturerX3(): array
    {
        try {
            $query = $this->sqlFileLoader->load('Sei/commandes_a_facturer_x3.sql');
            $data = $this->mssqlSei->executeQuery($query);
            return $data;

        } catch (\Exception $e) {
            $this->graphMailer->notifyError('❌ LCS Erreur Commandes à Facturer X3 : Récupération de données Ventes LCS', $e);
            $this->logger->error('LCS Erreur Commandes à Facturer X3 : Récupération de données Ventes LCS', ['exception' => $e]);
        }
    }

    public function getExcessForSales(
        ?string $tariffGroup = null,
        array $families = [],
        array $collections = []
    ): array
    {
        try {
            $query = $this->sqlFileLoader->load('Sei/excess_for_sales.sql');

            $conditions = [];

            if ($tariffGroup) {
                $conditions[] = "GROUPE_TARIF = '" . str_replace("'", "''", $tariffGroup) . "'";
            }

            if (!empty($families)) {
                $escaped = array_map(
                    fn($f) => "'" . str_replace("'", "''", trim($f)) . "'",
                    array_filter($families, fn($f) => is_string($f) && trim($f) !== '')
                );

                if (!empty($escaped)) {
                    $conditions[] = 'FAMILLE IN (' . implode(', ', $escaped) . ')';
                }
            }

            if (!empty($collections)) {
                $escaped = array_map(
                    fn($c) => "'" . str_replace("'", "''", trim($c)) . "'",
                    array_filter($collections, fn($c) => is_string($c) && trim($c) !== '')
                );

                if (!empty($escaped)) {
                    $conditions[] = 'COLLECTION IN (' . implode(', ', $escaped) . ')';
                }
            }

            if (!empty($conditions)) {
                $query .= ' WHERE ' . implode(' AND ', $conditions);
            }

            $rows = $this->mssqlSei->executeQuery($query);

            $locale = $this->requestStack->getCurrentRequest()?->getLocale() ?? 'fr';

            $foundVariants = [];
            $grouped = [];

            foreach ($rows as $row) {
                $row = (array) $row;

                $fullArticle = $row['ARTICLE'] ?? '';
                $articleParts = explode('_', $fullArticle);
                $articleBase = $articleParts[0] ?? $fullArticle;

                $key = implode('|', [
                    $fullArticle ?? '',
                    $row['COLLECTION'] ?? '',
                    $row['GROUPE_TARIF'] ?? ''
                ]);

                if (!isset($grouped[$key])) {
                    // URL directe vers lecoqsportif.com (cascade _2 → _new_1 → _1 gérée côté front via <img onerror>)
                    $photoUrl = 'https://www.lecoqsportif.com/cdn/shop/files/' . $articleBase . '_2.jpg';

                    // URL base64 pour l'export Excel (cascade gérée côté backend)
                    $photoBase64Url = $this->urlGenerator->generate('lecoqsportif_image_base64', [
                        '_locale' => $locale,
                        'article' => $articleBase,
                    ]);

                    $grouped[$key] = [
                        'PHOTO_URL' => $photoUrl,
                        'PHOTO_BASE64_URL' => $photoBase64Url,
                        'COLLECTION' => $row['COLLECTION'] ?? null,
                        'ARTICLE' => $fullArticle ?: null,
                        'FAMILLE' => $row['FAMILLE'] ?? null,
                        'GENRE' => $row['GENRE'] ?? null,
                        'AGE_GROUP' => $row['AGE_GROUP'] ?? null,
                        'ITEM_GROUP' => $row['ITEM_GROUP'] ?? null,
                        'DESIGNATION_MODELE' => $row['DESIGNATION_MODELE'] ?? null,
                        'PRIX' => $row['PRIX'] ?? null,
                        'DEVISE' => $row['DEVISE'] ?? null,
                        'GROUPE_TARIF' => $row['GROUPE_TARIF'] ?? null,
                        'Total' => 0,
                    ];
                }

                $variant = trim((string) ($row['CODE_VARIANT'] ?? ''));
                $stockTerme = (int) ($row['STOCK_TERME'] ?? 0);

                if ($variant !== '') {
                    $foundVariants[$variant] = true;

                    if (!array_key_exists($variant, $grouped[$key])) {
                        $grouped[$key][$variant] = 0;
                    }

                    $grouped[$key][$variant] += $stockTerme;
                }

                $grouped[$key]['Total'] += $stockTerme;
            }

            $variants = array_keys($foundVariants);
            $variants = $this->sortExcessVariants($variants);

            foreach ($grouped as &$line) {
                foreach ($variants as $variant) {
                    if (!array_key_exists($variant, $line)) {
                        $line[$variant] = 0;
                    }
                }
            }
            unset($line);

            return [
                'variants' => array_merge($variants, ['Total']),
                'rows' => array_values($grouped),
            ];

        } catch (\Exception $e) {
            $this->graphMailer->notifyError(
                '❌ LCS Erreur Sales : Récupération de données Excess For Sales',
                $e
            );

            $this->logger->error(
                'LCS Erreur Sales : Récupération de données Excess For Sales',
                ['exception' => $e]
            );

            return [
                'variants' => [],
                'rows' => [],
            ];
        }
    }

    public function getExcessForSalesTariffGroups(): array
    {
        try {
            $query = "
            select distinct SPL.PLICRI1_0 as GROUPE_TARIF from X3_LCS.SPRICLIST AS SPL
            where SPL.PLI_0 = 'T10'";
            return $this->mssqlSei->executeQuery($query);

        } catch (\Exception $e) {
            $this->graphMailer->notifyError(
                '❌ LCS Erreur Sales : Récupération des groupes tarifaires Excess For Sales',
                $e
            );

            $this->logger->error(
                'LCS Erreur Sales : Récupération des groupes tarifaires Excess For Sales',
                ['exception' => $e]
            );

            return [];
        }
    }

    private function sortExcessVariants(array $variants): array
    {
        $variants = array_values(array_unique(array_filter($variants)));

        $textileOrder = [
            'XXXS', 'XXS', 'XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL'
        ];

        usort($variants, function ($a, $b) use ($textileOrder) {
            $a = trim((string) $a);
            $b = trim((string) $b);

            $aIsNumeric = is_numeric(str_replace(',', '.', $a));
            $bIsNumeric = is_numeric(str_replace(',', '.', $b));

            if ($aIsNumeric && $bIsNumeric) {
                return (float) str_replace(',', '.', $a) <=> (float) str_replace(',', '.', $b);
            }

            $aTextilePos = array_search(strtoupper($a), $textileOrder, true);
            $bTextilePos = array_search(strtoupper($b), $textileOrder, true);

            $aIsTextile = $aTextilePos !== false;
            $bIsTextile = $bTextilePos !== false;

            if ($aIsTextile && $bIsTextile) {
                return $aTextilePos <=> $bTextilePos;
            }

            if ($aIsNumeric && !$bIsNumeric) {
                return -1;
            }

            if (!$aIsNumeric && $bIsNumeric) {
                return 1;
            }

            if ($aIsTextile && !$bIsTextile) {
                return -1;
            }

            if (!$aIsTextile && $bIsTextile) {
                return 1;
            }

            return strnatcasecmp($a, $b);
        });

        return $variants;
    }

    public function getStockPourBacklogClientsX3(): array
    {
        try {
            $query = "
            SELECT
                ARTICLE,

                -- WLOGM
                SUM(CASE WHEN SITE = 'WLOGM' THEN STOCK_REEL ELSE 0 END)     AS STOCK_REEL_WLOGM,
                SUM(CASE WHEN SITE = 'WLOGM' THEN STOCK_INTERNE ELSE 0 END)  AS STOCK_INTERNE_WLOGM,

                -- WSFCN
                SUM(CASE WHEN SITE = 'WSFCN' THEN STOCK_REEL ELSE 0 END)     AS STOCK_REEL_WSFCN,
                SUM(CASE WHEN SITE = 'WSFCN' THEN STOCK_INTERNE ELSE 0 END)  AS STOCK_INTERNE_WSFCN,

                -- WTAKH
                SUM(CASE WHEN SITE = 'WTAKH' THEN STOCK_REEL ELSE 0 END)     AS STOCK_REEL_WTAKH,
                SUM(CASE WHEN SITE = 'WTAKH' THEN STOCK_INTERNE ELSE 0 END)  AS STOCK_INTERNE_WTAKH,

                -- WDTTH
                SUM(CASE WHEN SITE = 'WDTTH' THEN STOCK_REEL ELSE 0 END)     AS STOCK_REEL_WDTTH,
                SUM(CASE WHEN SITE = 'WDTTH' THEN STOCK_INTERNE ELSE 0 END)  AS STOCK_INTERNE_WDTTH

            FROM [SEICube].[MASTER_TABLES].[STOCK_ALLOCATION] s

            WHERE
                s.STATUS_STOCK = 'A1'
                AND s.SITE IN ('WLOGM','WSFCN','WTAKH','WDTTH')

            GROUP BY ARTICLE;
            ";
            $data = $this->mssqlSei->executeQuery($query);
            return $data;

        } catch (\Exception $e) {
            $this->graphMailer->notifyError('❌ LCS Erreur Stock à Terme X3 : Récupération de données stock', $e);
            $this->logger->error('LCS Erreur Stock à Terme X3 : Récupération de données stock', ['exception' => $e]);
        }
    }

    public function getPoidFamilleParVariant(?string $collection = null, ?string $family = null, ?string $type = null): array
    {
        try {
            $sql = $this->sqlFileLoader->load('Sei/poid_famille_variant.sql');

            // Nettoyage strict
            $collection = $collection !== null ? trim($collection) : null;
            $collection = $collection !== '' ? preg_replace('/[^A-Za-z0-9_\-]/', '', $collection) : null;

            $family = $family !== null ? trim($family) : null;
            $family = $family !== '' ? preg_replace('/[^A-Za-z0-9_\-]/', '', $family) : null;

            $type = $type !== null ? trim($type) : null;
            $type = $type !== '' ? preg_replace('/[^A-Za-z0-9_\-]/', '', $type) : null;

            // Construction des clauses WHERE
            $collectionWhere = '';
            if ($collection) {
                $collectionWhere = " AND C.SERIESCODE = '{$collection}'";
            }

            $familyWhere = '';
            if ($family) {
                $familyWhere = " AND C.ITEMFAMILYCODE = '{$family}'";
            }

            $typeWhere = '';
            if ($type) {
                $typeWhere = " AND (CASE WHEN C.AGEGROUP = 'ADULT' THEN C.GENUSCODE ELSE 'KIDS' END) = '{$type}'";
            }

            $sql = str_replace('{{COLLECTION_WHERE}}', $collectionWhere, $sql);
            $sql = str_replace('{{FAMILY_WHERE}}', $familyWhere, $sql);
            $sql = str_replace('{{TYPE_WHERE}}', $typeWhere, $sql);

            return $this->mssqlSei->executeQuery($sql);

        } catch (\Exception $e) {
            $this->graphMailer->notifyError('❌ LCS Erreur Poid Famille/Variant : Récupération de données sales', $e);
            $this->logger->error('LCS Erreur Poid Famille/Variant : Récupération de données sales', ['exception' => $e]);

            return [];
        }
    }

    public function getBestDemandPerStyle(
        ?string $collection = null,
        ?string $family = null,
        ?string $type = null,
        ?string $itemGroup = null
    ): array
    {
        try {
            $sql = $this->sqlFileLoader->load('Sei/best_demand_per_style.sql');

            $collection = $collection !== null ? trim($collection) : null;
            $collection = $collection !== '' ? preg_replace('/[^A-Za-z0-9_\-]/', '', $collection) : null;

            $family = $family !== null ? trim($family) : null;
            $family = $family !== '' ? preg_replace('/[^A-Za-z0-9_\-]/', '', $family) : null;

            $collectionWhere = '';
            if ($collection) {
                $collectionWhere = " AND C.SERIESCODE = '{$collection}'";
            }

            $familyWhere = '';
            if ($family) {
                $familyWhere = " AND C.ITEMFAMILYCODE = '{$family}'";
            }

            $sql = str_replace('{{COLLECTION_WHERE}}', $collectionWhere, $sql);
            $sql = str_replace('{{FAMILY_WHERE}}', $familyWhere, $sql);

            return $this->mssqlSei->executeQuery($sql);

        } catch (\Exception $e) {
            $this->graphMailer->notifyError('❌ LCS Erreur Best Demand per Style : Récupération de données sales', $e);
            $this->logger->error('LCS Erreur Best Demand per Style : Récupération de données sales', ['exception' => $e]);

            return [];
        }
    }

    public function getVentesQteCaClient(int $year): array
    {
        try {
            $query = $this->sqlFileLoader->load('Sei/ventes_qte_ca_client.sql');
            $query = str_replace('{{YEAR}}', (string) $year, $query);
            $rows  = $this->mssqlSei->executeQuery($query);

            $pivoted = [];

            foreach ($rows as $row) {
                $pays   = (string) ($row->COUNTRYCODE ?? '');
                $client = (string) ($row->CLIENT      ?? '');
                $key    = $pays . '||' . $client;

                if (!isset($pivoted[$key])) {
                    $pivoted[$key] = [
                        'PAYS'   => $pays,
                        'CLIENT' => $client,
                    ];
                    for ($m = 1; $m <= 12; $m++) {
                        $pad = str_pad((string) $m, 2, '0', STR_PAD_LEFT);
                        $pivoted[$key]['CA_'  . $pad] = 0.0;
                        $pivoted[$key]['QTE_' . $pad] = 0;
                    }
                }

                $pad = str_pad((string) ((int) ($row->MOIS ?? 1)), 2, '0', STR_PAD_LEFT);
                $pivoted[$key]['CA_'  . $pad] += round((float) ($row->CA       ?? 0), 2);
                $pivoted[$key]['QTE_' . $pad] += (int)          ($row->QUANTITE ?? 0);
            }

            foreach ($pivoted as &$row) {
                $caTotal = 0.0; $qteTotal = 0;
                for ($m = 1; $m <= 12; $m++) {
                    $pad = str_pad((string) $m, 2, '0', STR_PAD_LEFT);
                    $caTotal  += $row['CA_'  . $pad];
                    $qteTotal += $row['QTE_' . $pad];
                }
                $row['CA_TOTAL']  = round($caTotal, 2);
                $row['QTE_TOTAL'] = $qteTotal;
            }
            unset($row);

            $result = array_values($pivoted);
            usort($result, fn($a, $b) =>
                ($a['PAYS'] <=> $b['PAYS']) ?: ($a['CLIENT'] <=> $b['CLIENT'])
            );

            return $result;

        } catch (\Exception $e) {
            $this->graphMailer->notifyError('❌ LCS Erreur Sales : QTE/CA par Client', $e);
            $this->logger->error('LCS Erreur Sales : QTE/CA par Client', ['exception' => $e]);
            return [];
        }
    }

    public function getVentesQteCaArticle(int $year, string $client): array
    {
        try {
            $query = $this->sqlFileLoader->load('Sei/ventes_qte_ca_article.sql');
            $query = str_replace('{{YEAR}}',   (string) $year, $query);
            $query = str_replace('{{CLIENT}}', str_replace("'", "''", $client), $query);
            $rows  = $this->mssqlSei->executeQuery($query);

            $pivoted = [];

            foreach ($rows as $row) {
                $famille = (string) ($row->FAMILLE      ?? '');
                $article = (string) ($row->CODE_ARTICLE ?? '');
                $key     = $famille . '||' . $article;

                if (!isset($pivoted[$key])) {
                    $pivoted[$key] = [
                        'FAMILLE'      => $famille,
                        'CODE_ARTICLE' => $article,
                    ];
                    for ($m = 1; $m <= 12; $m++) {
                        $pad = str_pad((string) $m, 2, '0', STR_PAD_LEFT);
                        $pivoted[$key]['CA_'  . $pad] = 0.0;
                        $pivoted[$key]['QTE_' . $pad] = 0;
                    }
                }

                $pad = str_pad((string) ((int) ($row->MOIS ?? 1)), 2, '0', STR_PAD_LEFT);
                $pivoted[$key]['CA_'  . $pad] += round((float) ($row->CA       ?? 0), 2);
                $pivoted[$key]['QTE_' . $pad] += (int)          ($row->QUANTITE ?? 0);
            }

            foreach ($pivoted as &$row) {
                $caTotal = 0.0; $qteTotal = 0;
                for ($m = 1; $m <= 12; $m++) {
                    $pad = str_pad((string) $m, 2, '0', STR_PAD_LEFT);
                    $caTotal  += $row['CA_'  . $pad];
                    $qteTotal += $row['QTE_' . $pad];
                }
                $row['CA_TOTAL']  = round($caTotal, 2);
                $row['QTE_TOTAL'] = $qteTotal;
            }
            unset($row);

            $result = array_values($pivoted);
            usort($result, fn($a, $b) =>
                ($a['FAMILLE'] <=> $b['FAMILLE']) ?: ($a['CODE_ARTICLE'] <=> $b['CODE_ARTICLE'])
            );

            return $result;

        } catch (\Exception $e) {
            $this->graphMailer->notifyError('❌ LCS Erreur Sales : QTE/CA par Article', $e);
            $this->logger->error('LCS Erreur Sales : QTE/CA par Article', ['exception' => $e]);
            return [];
        }
    }

    public function getSellInSuiviPs(): array
    {
        try {
            $query = $this->sqlFileLoader->load('Sei/sell_in_suivi_ps.sql');
            return $this->mssqlSei->executeQuery($query);

        } catch (\Exception $e) {
            $this->graphMailer->notifyError('❌ LCS Erreur Sales : Récupération de données Sell In Suivi PS', $e);
            $this->logger->error('LCS Erreur Sales : Récupération de données Sell In Suivi PS', ['exception' => $e]);

            return [];
        }
    }

    public function saveSellInSuiviPs(
        string $customerCode,
        string $city,
        string $family,
        string $gender,
        string $seriesNo,
        string $field,
        mixed  $value,
        string $updatedBy,
    ): bool
    {
        $allowedFields = ['SIMPLE_STATUS', 'FORECAST', 'TARGET'];
        if (!in_array($field, $allowedFields, true)) {
            throw new \InvalidArgumentException("Champ '$field' non autorisé");
        }

        if ($field === 'SIMPLE_STATUS') {
            if (!in_array($value, ['OPEN', 'COMPLETE'], true)) {
                throw new \InvalidArgumentException('Valeur SIMPLE_STATUS invalide');
            }
            $valueSql = "'" . $value . "'";
        } else {
            if (!is_numeric($value)) {
                throw new \InvalidArgumentException("Valeur numérique attendue pour $field");
            }
            $valueSql = (string) (float) $value;
        }

        $escape = fn (string $s) => str_replace("'", "''", $s);

        $customerCodeEsc = $escape($customerCode);
        $cityEsc         = $escape($city);
        $familyEsc       = $escape($family);
        $genderEsc       = $escape($gender);
        $seriesNoEsc     = $escape($seriesNo);
        $updatedByEsc    = $escape($updatedBy);

        $defaultStatus   = ($field === 'SIMPLE_STATUS') ? $valueSql : "'OPEN'";
        $defaultForecast = ($field === 'FORECAST')      ? $valueSql : '0';
        $defaultTarget   = ($field === 'TARGET')        ? $valueSql : '0';

        $sql = <<<SQL
        MERGE [SEICube].[MASTER_TABLES].[SELL_IN_SUIVI_PS] AS target
        USING (SELECT
                  '{$customerCodeEsc}' AS CUSTOMER_CODE,
                  '{$cityEsc}'         AS CITY,
                  '{$familyEsc}'       AS FAMILY,
                  '{$genderEsc}'       AS GENDER,
                  '{$seriesNoEsc}'     AS SERIESNO
              ) AS src
        ON  target.CUSTOMER_CODE = src.CUSTOMER_CODE
        AND target.CITY          = src.CITY
        AND target.FAMILY        = src.FAMILY
        AND target.GENDER        = src.GENDER
        AND target.SERIESNO      = src.SERIESNO
        WHEN MATCHED THEN
            UPDATE SET
                {$field} = {$valueSql},
                UPDATED_AT = SYSUTCDATETIME(),
                UPDATED_BY = '{$updatedByEsc}'
        WHEN NOT MATCHED THEN
            INSERT (CUSTOMER_CODE, CITY, FAMILY, GENDER, SERIESNO, SIMPLE_STATUS, FORECAST, TARGET, UPDATED_BY)
            VALUES (
                '{$customerCodeEsc}',
                '{$cityEsc}',
                '{$familyEsc}',
                '{$genderEsc}',
                '{$seriesNoEsc}',
                {$defaultStatus},
                {$defaultForecast},
                {$defaultTarget},
                '{$updatedByEsc}'
            );
        SQL;

        try {
            $affectedRows = $this->mssqlSei->insertData($sql);

            if ($affectedRows < 1) {
                $this->logger->warning('Sell In Suivi PS : MERGE exécuté mais 0 ligne affectée', [
                    'params' => compact('customerCode', 'city', 'family', 'gender', 'seriesNo', 'field', 'value'),
                ]);
                return false;
            }

            return true;

        } catch (\Throwable $e) {
            $this->graphMailer->notifyError('❌ LCS Erreur Sales : Sauvegarde Sell In Suivi PS', $e);
            $this->logger->error('LCS Erreur Sales : Sauvegarde Sell In Suivi PS', [
                'exception' => $e,
                'params'    => compact('customerCode', 'city', 'family', 'gender', 'seriesNo', 'field', 'value'),
            ]);

            return false;
        }
    }

    /**
     * Mapping centralisé : field Ag-Grid → colonne SQL.
     * Une seule source de vérité, utilisée par les méthodes paginate + full.
     */
    private function getBacklogClientsX3FieldMap(): array
    {
        return [
            'PAYS'                  => 'SOH.BPCCRYNAM_0',
            'SITE'                  => 'SOH.STOFCY_0',
            'MAINNETWORK'           => 'ATX.TEXTE_0',
            'CLIENT'                => 'SOH.BPCINV_0',
            'NOM_CLIENT'            => 'BPC_INV.BPCNAM_0',
            'CLIENT_COMMANDE'       => 'SOH.BPCORD_0',
            'NOM_CLIENT_COMMANDE'   => 'SOH.BPCNAM_0',
            'PAIEMENT'              => 'SOH.PTE_0',
            'NUM_COMMANDE'          => 'SOQ.SOHNUM_0',
            'REF_CLIENT'            => 'SOH.CUSORDREF_0',
            'CODE_MARQUE'           => 'ITM.TSICOD_0',
            'ADRESSE_LIVRAISON'     => 'SOH.BPAADD_0',
            'FAMILLE'               => 'ITM.TCLCOD_0',
            'COLLECTION'            => 'SOQ.YCOLLECT_0',

            // 🆕 SKU complet (123456_L)
            'SKU'                   => 'ITM.ITMREF_0',
            // 🆕 ARTICLE = partie avant '_' (123456)
            'ARTICLE'               => 'SPLIT.ARTICLE_BASE',
            // 🆕 VARIANT = partie après '_' (L)
            'VARIANT'               => 'SPLIT.VARIANT_VAL',

            'ITMDES1_0'             => 'ITM.ITMDES1_0',
            'EAN'                   => 'ITM.EANCOD_0',
            // 🆕 DROPPE = OUI/NON depuis ZITMCOL
            'DROPPE'                => "CASE WHEN ITC.ZDROPPED_0 = 2 THEN 'OUI' ELSE 'NON' END",

            'DATE_COMMANDE'         => 'CONVERT(varchar(10), SOH.ORDDAT_0, 23)',
            'DATE_LIVRAISON'        => 'CONVERT(varchar(10), SOQ.DEMDLVDAT_0, 23)',
            'REP1'                  => 'REP2.REPNAM_0',
            'REP2'                  => 'REP1.REPNAM_0',
            'STATUT_ARTICLE'        => 'ITM.ITMSTA_0',
            'QUANTITE'              => '(SOQ.QTY_0 - (SOQ.DLVQTY_0 + SOQ.ODLQTY_0))',
            'CUR_0'                 => 'SOH.CUR_0',
            'PRICE_HT'              => 'SOP.NETPRINOT_0',
            'CLIENT_LIVRE'          => 'SOH.BPDNAM_0',
            'INDEPENDANT_GROUPMENT' => 'ATX4.TEXTE_0',
            'VILLE'                 => 'BPA.CTY_0',
            'ZCLASSE_0'             => 'SOH.ZCLASSE_0',
        ];
    }

    public function getBacklogClientsX3DistinctValues(string $field, array $filterModel = []): array
    {
        $fieldMap = $this->getBacklogClientsX3FieldMap();

        if (!isset($fieldMap[$field])) {
            return [];
        }

        try {
            $expr    = $fieldMap[$field];
            $baseSql = $this->sqlFileLoader->load('Sei/backlog_client.sql');

            $fromPos = stripos($baseSql, 'FROM X3_LCS');
            if ($fromPos === false) return [];

            $fromClause = substr($baseSql, $fromPos);
            $fromClause = str_replace(['{{ORDER_BY}}', '{{PAGINATION}}'], '', $fromClause);

            // Construire le WHERE à partir des filtres actifs sur les autres colonnes
            $ssrmRequest = SsrmRequest::fromArray(['filterModel' => $filterModel]);
            $builder     = new AgGridSqlBuilder($ssrmRequest, $fieldMap);
            $whereClause = $builder->buildWhereClause();
            $fromClause  = str_replace('{{WHERE_CLAUSE}}', $whereClause, $fromClause);

            $sql  = "SELECT DISTINCT {$expr} AS val\n{$fromClause}\nORDER BY val";
            $rows = $this->mssqlSei->executeQuery($sql);

            return array_values(array_filter(
                array_map(fn($r) => $r->val ?? null, $rows),
                fn($v) => $v !== null && $v !== ''
            ));
        } catch (\Throwable $e) {
            $this->logger->error('getBacklogClientsX3DistinctValues error', ['field' => $field, 'error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Version paginée (SSRM) : 1 bloc de lignes + total pour la scrollbar.
     */
    public function getBacklogClientsX3Paginated(SsrmRequest $request): SsrmResponse
    {
        try {
            $includeStock = (bool) $request->getOption('includeStock', true);
            // 🆕 Mode export : skip COUNT + totaux
            $isExport     = (bool) $request->getOption('isExport', false);

            $builder = new AgGridSqlBuilder(
                $request,
                $this->getBacklogClientsX3FieldMap()
            );

            $whereClause = $builder->buildWhereClause();
            $orderBy     = $builder->buildOrderByClause('SOQ.SOHNUM_0 ASC');
            $pagination  = $builder->buildPaginationClause();

            $totalRows = 0;
            $totals = [];

            // 🆕 Agrégats UNIQUEMENT pour la grille (pas pour l'export)
            if ($request->getOffset() === 0 && !$isExport) {
                $aggregateSql = $this->buildBacklogClientsX3AggregateSql($whereClause);
                $aggregateResult = $this->mssqlSei->executeQuery($aggregateSql);

                if (!empty($aggregateResult)) {
                    $totalRows = 0;
                    $totalQuantite = 0.0;
                    $totalPrixEur = 0.0;
                    $taux = $this->divers->getExchangeRatesValues();

                    foreach ($aggregateResult as $row) {
                        $totalRows     += (int)   ($row->NB_ROWS  ?? 0);
                        $totalQuantite += (float) ($row->QUANTITE ?? 0);

                        $devise    = $row->DEVISE ?? null;
                        $totalPrix = (float) ($row->TOTAL_PRIX ?? 0);
                        $rate      = ($devise !== null) ? ($taux[$devise] ?? null) : null;

                        if ($rate !== null && (float) $rate > 0) {
                            $totalPrixEur += $totalPrix / (float) $rate;
                        }
                    }

                    $totals = [
                        'QUANTITE' => (int) round($totalQuantite),
                        'PRIX_EUR' => round($totalPrixEur, 2),
                    ];
                }
            }

            // 🆕 Pour la grille uniquement : si total=0 on s'arrête
            if (!$isExport && $request->getOffset() === 0 && $totalRows === 0) {
                return new SsrmResponse(rows: [], lastRow: 0, totals: []);
            }

            // Requête paginée principale
            $sql = $this->sqlFileLoader->load('Sei/backlog_client.sql');
            $sql = str_replace('{{WHERE_CLAUSE}}', $whereClause, $sql);
            $sql = str_replace('{{ORDER_BY}}',    $orderBy, $sql);
            $sql = str_replace('{{PAGINATION}}',  $pagination, $sql);

            $rows = $this->mssqlSei->executeQuery($sql);
            $this->enrichBacklogClientsX3Rows($rows, $includeStock);

            return new SsrmResponse(
                rows: $rows,
                lastRow: $request->getOffset() === 0 ? $totalRows : null,
                totals: $totals,
            );

        } catch (\Throwable $e) {
            $this->graphMailer->notifyError('❌ LCS Erreur Backlog Client X3 SSRM', $e);
            $this->logger->error('LCS Erreur Backlog Client X3 SSRM', ['exception' => $e]);
            return new SsrmResponse(rows: [], lastRow: 0, totals: []);
        }
    }

    /**
     * Requête agrégée groupée par devise.
     * - NB_ROWS : compte global (on aura le total lignes en sommant)
     * - QUANTITE : somme des quantités
     * - TOTAL_PRIX : somme PRIX_HT * QUANTITE dans la devise d'origine
     * - DEVISE : pour appliquer le taux côté PHP
     */
    private function buildBacklogClientsX3AggregateSql(string $whereClause): string
    {
        return "
        SELECT
            SOH.CUR_0 AS DEVISE,
            COUNT(*) AS NB_ROWS,
            SUM(SOQ.QTY_0 - (SOQ.DLVQTY_0 + SOQ.ODLQTY_0)) AS QUANTITE,
            SUM(SOP.NETPRINOT_0 * (SOQ.QTY_0 - (SOQ.DLVQTY_0 + SOQ.ODLQTY_0))) AS TOTAL_PRIX
        FROM X3_LCS.SORDERQ SOQ
        INNER JOIN X3_LCS.SORDER SOH ON SOQ.SOHNUM_0 = SOH.SOHNUM_0
        INNER JOIN X3_LCS.SORDERP SOP ON SOQ.SOHNUM_0 = SOP.SOHNUM_0 AND SOQ.ITMREF_0 = SOP.ITMREF_0 AND SOQ.SOPLIN_0 = SOP.SOPLIN_0
        INNER JOIN X3_LCS.ITMMASTER ITM ON SOQ.ITMREF_0 = ITM.ITMREF_0
        CROSS APPLY (
            SELECT
                CASE WHEN CHARINDEX('_', ITM.ITMREF_0) > 0
                     THEN LEFT(ITM.ITMREF_0, CHARINDEX('_', ITM.ITMREF_0) - 1)
                     ELSE ITM.ITMREF_0
                END AS ARTICLE_BASE,
                CASE WHEN CHARINDEX('_', ITM.ITMREF_0) > 0
                     THEN SUBSTRING(ITM.ITMREF_0, CHARINDEX('_', ITM.ITMREF_0) + 1, 50)
                     ELSE NULL
                END AS VARIANT_VAL
        ) AS SPLIT
        INNER JOIN X3_LCS.BPCUSTOMER BPC ON SOH.BPCORD_0 = BPC.BPCNUM_0
        LEFT  JOIN X3_LCS.BPCUSTOMER BPC_INV ON SOH.BPCINV_0 = BPC_INV.BPCNUM_0
        INNER JOIN X3_LCS.BPADDRESS BPA ON BPC.BPCNUM_0 = BPA.BPANUM_0 AND BPA.BPAADD_0 = SOH.BPAADD_0
        LEFT  JOIN X3_LCS.SALESREP REP1 ON BPC.REP_0 = REP1.REPNUM_0
        LEFT  JOIN X3_LCS.SALESREP REP2 ON BPC.REP_1 = REP2.REPNUM_0
        LEFT  JOIN X3_LCS.ATEXTRA ATX  ON ATX.IDENT2_0  = BPC.TSCCOD_2     AND ATX.CODFIC_0  = 'ATABDIV' AND ATX.LANGUE_0  = 'FRA' AND ATX.ZONE_0  = 'LNGDES' AND ATX.IDENT1_0  = '32'
        LEFT  JOIN X3_LCS.ATEXTRA ATX4 ON ATX4.IDENT2_0 = BPC.ZGROUPIND_0 AND ATX4.CODFIC_0 = 'ATABDIV' AND ATX4.LANGUE_0 = 'FRA' AND ATX4.ZONE_0 = 'LNGDES' AND ATX4.IDENT1_0 = '6021'
        LEFT  JOIN X3_LCS.ZITMCOL  ITC ON ITC.ITMREF_0 = SPLIT.ARTICLE_BASE AND ITC.YCOLLECT_0 = SOQ.YCOLLECT_0
        WHERE
            SOQ.SOQSTA_0 <> 3
            AND SOH.ZSOHVALSTA_0 <> 3
            AND BPC.BCGCOD_0 <> 'INTER'
            $whereClause
        GROUP BY SOH.CUR_0
    ";
    }

    /**
     * Enrichissement : prix + (optionnellement) stocks.
     * Le stock est coûteux (1 requête lourde sur SEICube) → option pour l'export.
     */
    private function enrichBacklogClientsX3Rows(array &$rows, bool $includeStock = true): void
    {
        if ($rows === []) return;

        $taux = $this->divers->getExchangeRatesValues();

        $stocksByArticle = [];
        if ($includeStock) {
            $stocks = $this->getStockPourBacklogClientsX3();
            foreach ($stocks as $stock) {
                $stocksByArticle[$stock->ARTICLE] = $stock;
            }
        }

        foreach ($rows as $row) {
            $quantity = (int) $row->QUANTITE;
            $priceHt = (float) $row->PRICE_HT;
            $currencyRate = $taux[$row->CUR_0] ?? null;

            $row->PRIX = round($priceHt * $quantity, 2);
            $row->PRIX_EUR = ($currencyRate !== null && (float) $currencyRate > 0)
                ? round(($priceHt / (float) $currencyRate) * $quantity, 2)
                : 0.0;

            if ($includeStock) {
                // 🆕 Lookup sur SKU (123456_L) au lieu d'ARTICLE (qui est maintenant 123456)
                $stock = $stocksByArticle[$row->SKU] ?? null;

                $row->STOCK_REEL_WLOGM    = $stock ? (float) $stock->STOCK_REEL_WLOGM    : 0.0;
                $row->STOCK_INTERNE_WLOGM = $stock ? (float) $stock->STOCK_INTERNE_WLOGM : 0.0;
                $row->STOCK_REEL_WSFCN    = $stock ? (float) $stock->STOCK_REEL_WSFCN    : 0.0;
                $row->STOCK_INTERNE_WSFCN = $stock ? (float) $stock->STOCK_INTERNE_WSFCN : 0.0;
                $row->STOCK_REEL_WTAKH    = $stock ? (float) $stock->STOCK_REEL_WTAKH    : 0.0;
                $row->STOCK_INTERNE_WTAKH = $stock ? (float) $stock->STOCK_INTERNE_WTAKH : 0.0;
                $row->STOCK_REEL_WDTTH    = $stock ? (float) $stock->STOCK_REEL_WDTTH    : 0.0;
                $row->STOCK_INTERNE_WDTTH = $stock ? (float) $stock->STOCK_INTERNE_WDTTH : 0.0;
            }
        }
    }

    public function getSuiviPerfWholesaleFr(): array
    {
        try {
            $sql  = $this->sqlFileLoader->load('Sei/suivi_perf_wholesale_fr.sql');
            $rows = $this->mssqlSei->executeQuery($sql);

            $brachetName   = 'BRACHET Sylvain';
            $groups        = [];
            $grandSs26     = 0.0;
            $grandSs27     = 0.0;
            $grandObjectif = 0.0;

            foreach ($rows as $row) {
                $rep      = (string) ($row->REPRESENTANT    ?? '');
                $manager  = (string) ($row->MANAGER_RZ      ?? '');
                $ss26     = (float)  ($row->PS_SS26          ?? 0);
                $ss27     = (float)  ($row->PS_SS27          ?? 0);
                $objectif = (float)  ($row->OBJECTIF_PS_SS27 ?? 0);

                $grandSs26     += $ss26;
                $grandSs27     += $ss27;
                $grandObjectif += $objectif;

                if ($rep === $brachetName) {
                    continue;
                }

                // Un RZ appartient à son propre groupe
                $groupKey = str_contains($rep, ' RZ') ? $rep : $manager;

                if (!isset($groups[$groupKey])) {
                    $groups[$groupKey] = ['reps' => [], 'ss26' => 0.0, 'ss27' => 0.0, 'objectif' => 0.0];
                }

                $groups[$groupKey]['reps'][]   = ['REPRESENTANT' => $rep, 'ss26' => $ss26, 'ss27' => $ss27, 'objectif' => $objectif];
                $groups[$groupKey]['ss26']     += $ss26;
                $groups[$groupKey]['ss27']     += $ss27;
                $groups[$groupKey]['objectif'] += $objectif;
            }

            ksort($groups);

            $result = [];

            foreach ($groups as $groupKey => $group) {
                usort($group['reps'], fn($a, $b) => strcmp($a['REPRESENTANT'], $b['REPRESENTANT']));

                $rzRow = null;
                foreach ($group['reps'] as $rep) {
                    if (str_contains($rep['REPRESENTANT'], ' RZ')) {
                        $rzRow = $rep;
                    } else {
                        $result[] = $this->enrichPerfRow($rep['REPRESENTANT'], $rep['ss26'], $rep['objectif'], $rep['ss27'], 'rep');
                    }
                }

                if ($rzRow !== null) {
                    $result[] = $this->enrichPerfRow($rzRow['REPRESENTANT'], $rzRow['ss26'], $rzRow['objectif'], $rzRow['ss27'], 'rep');
                }

                $result[] = $this->enrichPerfRow($groupKey, $group['ss26'], $group['objectif'], $group['ss27'], 'manager');
            }

            $result[] = $this->enrichPerfRow($brachetName, $grandSs26, $grandObjectif, $grandSs27, 'director');

            return $result;

        } catch (\Exception $e) {
            $this->graphMailer->notifyError('❌ LCS Erreur Sales : Suivi Perf Wholesale FR', $e);
            $this->logger->error('LCS Erreur Sales : Suivi Perf Wholesale FR', ['exception' => $e]);
            return [];
        }
    }

    private function enrichPerfRow(string $name, float $ss26, float $objectif, float $ss27, string $rowType): array
    {
        return [
            'REPRESENTANT'          => $name,
            'PS_ORDER_SS26_EOS'     => $ss26,
            'OBJECTIF_PS_SS27'      => $objectif,
            'PS_ORDER_SS27_TD'      => $ss27,
            'EVOLUTION_VS_SS26'     => $ss26 > 0 ? round($ss27 / $ss26 - 1, 4) : null,
            'POURCENTAGE_ATTEINTE'  => $objectif > 0 ? round($ss27 / $objectif, 4) : null,
            'ROW_TYPE'              => $rowType,
        ];
    }

}
