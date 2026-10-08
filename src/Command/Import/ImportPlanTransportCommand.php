<?php

namespace App\Command\Import;

use App\Service\PlanTransport\PlanTransportImporter;
use App\Service\Tools\GraphMailer;
use App\Service\Tools\SharePointClient;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Importe le plan transport prévision (Excel SharePoint PURCHASING) dans
 * MASTER_TABLES.PLAN_TRANSPORT_PREVISION, lue par la stat Achats.
 *
 * Prévue en cron quotidien. En cas d'échec la table garde sa version précédente (import transactionnel)
 * et un mail d'alerte est envoyé.
 *
 *   php bin/console app:import-plan-transport                  # télécharge depuis SharePoint puis importe
 *   php bin/console app:import-plan-transport --dry-run        # télécharge et contrôle, n'écrit rien
 *   php bin/console app:import-plan-transport --file=/chemin/fichier.xlsx   # fichier local (tests)
 */
#[AsCommand(
    name: 'app:import-plan-transport',
    description: 'Importe le plan transport prévision (Excel SharePoint) dans MASTER_TABLES.PLAN_TRANSPORT_PREVISION',
)]
class ImportPlanTransportCommand extends Command
{
    public function __construct(
        private readonly PlanTransportImporter $importer,
        private readonly SharePointClient $sharePoint,
        private readonly GraphMailer $graphMailer,
        #[Autowire(env: 'PLAN_TRANSPORT_SHAREPOINT_URL')]
        private readonly string $sharePointUrl,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('file', null, InputOption::VALUE_REQUIRED, 'Fichier Excel local (au lieu de SharePoint)')
            ->addOption('sheet', null, InputOption::VALUE_REQUIRED, 'Nom de l\'onglet à lire', PlanTransportImporter::DEFAULT_SHEET)
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Télécharge et contrôle le fichier sans écrire en base');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Import plan transport prévision');
        $io->text('Table : ' . $this->importer->table());

        $localFile = $input->getOption('file');
        $tmp = null;

        try {
            $start = microtime(true);

            if ($localFile !== null) {
                if (!is_file($localFile)) {
                    throw new \RuntimeException("Fichier introuvable : $localFile");
                }
                $path = $localFile;
                $sourceName = basename($localFile);
                $sourceModified = date('c', (int) filemtime($localFile));
            } else {
                $info = $this->sharePoint->getFileInfo($this->sharePointUrl);
                $io->text(sprintf('Fichier SharePoint : %s (%.1f Mo, modifié le %s)', $info['name'], $info['size'] / 1048576, $info['lastModified']));
                $tmp = tempnam(sys_get_temp_dir(), 'plan_transport_') . '.xlsx';
                $this->sharePoint->download($this->sharePointUrl, $tmp);
                $path = $tmp;
                $sourceName = $info['name'];
                $sourceModified = $info['lastModified'];
            }

            $parsed = $this->importer->parse($path, (string) $input->getOption('sheet'));
            $io->text(sprintf('%d lignes lues dans l\'onglet « %s » (%d lignes ignorées : vides / total)', count($parsed['rows']), $input->getOption('sheet'), $parsed['skipped']));

            if ($input->getOption('dry-run')) {
                $io->success('Simulation : fichier valide, rien n\'a été écrit en base.');

                return Command::SUCCESS;
            }

            $inserted = $this->importer->replaceAll($parsed['rows'], $sourceName, $sourceModified);
            $io->success(sprintf('%s lignes importées en %.0f s', number_format($inserted, 0, ',', ' '), microtime(true) - $start));

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $this->graphMailer->notifyError('❌ Erreur import Plan transport prévision', $e);
            $io->error('Import en échec, la table est inchangée : ' . $e->getMessage());

            return Command::FAILURE;
        } finally {
            if ($tmp !== null && is_file($tmp)) {
                @unlink($tmp);
            }
        }
    }
}
