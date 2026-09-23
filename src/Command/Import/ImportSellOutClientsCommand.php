<?php

namespace App\Command\Import;

use App\Service\SellOut;
use App\Service\Tools\GraphMailer;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Mime\Email;

/**
 * Recalcule les agrégats de la stat "Sell Out par Client".
 *
 * Indépendante de app:import-donnees-sellout, qui alimente le dashboard :
 * tables distinctes, grain distinct. À planifier en cron après celle-ci.
 */
#[AsCommand(
    name: 'app:import-sellout-clients',
    description: 'Recalcule les agrégats Sell Out par client (SPORT2000 / INTERSPORT)',
)]
class ImportSellOutClientsCommand extends Command
{
    public function __construct(
        private SellOut $sellOut,
        private GraphMailer $graphMailer,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Import Sell Out par Client (SPORT2000 / INTERSPORT)');

        try {
            $debut   = microtime(true);
            $results = $this->sellOut->refreshAll();
            $duree   = microtime(true) - $debut;

            $io->section("Résultat d'enregistrement");
            $lines = [];

            foreach ($results as $cube => $rows) {
                $message = sprintf('%s : %s lignes insérées', $cube, number_format($rows, 0, ',', ' '));
                $io->text($message);
                $lines[] = $message;
            }

            $io->success(sprintf('Données Sell Out par client enregistrées en %.0f s', $duree));

            $email = (new Email())
                ->to('ajacob@lecoqsportif.com')
                ->subject('Mise à jour données Sell Out par Client')
                ->html(
                    "Import Sell Out par Client (SPORT2000 / INTERSPORT)<br><br>"
                    . implode('<br>', $lines)
                    . sprintf('<br><br>Durée : %.0f s.', $duree)
                );

            $this->graphMailer->send($email);

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $this->graphMailer->notifyError('❌ Erreur import Sell Out par Client', $e);
            $io->error('Erreur lors du recalcul des agrégats Sell Out par client.');

            return Command::FAILURE;
        }
    }
}
