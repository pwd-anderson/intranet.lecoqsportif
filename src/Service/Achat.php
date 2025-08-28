<?php

namespace App\Service;

use App\Service\Tools\GraphMailer;
use Symfony\Component\Mime\Email;
use Psr\Log\LoggerInterface;
use App\Service\Tools\MssqlManager;

class Achat
{

    public function __construct(
        private MssqlManager $mssqlManager,
        private LoggerInterface $logger,
        private GraphMailer $graphMailer
    ) {}

    public function getReception(): array
    {
        try {
            $query = "";

            $data = $this->mssqlManager->executeQuery($query);
            return $data;

        } catch (\Exception $e) {
            $this->graphMailer->notifyError('❌ LCS Erreur Sales : Récupération de données Livraison non facturées', $e);
            $this->logger->error('LCS Erreur Sales : Récupération de données Livraison non facturées', ['exception' => $e]);
        }
    }

}
