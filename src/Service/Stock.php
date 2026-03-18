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
    private MssqlManager $mssqlLcs;
    private MssqlManager $mssqlSei;

    public function __construct(
        private MssqlManagerFactory $mssqlManagerFactory,
        private LoggerInterface $logger,
        private GraphMailer $graphMailer,
        private SqlFileLoader $sqlFileLoader,
        #[Autowire('%db.lcs%')]
        string $dbLcs,
        #[Autowire('%db.lcs_sei%')]
        string $dbLcsSei,
    )
    {
        $this->mssqlLcs = $this->mssqlManagerFactory->create($dbLcs);
        $this->mssqlSei = $this->mssqlManagerFactory->create($dbLcsSei);
    }

    public function getStockATerme(): array
    {
        try {
            $query = $this->sqlFileLoader->load('Navision/stock_a_terme_tmp.sql');
            $data = $this->mssqlLcs->executeMultiStatement($query);
            return $data;

        } catch (\Exception $e) {
            $this->graphMailer->notifyError('❌ LCS Erreur Stock à Terme : Récupération de données stock', $e);
            $this->logger->error('LCS Erreur Stock à Terme : Récupération de données stock', ['exception' => $e]);
        }
    }

    public function getStockATermeAvecSegmentationProduit(): array
    {
        try {
            $query = $this->sqlFileLoader->load('Navision/stock_a_terme_segmentation_produit_tmp.sql');
            $data = $this->mssqlLcs->executeMultiStatement($query);
            return $data;

        } catch (\Exception $e) {
            $this->graphMailer->notifyError('❌ LCS Erreur Stock à Terme : Récupération de données stock', $e);
            $this->logger->error('LCS Erreur Stock à Terme : Récupération de données stock', ['exception' => $e]);
        }
    }

    public function getStockAllocation(): array
    {
        try {
            $query = $this->sqlFileLoader->load('Sei/stock_allocation.sql');
            $data = $this->mssqlSei->executeQuery($query);
            return $data;

        } catch (\Exception $e) {
            $this->graphMailer->notifyError('❌ LCS Erreur Stock Allocation : Récupération de données stock', $e);
            $this->logger->error('LCS Erreur Stock Allocation : Récupération de données stock', ['exception' => $e]);
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

            return $this->mssqlSei->executeMultiStatement($query);

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
            return $this->mssqlSei->executeQuery($sql);

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
            $data = $this->mssqlSei->executeQuery($query);
            return $data;

        } catch (\Exception $e) {
            $this->graphMailer->notifyError('❌ LCS Erreur Stock à Terme X3 : Récupération de données stock', $e);
            $this->logger->error('LCS Erreur Stock à Terme X3 : Récupération de données stock', ['exception' => $e]);
        }
    }

}
