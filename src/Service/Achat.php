<?php

namespace App\Service;

use App\Factory\MssqlManagerFactory;
use App\Service\Tools\GraphMailer;
use Symfony\Component\Mime\Email;
use Psr\Log\LoggerInterface;

class Achat
{

    public function __construct(
        private MssqlManagerFactory $mssqlManagerFactory,
        private LoggerInterface $logger,
        private GraphMailer $graphMailer
    ) {}

    public function getReception(): array
    {
        try {
            $query = "";

            //$data = $this->mssqlManager->executeQuery($query);
            return $data;

        } catch (\Exception $e) {
            $this->graphMailer->notifyError('❌ LCS Erreur Sales : Récupération de données Livraison non facturées', $e);
            $this->logger->error('LCS Erreur Sales : Récupération de données Livraison non facturées', ['exception' => $e]);
        }
    }

}
