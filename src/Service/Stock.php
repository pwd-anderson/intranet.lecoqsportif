<?php

namespace App\Service;

use App\Service\Tools\GraphMailer;
use Psr\Log\LoggerInterface;
use App\Service\Tools\MssqlManager;

class Stock
{

    public function __construct(
        private MssqlManager $mssqlManager,
        private LoggerInterface $logger,
        private GraphMailer $graphMailer
    ) {}

    public function getStockATerme(): array
    {
        try {
            $query = "SELECT * FROM LCS_BI.STOCK_A_TERME;";

            $data = $this->mssqlManager->executeQuery($query);
            return $data;

        } catch (\Exception $e) {
            $this->graphMailer->notifyError('❌ LCS Erreur Stock à Terme : Récupération de données stock', $e);
            $this->logger->error('LCS Erreur Stock à Terme : Récupération de données stock', ['exception' => $e]);
        }
    }



}
