<?php

namespace App\Command\Import;


use App\Service\Imports\ExchangeRateImport;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

use Symfony\Component\Mime\Email;
use App\Service\Tools\GraphMailer;


#[AsCommand(
    name: 'app:exchange-rate-api',
    description: 'on récupère les cours de devise depuis un API Exchange Rate',
)]
class ExchangeRateApiCommand extends Command
{
    public function __construct(private ExchangeRateImport $import)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $this->import->fetchAndStoreMonthlyAveragesFromXml();

        $io->success("Commande exécutée avec succès");

        return Command::SUCCESS;
    }
}
