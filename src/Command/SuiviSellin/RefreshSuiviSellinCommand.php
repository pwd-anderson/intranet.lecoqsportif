<?php

namespace App\Command\SuiviSellin;

use App\Service\SuiviSellin\SuiviSellinData;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Prépare les jeux de données du module Suivi Sellin global (fichiers var/suivi_sellin/*.json) pour que les
 * écrans s'ouvrent instantanément. À planifier une fois par jour tôt le matin (cron), avant l'arrivée des
 * commerciaux. Sans argument : tous les jeux ; sinon ceux qui sont nommés (ex. « data »).
 */
#[AsCommand(name: 'app:suivi-sellin:refresh', description: 'Reconstruit les jeux de données du module Suivi Sellin global')]
final class RefreshSuiviSellinCommand extends Command
{
    public function __construct(private readonly SuiviSellinData $data)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('names', InputArgument::IS_ARRAY, 'Jeux à reconstruire (tous par défaut)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $names = $input->getArgument('names') ?: $this->data->names();
        foreach ($names as $name) {
            $t = microtime(true);
            $built = $this->data->refresh($name);
            $output->writeln(sprintf('%s : %.1f Mo en %.1f s', $name, strlen($built['json']) / 1e6, microtime(true) - $t));
        }

        return Command::SUCCESS;
    }
}
