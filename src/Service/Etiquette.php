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

    /**
     * Lignes d'une commande d'achat, lues dans les réceptions fournisseur (une ligne par commande et par article).
     * Le numéro est reconnu avec ou sans son suffixe « /NNN ».
     */
    public function getLignesCommandeAchat(string $numeroCommande): array
    {
        try {
            $numeroCommande = trim($numeroCommande);
            if ($numeroCommande === '') {
                return [];
            }
            // Apostrophes doublées (comme la requête livraison) ; pour le LIKE, \ % _ [ saisis par l'utilisateur sont en plus
            // neutralisés (ESCAPE '\' dans la requête).
            $exact = str_replace("'", "''", $numeroCommande);
            $like  = str_replace("'", "''", strtr($numeroCommande, ['\\' => '\\\\', '%' => '\\%', '_' => '\\_', '[' => '\\[']));

            $query = str_replace(
                ['{{NUMERO_COMMANDE}}', '{{NUMERO_COMMANDE_LIKE}}'],
                [$exact, $like],
                $this->sqlFileLoader->load('Sei/etiquette_expedition_commande_achat.sql')
            );

            return $this->mssqlSei->executeQuery($query);

        } catch (\Exception $e) {
            $this->graphMailer->notifyError('❌ LCS Erreur Étiquette : Récupération lignes commande d\'achat', $e);
            $this->logger->error('LCS Erreur Étiquette : Récupération lignes commande d\'achat', ['exception' => $e]);

            return [];
        }
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
