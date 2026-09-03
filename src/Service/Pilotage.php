<?php

namespace App\Service;

use App\Factory\MssqlManagerFactory;
use App\Infrastructure\Sql\SqlFileLoader;
use App\Service\Tools\GraphMailer;
use App\Service\Tools\MssqlManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class Pilotage
{
    private MssqlManager $mssqlSei;

    public function __construct(
        private MssqlManagerFactory $mssqlManagerFactory,
        private LoggerInterface $logger,
        private GraphMailer $graphMailer,
        private SqlFileLoader $sqlFileLoader,
        private Divers $divers,
        #[Autowire('%db.lcs_sei%')]
        string $dbLcsSei,
    ) {
        $this->mssqlSei = $this->mssqlManagerFactory->create($dbLcsSei);
    }

    /**
     * @param string[] $collections  Liste de collections à filtrer (multi-sélection)
     */
    public function getBacklogClient(array $collections = []): array
    {
        try {
            $sql = $this->sqlFileLoader->load('Sei/backlog_client_pilotage.sql');

            if (!empty($collections)) {
                $escaped = implode(',', array_map(
                    fn(string $c) => "'" . str_replace("'", "''", $c) . "'",
                    $collections
                ));
                $sql .= " AND SOQ.YCOLLECT_0 IN ($escaped)";
            }

            $rows = $this->mssqlSei->executeQuery($sql);
            $this->applyEurConversion($rows);

            return $rows;
        } catch (\Exception $e) {
            $this->graphMailer->notifyError('❌ Pilotage : getBacklogClient', $e);
            $this->logger->error('Pilotage : getBacklogClient', ['exception' => $e]);
            return [];
        }
    }

    /**
     * Le prix n'est disponible qu'en devise d'origine côté X3 (PRIX UNITAIRE + DEVISE).
     * On applique ici le même calcul que le Backlog Client X3 (Sales::enrichBacklogClientsX3Rows) :
     * montant EUR = (prix unitaire / taux de la devise) * quantité.
     */
    private function applyEurConversion(array &$rows): void
    {
        if ($rows === []) {
            return;
        }

        $taux = $this->divers->getExchangeRatesValues();

        foreach ($rows as $row) {
            $priceUnit = (float) ($row->{'PRIX UNITAIRE'} ?? 0);
            $quantity = (float) ($row->{'QUANTITE'} ?? 0);
            $rate = $taux[$row->{'DEVISE'}] ?? null;

            $row->{'PRIX EUR'} = ($rate !== null && (float) $rate > 0)
                ? round(($priceUnit / (float) $rate) * $quantity, 2)
                : 0.0;
        }
    }

    public function getBacklogFournisseur(): array
    {
        try {
            $sql = $this->sqlFileLoader->load('Sei/backlog_fournisseur_pilotage.sql');
            return $this->mssqlSei->executeQuery($sql);
        } catch (\Exception $e) {
            $this->graphMailer->notifyError('❌ Pilotage : getBacklogFournisseur', $e);
            $this->logger->error('Pilotage : getBacklogFournisseur', ['exception' => $e]);
            return [];
        }
    }

    public function getStock(): array
    {
        try {
            $sql = $this->sqlFileLoader->load('Sei/stock_pilotage.sql');
            return $this->mssqlSei->executeQuery($sql);
        } catch (\Exception $e) {
            $this->graphMailer->notifyError('❌ Pilotage : getStock', $e);
            $this->logger->error('Pilotage : getStock', ['exception' => $e]);
            return [];
        }
    }
}
