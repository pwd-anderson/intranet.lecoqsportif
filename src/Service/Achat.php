<?php

namespace App\Service;

use App\Factory\MssqlManagerFactory;
use App\Infrastructure\Sql\SqlFileLoader;
use App\Service\Tools\GraphMailer;
use App\Service\Tools\MssqlManager;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mime\Email;
use Psr\Log\LoggerInterface;

class Achat
{

    private MssqlManager $mssqlLcs;
    private MssqlManager $mssqlSei;
    public function __construct(
        private MssqlManagerFactory $mssqlManagerFactory,
        private LoggerInterface $logger,
        private GraphMailer $graphMailer,
        private SqlFileLoader $sqlFileLoader,
        private Divers $divers,
        #[Autowire('%db.lcs%')]
        string $dbLcs,
        #[Autowire('%db.lcs_sei%')]
        string $dbLcsSei,
    )
    {
        $this->mssqlLcs = $this->mssqlManagerFactory->create($dbLcs);
        $this->mssqlSei = $this->mssqlManagerFactory->create($dbLcsSei);
    }

    public function getBAcklogFournisseurNavision(): array
    {
        try {
            $query = $this->sqlFileLoader->load('Navision/backlog_fournisseur_tmp.sql');
            $data = $this->mssqlLcs->executeMultiStatement($query);
            return $data;

        } catch (\Exception $e) {
            $this->graphMailer->notifyError('❌ LCS Erreur Backlog fournisseur : Récupération de données achat', $e);
            $this->logger->error('LCS Erreur Backlog fournisseur : Récupération de données achat', ['exception' => $e]);
        }
    }

    public function getBacklogFournisseur(): array
    {
        try {
            $query = $this->sqlFileLoader->load('Sei/backlog_fournisseur.sql');
            $data = $this->mssqlSei->executeQuery($query);

            if (empty($data)) {
                return [];
            }

            $taux = $this->divers->getExchangeRatesValues();
            $supplierReferences = $this->divers->getSupplierReferences();
            $today = new \DateTimeImmutable('today');
            $inThreeDays = $today->modify('+3 days');

            foreach ($data as $row) {
                $row->PRIX_EUR = isset($taux[$row->CUR_0]) && (float) $taux[$row->CUR_0] > 0
                    ? (float) $row->NETPRI_0 / (float) $taux[$row->CUR_0]
                    : 0.0;

                $row->REF_FOURN = $supplierReferences[$row->TSICOD_0][$row->ITMREF_0] ?? null;
                $row->STATUS = null;

                if (!empty($row->EXTRCPDAT_0)) {
                    $expectedDate = new \DateTimeImmutable($row->EXTRCPDAT_0);

                    if ($expectedDate < $today) {
                        $row->STATUS = 'EN RETARD';
                    } elseif ($expectedDate <= $inThreeDays) {
                        $row->STATUS = 'BIENTOT EN RETARD';
                    }
                }
                $row->QUANTITE = (int) round((float) $row->QTYUOM_0 - (float) $row->RCPQTYSTU_0);
                $row->PRIX = round((float) $row->NETPRI_0 * (int) $row->QUANTITE, 2);
                $row->PRIX_EUR = isset($taux[$row->CUR_0]) && (float) $taux[$row->CUR_0] > 0
                    ? round(((float) $row->NETPRI_0 / (float) $taux[$row->CUR_0]) * (int) $row->QUANTITE, 2)
                    : 0.0;
            }

            return $data;
        } catch (\Throwable $e) {
            $this->graphMailer->notifyError(
                '❌ LCS Erreur Backlog fournisseur X3 : Récupération de données achat',
                $e
            );

            $this->logger->error(
                'LCS Erreur Backlog fournisseur X3 : Récupération de données achat',
                ['exception' => $e]
            );

            return [];
        }
    }

}
