<?php

namespace App\Service;

use App\Factory\MssqlManagerFactory;
use App\Infrastructure\Sql\SqlFileLoader;
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
        ?string $family = null,
        ?string $collection = null
    ): array
    {
        try {
            $query = $this->sqlFileLoader->load('Sei/excess_for_sales.sql');

            $conditions = [];

            if ($tariffGroup) {
                $conditions[] = "GROUPE_TARIF = '" . str_replace("'", "''", $tariffGroup) . "'";
            }

            if ($family) {
                $conditions[] = "FAMILLE = '" . str_replace("'", "''", $family) . "'";
            }

            if ($collection) {
                $conditions[] = "COLLECTION = '" . str_replace("'", "''", $collection) . "'";
            }

            if (!empty($conditions)) {
                $query .= ' WHERE ' . implode(' AND ', $conditions);
            }

            // ... reste de la méthode INCHANGÉ ...

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

    public function getBacklogClientsX3(): array
    {
        try {
            $query = $this->sqlFileLoader->load('Sei/backlog_client.sql');
            $data = $this->mssqlSei->executeQuery($query);

            if (empty($data)) {
                return [];
            }

            $taux = $this->divers->getExchangeRatesValues();
            $stocks = $this->getStockPourBacklogClientsX3();

            $stocksByArticle = [];
            foreach ($stocks as $stock) {
                $stocksByArticle[$stock->ARTICLE] = $stock;
            }

            foreach ($data as $row) {
                $quantity = (int) $row->QUANTITE;
                $priceHt = (float) $row->PRICE_HT;
                $currencyRate = $taux[$row->CUR_0] ?? null;
                $stock = $stocksByArticle[$row->ARTICLE] ?? null;

                $row->PRIX = round($priceHt * $quantity, 2);
                $row->PRIX_EUR = ($currencyRate !== null && (float) $currencyRate > 0)
                    ? round(($priceHt / (float) $currencyRate) * $quantity, 2)
                    : 0.0;

                $row->STOCK_REEL_WLOGM = $stock ? (float) $stock->STOCK_REEL_WLOGM : 0.0;
                $row->STOCK_INTERNE_WLOGM = $stock ? (float) $stock->STOCK_INTERNE_WLOGM : 0.0;
                $row->STOCK_REEL_WSFCN = $stock ? (float) $stock->STOCK_REEL_WSFCN : 0.0;
                $row->STOCK_INTERNE_WSFCN = $stock ? (float) $stock->STOCK_INTERNE_WSFCN : 0.0;
                $row->STOCK_REEL_WTAKH = $stock ? (float) $stock->STOCK_REEL_WTAKH : 0.0;
                $row->STOCK_INTERNE_WTAKH = $stock ? (float) $stock->STOCK_INTERNE_WTAKH : 0.0;
                $row->STOCK_REEL_WDTTH = $stock ? (float) $stock->STOCK_REEL_WDTTH : 0.0;
                $row->STOCK_INTERNE_WDTTH = $stock ? (float) $stock->STOCK_INTERNE_WDTTH : 0.0;
            }

            return $data;
        } catch (\Throwable $e) {
            $this->graphMailer->notifyError(
                '❌ LCS Erreur Backlog Client X3 : Récupération de données ventes',
                $e
            );

            $this->logger->error(
                'LCS Erreur Backlog Client X3 : Récupération de données ventes',
                ['exception' => $e]
            );

            return [];
        }
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

    public function getCommandesAlloueesSansBp(): array
    {
        try {
            $query = $this->sqlFileLoader->load('Sei/commandes_allouees_sans_bp.sql');
            return $this->mssqlSei->executeQuery($query);

        } catch (\Exception $e) {
            $this->graphMailer->notifyError('❌ LCS Erreur Sales : Commandes allouées sans BP', $e);
            $this->logger->error('LCS Erreur Sales : Commandes allouées sans BP', ['exception' => $e]);

            return [];
        }
    }

    public function getCommandesBpSansLivraison(): array
    {
        try {
            $query = $this->sqlFileLoader->load('Sei/commandes_bp_sans_livraison.sql');
            return $this->mssqlSei->executeQuery($query);

        } catch (\Exception $e) {
            $this->graphMailer->notifyError('❌ LCS Erreur Sales : Commandes avec BP sans livraison', $e);
            $this->logger->error('LCS Erreur Sales : Commandes avec BP sans livraison', ['exception' => $e]);

            return [];
        }
    }

    public function getCommandesNonSoldeesParDate(): array
    {
        try {
            $query = $this->sqlFileLoader->load('Sei/commandes_non_soldees_par_date.sql');
            return $this->mssqlSei->executeQuery($query);

        } catch (\Exception $e) {
            $this->graphMailer->notifyError('❌ LCS Erreur Sales : Commandes non soldées par date', $e);
            $this->logger->error('LCS Erreur Sales : Commandes non soldées par date', ['exception' => $e]);

            return [];
        }
    }

}
