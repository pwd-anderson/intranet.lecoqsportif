<?php

namespace App\Command\Export;

use App\Service\CaMargeX3;
use App\Service\Tools\GraphMailer;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsCommand(
    name: 'app:export:ca-marge-x3',
    description: 'Génère le fichier CA / Marge détails (factures et avoirs X3 de l\'année en cours) au format CSV',
)]
class ExportCaMargeX3Command extends Command
{
    public function __construct(
        private CaMargeX3 $caMarge,
        private GraphMailer $graphMailer,
        #[Autowire('%kernel.project_dir%')]
        private string $projectDir,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $dir = $this->projectDir . '/var/upload/export/ca_marge';
        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            $output->writeln('<error>Impossible de créer le dossier d\'export : ' . $dir . '</error>');
            return Command::FAILURE;
        }

        $now = new \DateTimeImmutable();
        // La requête remonte les N dernières années : le nom du fichier porte la plage, pas une année
        $debut = $now->modify('-' . CaMargeX3::ANNEES_HISTORIQUE . ' years');
        $periode = $debut->format('Y') === $now->format('Y')
            ? $now->format('Y')
            : $debut->format('Y') . '-' . $now->format('Y');

        $path = sprintf('%s/CA_MARGE_DETAILS_MASTER_%s_%s.csv', $dir, $periode, $now->format('dmY'));

        $output->writeln('<comment>Export CA / Marge X3 ' . $periode . ' — génération…</comment>');
        $start = microtime(true);

        try {
            $count = $this->caMarge->writeSalesCsv($path);
        } catch (\Throwable $e) {
            @unlink($path);
            $this->graphMailer->notifyError('❌ LCS Erreur Export CA / Marge X3', $e);
            $output->writeln('<error>Échec de l\'export : ' . $e->getMessage() . '</error>');
            return Command::FAILURE;
        }

        $output->writeln(sprintf('<info>%d lignes écrites en %.1f s : %s</info>', $count, microtime(true) - $start, $path));

        return Command::SUCCESS;
    }
}
