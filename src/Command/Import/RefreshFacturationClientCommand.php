<?php

namespace App\Command\Import;

use App\Service\FacturationClient;
use App\Service\Tools\GraphMailer;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Recalcule la facturation client par plage (0-12 / 12-18 / 18-24 mois), lue par la
 * stat Comptes Clients.
 *
 * Les fenetres sont glissantes par rapport a la date du jour : a planifier en cron
 * quotidien, sinon les plages se decalent silencieusement.
 */
#[AsCommand(
    name: 'app:import-facturation-client',
    description: 'Recalcule la facturation client par plage (0-12 / 12-18 / 18-24 mois) depuis le cube SEI',
)]
class RefreshFacturationClientCommand extends Command
{
    public function __construct(
        private FacturationClient $facturationClient,
        private GraphMailer $graphMailer,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Facturation client par plage');
        $io->text('Table : ' . $this->facturationClient->table());

        try {
            $debut = microtime(true);
            $lignes = $this->facturationClient->refresh();
            $duree = microtime(true) - $debut;

            $io->success(sprintf(
                '%s clients enregistres en %.0f s',
                number_format($lignes, 0, ',', ' '),
                $duree
            ));

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $this->graphMailer->notifyError('❌ Erreur import Facturation Client', $e);
            $io->error('Erreur lors du recalcul de la facturation client : ' . $e->getMessage());

            return Command::FAILURE;
        }
    }
}
