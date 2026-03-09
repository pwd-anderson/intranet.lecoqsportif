<?php

namespace App\Command\Import;

use App\Service\Dashboards\MainDashboard;
use App\Service\Tools\GraphMailer;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Mime\Email;

#[AsCommand(
    name: 'app:import-donnees-cube-vente-intranet',
    description: 'Recalcule les données cube ventes intranet (agrégation mensuelle)',
)]
class ImportDonneesCubeVenteIntranetCommand extends Command
{
    public function __construct(
        private MainDashboard $mainDashboard,
        private GraphMailer $graphMailer
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Récupération de données de cube ventes -> intranet');

        try {

            $results = $this->mainDashboard->refreshAllSalesCubes();

            $io->section("Résultat d'enregistrement");

            $lines = [];

            foreach ($results as $cube => $rows) {

                $message = sprintf('%s : %d lignes insérées', $cube, $rows);

                $io->text($message);
                $lines[] = $message;

            }

            $io->success('Données enregistrées');

            $mailBody =
                "Récupération de données de cube ventes -> intranet\n\n"
                . implode("\n", $lines) .
                "\n\nDonnées enregistrées avec succès.";

            $email = (new Email())
                ->to('ajacob@lecoqsportif.com')
                ->subject('Mise à jour de données Dashboards')
                ->html($mailBody);

            $this->graphMailer->send($email);

            return Command::SUCCESS;

        } catch (\Exception $e) {

            $this->graphMailer->notifyError(
                '❌ Erreur Enregistrement de données cubes ventes intranet',
                $e
            );

            $io->error('Erreur lors du recalcul des cubes.');

            return Command::FAILURE;
        }
    }
}
