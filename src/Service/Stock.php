<?php

namespace App\Service;

use App\Factory\MssqlManagerFactory;
use App\Service\Tools\GraphMailer;
use Psr\Log\LoggerInterface;
use App\Service\Tools\MssqlManager;

class Stock
{
    private MssqlManager $mssqlLcs;

    public function __construct(
        private MssqlManagerFactory $mssqlManagerFactory,
        private LoggerInterface $logger,
        private GraphMailer $graphMailer
    )
    {
        $this->mssqlLcs = $this->mssqlManagerFactory->create('lcs');
    }

    public function getStockATerme(): array
    {
        try {
            $query = "SELECT * FROM BI.REPORT.Audit_Planned_Stock();";

            $data = $this->mssqlLcs->executeQuery($query);
            return $data;

        } catch (\Exception $e) {
            $this->graphMailer->notifyError('❌ LCS Erreur Stock à Terme : Récupération de données stock', $e);
            $this->logger->error('LCS Erreur Stock à Terme : Récupération de données stock', ['exception' => $e]);
        }
    }



}
