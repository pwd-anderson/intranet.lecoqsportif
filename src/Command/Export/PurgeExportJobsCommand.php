<?php

namespace App\Command\Export;

use App\Repository\ExportJobRepository;
use App\Service\Export\ExportJobRunner;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Supprime les fichiers d'export et leurs demandes passe un certain age.
 *
 * Un export du Backlog Client v2 pese 68 Mo : sans purge, quelques dizaines par jour
 * rempliraient le disque. A planifier en cron quotidien.
 */
#[AsCommand(
    name: 'app:export:purge',
    description: "Supprime les exports generes il y a plus de N heures",
)]
class PurgeExportJobsCommand extends Command
{
    public function __construct(
        private ExportJobRepository $exportJobRepository,
        private ExportJobRunner $runner,
        private EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('heures', null, InputOption::VALUE_REQUIRED,
            'Age au-dela duquel un export est supprime.', '24');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io     = new SymfonyStyle($input, $output);
        $heures = max(1, (int) $input->getOption('heures'));
        $limite = new \DateTimeImmutable(sprintf('-%d hours', $heures));

        $jobs = $this->exportJobRepository->trouverAnterieuresA($limite);

        if ($jobs === []) {
            $io->writeln(sprintf('Aucun export de plus de %d h.', $heures));

            return Command::SUCCESS;
        }

        $octets = 0;

        foreach ($jobs as $job) {
            $chemin = $this->runner->cheminFichier($job);

            if ($chemin !== null && is_file($chemin)) {
                $octets += (int) filesize($chemin);
                unlink($chemin);
            }

            $this->entityManager->remove($job);
        }

        $this->entityManager->flush();

        $io->success(sprintf(
            '%d export(s) supprime(s), %.1f Mo liberes.',
            count($jobs),
            $octets / 1048576
        ));

        return Command::SUCCESS;
    }
}
