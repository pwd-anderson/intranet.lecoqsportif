<?php

namespace App\Service;

use App\Factory\MssqlManagerFactory;
use App\Infrastructure\Sql\SqlFileLoader;
use App\Service\Tools\GraphMailer;
use App\Service\Tools\MssqlManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class It
{
    private MssqlManager $mssqlSei;

    public function __construct(
        private MssqlManagerFactory $mssqlManagerFactory,
        private LoggerInterface $logger,
        private GraphMailer $graphMailer,
        private SqlFileLoader $sqlFileLoader,
        #[Autowire('%db.lcs_sei%')]
        string $dbLcsSei,
    )
    {
        $this->mssqlSei = $this->mssqlManagerFactory->create($dbLcsSei);
    }

    public function getTcdCmdNonSoldees(): array
    {
        try {
            $query = $this->sqlFileLoader->load('Sei/tcd_cmd_non_soldees.sql');
            return $this->mssqlSei->executeQuery($query);

        } catch (\Exception $e) {
            $this->graphMailer->notifyError('❌ LCS Erreur IT : TCD Commandes non soldées', $e);
            $this->logger->error('LCS Erreur IT : TCD Commandes non soldées', ['exception' => $e]);

            return [];
        }
    }

    public function getDetailTcd(): array
    {
        try {
            $query = $this->sqlFileLoader->load('Sei/detail_tcd.sql');
            return $this->mssqlSei->executeQuery($query);

        } catch (\Exception $e) {
            $this->graphMailer->notifyError('❌ LCS Erreur IT : Détail du TCD', $e);
            $this->logger->error('LCS Erreur IT : Détail du TCD', ['exception' => $e]);

            return [];
        }
    }
}
