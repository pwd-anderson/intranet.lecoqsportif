<?php

namespace App\Command\Export;

use App\Entity\ExportJob;
use App\Repository\ExportJobRepository;
use App\Service\Export\ExportJobRunner;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Genere le fichier d'une demande d'export.
 *
 * Lancee en tache de fond par le controleur juste apres la demande. Avec l'option
 * --pending, traite toutes les demandes en attente : utile en cron si le serveur web
 * n'a pas le droit de demarrer un processus.
 */
#[AsCommand(
    name: 'app:export:run',
    description: "Genere le fichier d'une demande d'export (table export_job)",
)]
class RunExportJobCommand extends Command
{
    public function __construct(
        private ExportJobRepository $exportJobRepository,
        private ExportJobRunner $runner,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('id', InputArgument::OPTIONAL, "Identifiant de la demande d'export")
            ->addOption('pending', null, InputOption::VALUE_NONE,
                'Traite toutes les demandes en attente (mode cron).');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Un export volumineux dure plus longtemps que la limite par defaut
        set_time_limit(0);

        $jobs = $input->getOption('pending')
            ? $this->exportJobRepository->findBy(['statut' => ExportJob::STATUT_EN_ATTENTE], ['creeLe' => 'ASC'])
            : array_filter([$this->exportJobRepository->find((int) $input->getArgument('id'))]);

        if ($jobs === []) {
            $io->writeln('Aucune demande a traiter.');

            return Command::SUCCESS;
        }

        $echecs = 0;

        foreach ($jobs as $job) {
            $debut = microtime(true);
            $ok    = $this->runner->executer($job);

            if ($ok) {
                $io->success(sprintf(
                    'Export #%d genere en %.0f s : %s lignes, %.1f Mo',
                    $job->getId(),
                    microtime(true) - $debut,
                    number_format((int) $job->getLignes(), 0, ',', ' '),
                    (int) $job->getTaille() / 1048576
                ));
            } else {
                ++$echecs;
                $io->error(sprintf('Export #%d en echec : %s', $job->getId(), $job->getErreur()));
            }
        }

        return $echecs === 0 ? Command::SUCCESS : Command::FAILURE;
    }
}
