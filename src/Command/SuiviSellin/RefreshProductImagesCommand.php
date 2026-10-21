<?php

namespace App\Command\SuiviSellin;

use App\Service\ProductImages\ProductImageLibrary;
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
 */
#[AsCommand(name: 'app:suivi-sellin:images-index', description: 'Reconstruit l\'index des photos et sketchs articles (SharePoint)')]
final class RefreshProductImagesCommand extends Command
{
    public function __construct(private readonly ProductImageLibrary $library)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('warm-prefix', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Préfixes de codes article dont on prépare le cache d\'images');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $t = microtime(true);
        $stats = $this->library->rebuild();
        $removed = $this->library->cleanCache();
        $output->writeln(sprintf(
            '%d articles avec photo, %d avec sketch, %d dossiers parcourus, %d images retirées du cache, %.0f s',
            $stats['photos'], $stats['sketches'], $stats['folders'], $removed, microtime(true) - $t,
        ));

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
        }

        return Command::SUCCESS;
    }
}
