<?php

namespace App\Command\SuiviSellin;

use App\Service\ProductImages\ProductImageLibrary;
use App\Service\SuiviSellin\CronReportMailer;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Reconstruit l'index des photos et des sketchs des articles (SharePoint, lecture seule) et nettoie le cache d'images
 * devenu obsolète. À planifier une fois par nuit (le marketing dépose des photos tout au long de la saison).
 *
 * --warm-prefix=27 (répétable) prépare aussi le cache des images dont le code commence par ce préfixe (27 = collections
 * 2027) : sans cela chaque image est téléchargée à sa première demande (~0,5 s), ce qui se voit à l'ouverture d'une page.
 *
 * Un e-mail de compte rendu (OK ou ÉCHEC) part aux destinataires de MAIL_SUIVI_SELLIN, sauf avec --no-mail. Un index qui
 * retrouve moins de la moitié des photos de l'index actuel est refusé (l'ancien est conservé) et signalé comme un échec.
 */
#[AsCommand(name: 'app:suivi-sellin:images-index', description: 'Reconstruit l\'index des photos et sketchs articles (SharePoint)')]
final class RefreshProductImagesCommand extends Command
{
    public function __construct(
        private readonly ProductImageLibrary $library,
        private readonly CronReportMailer $report,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('warm-prefix', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Préfixes de codes article dont on prépare le cache d\'images')
            ->addOption('no-mail', null, InputOption::VALUE_NONE, 'Ne pas envoyer l\'e-mail de compte rendu');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $started = microtime(true);
        $rows = [];
        $notes = [];
        $ok = true;
        $fatal = null;

        try {
            $t = microtime(true);
            $stats = $this->library->rebuild();
            $removed = $this->library->cleanCache();
            $output->writeln(sprintf(
                '%d articles avec photo, %d avec sketch, %d dossiers parcourus, %d images retirées du cache, %.0f s',
                $stats['photos'], $stats['sketches'], $stats['folders'], $removed, microtime(true) - $t,
            ));
            $rows[] = ['Index SharePoint', 'OK', sprintf('%d articles avec photo, %d avec sketch (%d dossiers parcourus)', $stats['photos'], $stats['sketches'], $stats['folders']), sprintf('%.0f s', microtime(true) - $t)];
            $rows[] = ['Nettoyage du cache', 'OK', sprintf('%d images obsolètes retirées', $removed), ''];

            $prefixes = (array) $input->getOption('warm-prefix');
            if ($prefixes !== []) {
                $t = microtime(true);
                $done = 0;
                $failed = 0;
                foreach (array_keys($this->library->codes()) as $code) {
                    $code = (string) $code;
                    foreach ($prefixes as $prefix) {
                        if (str_starts_with($code, (string) $prefix)) {
                            $this->library->file($code) === null ? ++$failed : ++$done;
                            break;
                        }
                    }
                }
                $output->writeln(sprintf('Cache préparé : %d images, %d en échec, %.0f s', $done, $failed, microtime(true) - $t));
                $rows[] = ['Cache des images (préfixes ' . implode(', ', $prefixes) . ')', $failed > 0 ? 'ATTENTION' : 'OK', sprintf('%d images préparées, %d en échec', $done, $failed), sprintf('%.0f s', microtime(true) - $t)];
                if ($failed > 0) {
                    $notes[] = 'Les images en échec seront retéléchargées à leur première demande ; voir le journal (« image indisponible »).';
                }
            }
        } catch (\Throwable $e) {
            $ok = false;
            $fatal = $e;
            $rows[] = ['Index des images', 'ÉCHEC', mb_substr($e->getMessage(), 0, 300), sprintf('%.0f s', microtime(true) - $started)];
            $notes[] = 'L\'ancien index et le cache existant restent en service : les photos déjà connues continuent de s\'afficher.';
            $output->writeln('<error>' . $e->getMessage() . '</error>');
        }

        if (!$input->getOption('no-mail')) {
            $notes[] = sprintf('Durée totale : %.0f s.', microtime(true) - $started);
            $sent = $this->report->send('app:suivi-sellin:images-index', $ok, $rows, $notes, $fatal);
            $output->writeln($sent ? 'E-mail de compte rendu envoyé.' : 'Aucun e-mail envoyé (MAIL_SUIVI_SELLIN vide ou envoi impossible, voir le journal).');
        }

        return $ok ? Command::SUCCESS : Command::FAILURE;
    }
}
