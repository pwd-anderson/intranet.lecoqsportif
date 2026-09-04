<?php

namespace App\Service;

use App\Factory\MssqlManagerFactory;
use App\Infrastructure\Sql\SqlFileLoader;
use App\Service\Tools\GraphMailer;
use Psr\Log\LoggerInterface;
use App\Service\Tools\MssqlManager;
use SQLite3;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class Stock
{
    private ?MssqlManager $mssqlLcsInstance = null;
    private ?MssqlManager $mssqlSeiInstance = null;

    public function __construct(
        private MssqlManagerFactory $mssqlManagerFactory,
        private LoggerInterface $logger,
        private GraphMailer $graphMailer,
        private SqlFileLoader $sqlFileLoader,
        #[Autowire('%db.lcs%')]
        private string $dbLcs,
        #[Autowire('%db.lcs_sei%')]
        private string $dbLcsSei,
    )
    {
    }

    /**
     * Connexions MSSQL établies à la demande seulement (pas dans le constructeur) :
     * ouvrir une connexion PDO vers un serveur distant est coûteux (mssqlLcs peut
     * prendre ~15s), donc on évite de payer ce coût pour les actions qui n'ont besoin
     * que d'une seule des deux connexions.
     */
    private function mssqlLcs(): MssqlManager
    {
        return $this->mssqlLcsInstance ??= $this->mssqlManagerFactory->create($this->dbLcs);
    }

    private function mssqlSei(): MssqlManager
    {
        return $this->mssqlSeiInstance ??= $this->mssqlManagerFactory->create($this->dbLcsSei);
    }

    /**
     * Route Navision obsolète — orpheline (aucun lien sidebar/StatRegistry, seule la
     * version X3 est utilisée). Données Navision migrées vers SEICube : connexion
     * mssqlLcs désactivée ici volontairement, ne plus la réactiver sur cette méthode.
     */
    public function getStockATerme(): array
    {
        $this->logger->warning('Stock::getStockATerme — route Navision obsolète appelée (données migrées vers SEICube).');
        return [];
    }

    /**
     * Route Navision obsolète — orpheline (aucun lien sidebar/StatRegistry, seule la
     * version X3 est utilisée). Données Navision migrées vers SEICube : connexion
     * mssqlLcs désactivée ici volontairement, ne plus la réactiver sur cette méthode.
     */
    public function getStockATermeAvecSegmentationProduit(): array
    {
        $this->logger->warning('Stock::getStockATermeAvecSegmentationProduit — route Navision obsolète appelée (données migrées vers SEICube).');
        return [];
    }

    public function getStockAllocation(?string $siteGroup = null): array
    {
        try {
            $query = $this->sqlFileLoader->load('Sei/stock_allocation.sql');

            $sitesByGroup = [
                'central' => [
                    'WDTTH',
                    'WLOGM',
                    'WSFCN',
                ],
                'magasins' => [
                    'RBBAY',
                    'RBCAB',
                    'RBCAG',
                    'RBCIT',
                    'RBORL',
                    'RBSJL',
                    'RBSTG',
                    'RFMIR',
                    'RFROP',
                    'RFROU',
                    'RFTRO',
                    'RFVIL',
                ],
                'concept_stores' => [
                    'RBBAY',
                    'RBCAB',
                    'RBCAG',
                    'RBCIT',
                    'RBORL',
                    'RBSJL',
                    'RBSTG',
                ],
                'factory_outlets' => [
                    'RFMIR',
                    'RFROP',
                    'RFROU',
                    'RFTRO',
                    'RFVIL',
                ],
            ];

            if ($siteGroup && isset($sitesByGroup[$siteGroup])) {
                $sites = $sitesByGroup[$siteGroup];

                $quotedSites = array_map(static function ($site) {
                    return "'" . str_replace("'", "''", $site) . "'";
                }, $sites);

                $sitesSql = implode(',', $quotedSites);

                $query = str_replace(
                    '/*SITE_FILTER*/',
                    "AND s.STOFCY_0 IN ($sitesSql)",
                    $query
                );
            } else {
                $query = str_replace('/*SITE_FILTER*/', '', $query);
            }

            return $this->mssqlSei()->executeQuery($query);

        } catch (\Exception $e) {
            $this->graphMailer->notifyError('❌ LCS Erreur Stock Allocation : Récupération de données stock', $e);
            $this->logger->error('LCS Erreur Stock Allocation : Récupération de données stock', ['exception' => $e]);

            return [];
        }
    }

    public function getStockParCollection(?string $location = null, ?string $status): array
    {
        try {

            $query = $this->sqlFileLoader->load('Sei/stock_collection.sql');

            $location = preg_replace('/[^A-Za-z0-9_]/', '', $location ?? '');
            $status   = preg_replace('/[^A-Za-z0-9_]/', '', $status ?? '');

            $query = str_replace('{{LOCATION}}', $location, $query);
            $query = str_replace('{{STATUS}}', $status, $query);

            return $this->mssqlSei()->executeMultiStatement($query);

        } catch (\Exception $e) {
            $this->graphMailer->notifyError('❌ LCS Erreur Stock Collection : Récupération de données stock', $e);
            $this->logger->error('LCS Erreur Stock Collection: Récupération de données stock', ['exception' => $e]);
        }
    }

    public function getStockComposant(?string $famille = null): array
    {
        try {
            $sql = $this->sqlFileLoader->load('Sei/stock_composant.sql');

            // Nettoyage strict (évite injection, espaces, quotes, etc.)
            $famille = $famille !== null ? trim($famille) : null;
            $famille = $famille !== '' ? preg_replace('/[^A-Za-z0-9_\-]/', '', $famille) : null;

            // Construit la clause WHERE
            $familleWhere = '';
            if ($famille) {
                // ⚠️ TCLCOD_0 est un code => on le quote ici (après sanitation)
                $familleWhere = "  AND ITM.TCLCOD_0 = '{$famille}'";
            }

            $sql = str_replace('{{FAMILLE_WHERE}}', $familleWhere, $sql);

            // Ici tu utilises ta méthode "simple"
            return $this->mssqlSei()->executeQuery($sql);

        } catch (\Exception $e) {
            $this->graphMailer->notifyError('❌ LCS Erreur Stock Composant : Récupération de données stock', $e);
            $this->logger->error('LCS Erreur Stock Composant: Récupération de données stock', ['exception' => $e]);
            return [];
        }
    }

    public function getStockATermeX3(): array
    {
        try {
            $query = $this->sqlFileLoader->load('Sei/stock_a_terme.sql');
            $data = $this->mssqlSei()->executeQuery($query);
            return $data;

        } catch (\Exception $e) {
            $this->graphMailer->notifyError('❌ LCS Erreur Stock à Terme X3 : Récupération de données stock', $e);
            $this->logger->error('LCS Erreur Stock à Terme X3 : Récupération de données stock', ['exception' => $e]);
        }
    }

    public function getStockATermeSegmentationProduitsX3(): array
    {
        try {
            $query = "
                SELECT [SITE], [DESCRIPTION_SITE], [FAMILLE], [DERNIERE_COLLECTION],
                       [ARTICLE], [EAN], [DESCRIPTION_ARTICLE], [STATUT], GENRE, SEG_OFFRE,
                       [LARGEUR], [LONGUEUR], [HAUTEUR], [UNITE], [POIDS],
                       [PRIX_MARCHE], [DEVISE_MARCHE], [PDM_MATERIAL_CODE], [PAYS_ORIGINE],
                       [STOCK_INTERNE],
                       [ACHAT_M0], [VENTE_M0], [RESA_ETAIL_M0], [RESA_RETAIL_M0], [STOCK_TERME_M0],
                       [ACHAT_M1], [VENTE_M1], [RESA_ETAIL_M1], [RESA_RETAIL_M1], [STOCK_TERME_M1],
                       [ACHAT_M2], [VENTE_M2], [RESA_ETAIL_M2], [RESA_RETAIL_M2], [STOCK_TERME_M2],
                       [ACHAT_M3], [VENTE_M3], [RESA_ETAIL_M3], [RESA_RETAIL_M3], [STOCK_TERME_M3],
                       [ACHAT_M4], [VENTE_M4], [RESA_ETAIL_M4], [RESA_RETAIL_M4], [STOCK_TERME_M4],
                       [ACHAT_M5], [VENTE_M5], [RESA_ETAIL_M5], [RESA_RETAIL_M5], [STOCK_TERME_M5],
                       [ACHAT_M6], [VENTE_M6], [RESA_ETAIL_M6], [RESA_RETAIL_M6], [STOCK_TERME_M6]
                FROM MASTER_TABLES.AMAZON_SEG_STOCK_A_TERME
            ";
            return $this->mssqlSei()->executeQuery($query);

        } catch (\Exception $e) {
            $this->graphMailer->notifyError('❌ LCS Erreur Stock à Terme Segmentation X3', $e);
            $this->logger->error('LCS Erreur Stock à Terme Segmentation X3', ['exception' => $e]);
            return [];
        }
    }

    public function getStockProduits(?string $collection = null, ?string $famille = null, ?string $genre = null): array
    {
        try {
            $sql = $this->sqlFileLoader->load('Sei/stock_produits.sql');

            // Nettoyage strict (anti-injection)
            $collection = $collection !== null ? trim($collection) : null;
            $collection = $collection !== '' ? preg_replace('/[^A-Za-z0-9_\-]/', '', $collection) : null;

            $famille = $famille !== null ? trim($famille) : null;
            $famille = $famille !== '' ? preg_replace('/[^A-Za-z0-9_\-]/', '', $famille) : null;

            $genre = $genre !== null ? trim($genre) : null;
            $genre = $genre !== '' ? preg_replace('/[^A-Za-z0-9_\-]/', '', $genre) : null;

            // Construction des clauses WHERE
            $collectionWhere = '';
            if ($collection) {
                $collectionWhere = " AND CR.YCOLLECT_0 = '{$collection}'";
            }

            $familleWhere = '';
            if ($famille) {
                $familleWhere = " AND ITM.TCLCOD_0 = '{$famille}'";
            }

            $genreWhere = '';
            if ($genre) {
                $genreWhere = " AND ITM.TSICOD_0 = '{$genre}'";
            }

            $sql = str_replace('{{COLLECTION_WHERE}}', $collectionWhere, $sql);
            $sql = str_replace('{{FAMILLE_WHERE}}', $familleWhere, $sql);
            $sql = str_replace('{{GENRE_WHERE}}', $genreWhere, $sql);

            return $this->mssqlSei()->executeQuery($sql);

        } catch (\Exception $e) {
            $this->graphMailer->notifyError('❌ LCS Erreur Stock Produits : Récupération de données', $e);
            $this->logger->error('LCS Erreur Stock Produits : Récupération de données', ['exception' => $e]);

            return [];
        }
    }

    public function getStockProduitsShopifyNonCoches(): array
    {
        try {
            $query = $this->sqlFileLoader->load('Sei/produits_shopify_non_coches.sql');
            $data = $this->mssqlSei()->executeQuery($query);

            if (empty($data)) {
                return [];
            }

            $stocks = $this->getStockInterneLogtexEtMagasins();

            $stocksByArticle = [];
            foreach ($stocks as $stock) {
                $stocksByArticle[$stock->ARTICLE] = $stock;
            }

            $result = [];
            foreach ($data as $row) {
                $stock = $stocksByArticle[$row->ARTICLE] ?? null;

                // Si l'article n'est pas dans la liste des stocks (donc déjà filtré
                // par le HAVING SQL), on l'exclut
                if ($stock === null) {
                    continue;
                }

                $row->STOCK_INTERNE_LOGTEX  = (float) $stock->STOCK_INTERNE_LOGTEX;
                $row->STOCK_INTERNE_MAGASIN = (float) $stock->STOCK_INTERNE_MAGASIN;

                // Sécurité : si jamais les deux sont à 0, on exclut aussi
                if ($row->STOCK_INTERNE_LOGTEX == 0.0 && $row->STOCK_INTERNE_MAGASIN == 0.0) {
                    continue;
                }

                $result[] = $row;
            }

            return $result;

        } catch (\Throwable $e) {
            $this->graphMailer->notifyError(
                '❌ LCS Erreur Stock Produits Shop non Cochés: Récupération de données stock',
                $e
            );

            $this->logger->error(
                'LCS Erreur Stock Produits Shop non Cochés: Récupération de données stock',
                ['exception' => $e]
            );

            return [];
        }
    }

    public function getStockLogtex(): array
    {
        try {
            $query = $this->sqlFileLoader->load('Sei/stock_logtex.sql');
            $data = $this->mssqlSei()->executeQuery($query);
            return empty($data) ? [] : $data;
        } catch (\Exception $e) {
            $this->graphMailer->notifyError('❌ LCS Erreur Stock LogTex : Récupération de données', $e);
            $this->logger->error('LCS Erreur Stock LogTex : Récupération de données', ['exception' => $e]);
            return [];
        }
    }

    public function getStockInterneLogtexEtMagasins(): array
    {
        try {
            $query = $this->sqlFileLoader->load('Sei/stock_interne_mag_et_logtex.sql');
            $data = $this->mssqlSei()->executeQuery($query);

            if (empty($data)) {
                return [];
            }
            return $data;

        } catch (\Exception $e) {
            $this->graphMailer->notifyError('❌ LCS Erreur Stock à Terme X3 : Récupération de données stock', $e);
            $this->logger->error('LCS Erreur Stock à Terme X3 : Récupération de données stock', ['exception' => $e]);
        }
    }



}
