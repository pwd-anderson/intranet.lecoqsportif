<?php

namespace App\Command\Import;

use App\Service\Dashboards\SellOutDashboard;
use App\Service\Tools\GraphMailer;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Mime\Email;

#[AsCommand(
    name: 'app:import-donnees-sellout',
    description: 'Recalcule les données cube sell-out (SPORT2000 / INTERSPORT)',
)]
class ImportDonneesSellOutCommand extends Command
{
    public function __construct(
        private SellOutDashboard $sellOutDashboard,
        private GraphMailer $graphMailer
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Import données cube sell-out (SPORT2000 / INTERSPORT)');

        try {
            $results = $this->sellOutDashboard->refreshAll();

            $io->section("Résultat d'enregistrement");
            $lines = [];

            foreach ($results as $cube => $rows) {
                $message = sprintf('%s : %d lignes insérées', $cube, $rows);
                $io->text($message);
                $lines[] = $message;
            }

            $io->success('Données sell-out enregistrées');

            $mailBody =
                "Import données cube sell-out (SPORT2000 / INTERSPORT)\n\n"
                . implode("\n", $lines)
                . "\n\nDonnées enregistrées avec succès.";

            $email = (new Email())
                ->to('ajacob@lecoqsportif.com')
                ->subject('Mise à jour données Dashboard Sell-Out')
                ->html($mailBody);

            $this->graphMailer->send($email);

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->graphMailer->notifyError('❌ Erreur import données sell-out', $e);
            $io->error('Erreur lors du recalcul des cubes sell-out.');
            return Command::FAILURE;
        }
    }
}
