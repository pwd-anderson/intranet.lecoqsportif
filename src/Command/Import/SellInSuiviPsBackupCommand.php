<?php

namespace App\Command\Import;

use App\Service\Sales;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:sell-in-suivi-ps-backup',
    description: 'Snapshot journalier de la table SELL_IN_SUIVI_PS vers SELL_IN_SUIVI_PS_HISTORY',
)]
class SellInSuiviPsBackupCommand extends Command
{
    public function __construct(private Sales $sales)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $count = $this->sales->backupSellInSuiviPs();

        if ($count === false) {
            $io->error('Échec du snapshot SELL_IN_SUIVI_PS_HISTORY');
            return Command::FAILURE;
        }

        $io->success("Snapshot effectué : $count ligne(s) sauvegardée(s) dans SELL_IN_SUIVI_PS_HISTORY");

        return Command::SUCCESS;
    }
}
