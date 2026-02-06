<?php

namespace App\Service;

use App\Factory\MssqlManagerFactory;
use App\Infrastructure\Sql\SqlFileLoader;
use App\Service\Tools\GraphMailer;
use Psr\Log\LoggerInterface;
use App\Service\Tools\MssqlManager;
use SQLite3;

class Stock
{
    private MssqlManager $mssqlLcs;
    private MssqlManager $mssqlSei;

    public function __construct(
        private MssqlManagerFactory $mssqlManagerFactory,
        private LoggerInterface $logger,
        private GraphMailer $graphMailer,
        private SqlFileLoader $sqlFileLoader,
    )
    {
        $this->mssqlLcs = $this->mssqlManagerFactory->create('lcs_sei');
        $this->mssqlSei = $this->mssqlManagerFactory->create('lcs_sei');
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

}
