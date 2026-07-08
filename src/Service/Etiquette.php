<?php

namespace App\Service;

use App\Factory\MssqlManagerFactory;
use App\Infrastructure\Sql\SqlFileLoader;
use App\Service\Tools\GraphMailer;
use App\Service\Tools\MssqlManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class Etiquette
{
    private MssqlManager $mssqlSei;

    public function __construct(
        private MssqlManagerFactory $mssqlManagerFactory,
        private LoggerInterface $logger,
        private GraphMailer $graphMailer,
        private SqlFileLoader $sqlFileLoader,
        #[Autowire('%db.lcs_sei%')]
        string $dbLcsSei,
    ) {
        $this->mssqlSei = $this->mssqlManagerFactory->create($dbLcsSei);
    }

    public function getLignesLivraison(string $numeroLivraison): array
    {
        try {
            $query = $this->sqlFileLoader->load('Sei/etiquette_expedition.sql');
            $query = str_replace(
                '{{NUMERO_LIVRAISON}}',
                str_replace("'", "''", $numeroLivraison),
                $query
            );

            return $this->mssqlSei->executeQuery($query);

        } catch (\Exception $e) {
            $this->graphMailer->notifyError('❌ LCS Erreur Étiquette : Récupération lignes livraison', $e);
            $this->logger->error('LCS Erreur Étiquette : Récupération lignes livraison', ['exception' => $e]);

            return [];
        }
    }
}
