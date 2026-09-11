<?php

namespace App\Service;

use App\Factory\MssqlManagerFactory;
use App\Infrastructure\Sql\SqlFileLoader;
use App\Service\Tools\GraphMailer;
use App\Service\Tools\MssqlManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Module Distributor Availability — voir CLAUDE.md pour l'architecture.
 * Porte le pattern de connexion lazy MSSQL utilisé par Pilotage.php, sur les 4 sources
 * dédiées au périmètre des 5 distributeurs suivis (Pacific, Umnyama, Niala, Getro, Sports Life).
 */
class DistributorAvailability
{
    /** Nombre de mois utilisé pour la coupure de réserve marché France (RULES.FR_RESERVE_MONTHS côté JS de référence) */
    public const FR_RESERVE_MONTHS = 3;

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

    public function getBacklogClient(): array
    {
        try {
            $sql = $this->sqlFileLoader->load('Sei/distributor_availability_backlog_client.sql');
            return $this->mssqlSei->executeQuery($sql);
        } catch (\Exception $e) {
            $this->graphMailer->notifyError('❌ Distributor Availability : getBacklogClient', $e);
            $this->logger->error('DistributorAvailability : getBacklogClient', ['exception' => $e]);
            return [];
        }
    }

    /**
     * FR_CUT_DATE = aujourd'hui + FR_RESERVE_MONTHS (voir RULES.FR_RESERVE_MONTHS
     * dans le fichier de référence PM). Remplace le placeholder {{FR_CUT_DATE}} du fichier SQL.
     */
    public function getFranceReserve(): array
    {
        try {
            $sql = $this->sqlFileLoader->load('Sei/distributor_availability_france_reserve.sql');
            $cutDate = (new \DateTimeImmutable('today'))
                ->modify('+' . self::FR_RESERVE_MONTHS . ' months')
                ->format('Y-m-d');
            $sql = str_replace('{{FR_CUT_DATE}}', $cutDate, $sql);

            return $this->mssqlSei->executeQuery($sql);
        } catch (\Exception $e) {
            $this->graphMailer->notifyError('❌ Distributor Availability : getFranceReserve', $e);
            $this->logger->error('DistributorAvailability : getFranceReserve', ['exception' => $e]);
            return [];
        }
    }

    public function getBacklogFournisseur(): array
    {
        try {
            $sql = $this->sqlFileLoader->load('Sei/distributor_availability_backlog_fournisseur.sql');
            return $this->mssqlSei->executeQuery($sql);
        } catch (\Exception $e) {
            $this->graphMailer->notifyError('❌ Distributor Availability : getBacklogFournisseur', $e);
            $this->logger->error('DistributorAvailability : getBacklogFournisseur', ['exception' => $e]);
            return [];
        }
    }

    public function getStock(): array
    {
        try {
            $sql = $this->sqlFileLoader->load('Sei/distributor_availability_stock.sql');
            return $this->mssqlSei->executeQuery($sql);
        } catch (\Exception $e) {
            $this->graphMailer->notifyError('❌ Distributor Availability : getStock', $e);
            $this->logger->error('DistributorAvailability : getStock', ['exception' => $e]);
            return [];
        }
    }
}
