<?php

namespace App\Command\Export;

use App\Factory\MssqlManagerFactory;
use App\Service\Export\AskSupplyDataExporter;
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
 * Génère les exports de 7 stats (mêmes colonnes et valeurs que leur bouton d'export manuel) et les dépose dans le
 * dossier SharePoint PURCHASING / ASK SUPPLY DATA SOURCE, sous un nom de fichier fixe (le fichier de la veille est
 * remplacé). Prévue pour un cron quotidien.
 *
 *   php bin/console app:export-sharepoint-ask-supply-data-source                        # les 7 exports
 *   php bin/console app:export-sharepoint-ask-supply-data-source --only=produits        # un seul (répétable)
 *   php bin/console app:export-sharepoint-ask-supply-data-source --dry-run              # fichiers en local, rien déposé
 *
 * Un export qui échoue (ou renvoie 0 ligne) n'écrase jamais le fichier précédent ; les autres continuent. Un seul mail
 * récapitule les échecs, et le code de sortie est non nul s'il y en a.
 * Droit requis : Sites.ReadWrite.All pour l'application MailGraph.
 */
#[AsCommand(
    name: 'app:export-sharepoint-ask-supply-data-source',
    description: 'Exporte 7 stats (Achats, Stock, ADV) vers le dossier SharePoint PURCHASING/ASK SUPPLY DATA SOURCE',
)]
class ExportSharePointAskSupplyDataSourceCommand extends Command
{
    /** Dossier de dépôt (URL « claire » de la bibliothèque Documents partagés). */
    private const FOLDER_URL = 'https://lecoqsportif.sharepoint.com/Documents%20partages/PURCHASING/ASK%20SUPPLY%20DATA%20SOURCE';

    /** Collections de l'export Produits (line list en cours). */
    private const PRODUITS_COLLECTIONS = ['2027-01-SS', '2027-02-FW'];

    /** Etat des commandes clients : les N collections les plus récentes. */
    private const ETAT_COMMANDES_COLLECTIONS_COUNT = 3;

    public function __construct(
        private readonly AskSupplyDataExporter $exporter,
        private readonly SharePointClient $sharePoint,
        private readonly GraphMailer $graphMailer,
        private readonly MssqlManagerFactory $mssqlFactory,
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('only', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Limite à ces exports : ' . implode(', ', AskSupplyDataExporter::keys()))
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Génère les fichiers dans var/upload/export/ask_supply_data_source sans les déposer');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $dryRun = (bool) $input->getOption('dry-run');

        $keys = AskSupplyDataExporter::keys();
        $only = (array) $input->getOption('only');
        if ($only !== []) {
            $unknown = array_diff($only, $keys);
            if ($unknown !== []) {
                $io->error('Export(s) inconnu(s) : ' . implode(', ', $unknown) . '. Valeurs : ' . implode(', ', $keys));

                return Command::INVALID;
            }
            $keys = array_values(array_intersect($keys, $only));
        }

        $dir = $this->projectDir . '/var/upload/export/ask_supply_data_source';
        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            $io->error("Impossible de créer le dossier $dir");

            return Command::FAILURE;
        }

        $io->title('Export SharePoint ASK SUPPLY DATA SOURCE' . ($dryRun ? ' (dry-run)' : ''));

        $failures = [];
        foreach ($keys as $key) {
            $fileName = AskSupplyDataExporter::FILES[$key];
            // Fichier de travail distinct du nom final : un export en échec ne touche pas à la dernière version locale.
            $work = $dir . '/' . $fileName . '.part';
            $final = $dir . '/' . $fileName;
            $start = microtime(true);

            try {
                // Connexions rouvertes avant chaque export (celle d'un service resté inactif pendant un export long est
                // coupée par le serveur) ; un export à 0 ligne (les services avalent leurs erreurs SQL) ou interrompu par une
                // exception (coupure en cours de lecture) est retenté une fois.
                $rows = 0;
                for ($attempt = 1; $attempt <= 2 && $rows <= 0; $attempt++) {
                    $this->mssqlFactory->reconnectAll();
                    try {
                        $rows = $this->exporter->export($key, $work, self::PRODUITS_COLLECTIONS, self::ETAT_COMMANDES_COLLECTIONS_COUNT);
                    } catch (\Throwable $e) {
                        if ($attempt === 2) {
                            throw $e;
                        }
                        $io->text(sprintf('   %s : %s — nouvelle tentative', $fileName, strtok($e->getMessage(), "\n")));
                    }
                }
                if ($rows <= 0) {
                    throw new \RuntimeException('Aucune ligne exportée : fichier non déposé (requête en échec ou résultat vide).');
                }
                if (!rename($work, $final)) {
                    throw new \RuntimeException("Impossible de finaliser $final");
                }

                if ($dryRun) {
                    $io->text(sprintf('OK %-28s %s lignes, %.1f Mo, %.0f s (non déposé)', $fileName, number_format($rows, 0, ',', ' '), filesize($final) / 1048576, microtime(true) - $start));
                    continue;
                }

                $this->sharePoint->upload(self::FOLDER_URL, $fileName, $final);
                @unlink($final);
                $io->text(sprintf('OK %-28s %s lignes, déposé en %.0f s', $fileName, number_format($rows, 0, ',', ' '), microtime(true) - $start));
            } catch (\Throwable $e) {
                @unlink($work);
                $failures[$key] = $e->getMessage();
                $io->text(sprintf('KO %-28s %s', $fileName, $e->getMessage()));
            }
        }

        if ($failures !== []) {
            $message = 'Export SharePoint ASK SUPPLY DATA SOURCE : ' . count($failures) . ' échec(s) sur ' . count($keys);
            foreach ($failures as $key => $reason) {
                $message .= "\n- $key : $reason";
            }
            if (!$dryRun) {
                $this->graphMailer->notifyError('❌ LCS Erreur export SharePoint ASK SUPPLY DATA SOURCE', new \RuntimeException($message));
            }
            $io->error(count($failures) . ' export(s) en échec.');

            return Command::FAILURE;
        }

        $io->success(count($keys) . ' export(s) terminé(s).');

        return Command::SUCCESS;
    }
}
