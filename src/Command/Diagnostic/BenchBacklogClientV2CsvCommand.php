<?php

namespace App\Command\Diagnostic;

use App\Repository\AggridOptionRepository;
use App\Service\AgGrid\Ssrm\SsrmRequest;
use App\Service\BacklogClientV2;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Commande de diagnostic TEMPORAIRE (à supprimer une fois l'analyse terminée).
 *
 * Reproduit exactement l'export CSV du Backlog Client v2, mais en écrivant dans un
 * fichier local au serveur : ni HTTP, ni réseau client, ni proxy. Elle chronomètre
 * séparément le temps passé à attendre les lignes de MSSQL et le temps passé à les
 * formater en PHP, pour savoir lequel des deux plafonne.
 *
 * À lancer sur le même environnement que la stat, puis en local, et à comparer.
 */
#[AsCommand(
    name: 'app:diag:bench-backlog-client-v2-csv',
    description: 'Chronomètre la génération du CSV Backlog Client v2 (MSSQL vs formatage PHP).'
)]
class BenchBacklogClientV2CsvCommand extends Command
{
    public function __construct(
        private BacklogClientV2 $backlogClientV2,
        private AggridOptionRepository $aggridOptionRepository,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('collections', null, InputOption::VALUE_REQUIRED,
                'Collections séparées par des virgules. Par défaut : toutes (option "Tout").')
            ->addOption('limit', null, InputOption::VALUE_REQUIRED,
                'S\'arrêter après N lignes (utile pour un test rapide).', '0')
            ->addOption('out', null, InputOption::VALUE_REQUIRED,
                'Fichier de sortie. "null" pour ne rien écrire et isoler le coût du disque.',
                'var/bench_backlog_client_v2.csv');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $collections = array_values(array_filter(array_map(
            'trim',
            explode(',', (string) $input->getOption('collections'))
        )));

        $options = $collections === []
            ? ['allCollections' => true]
            : ['collections' => $collections];

        $request = SsrmRequest::fromArray([
            'filterModel' => [],
            'sortModel'   => [],
            'options'     => $options,
        ]);

        $limit = (int) $input->getOption('limit');
        $out   = (string) $input->getOption('out');

        $io->title('Bench export CSV — Backlog Client v2');
        $io->writeln($collections === []
            ? 'Collections : <info>toutes</info>'
            : 'Collections : <info>' . implode(', ', $collections) . '</info>');
        $io->writeln('Sortie      : <info>' . ($out === 'null' ? 'aucune (mesure pure)' : $out) . '</info>');
        $io->newLine();

        // Mêmes colonnes que l'export réel : celles visibles dans la config AG Grid
        $columns = [];
        foreach ($this->aggridOptionRepository->findBy(['gridName' => 'backlog_client_v2_grid'], ['orderIndex' => 'ASC']) as $option) {
            if ($option->isVisible() !== false) {
                $columns[$option->getField()] = ['header' => $option->getHeaderName(), 'type' => (string) $option->getType()];
            }
        }

        $io->writeln('Colonnes    : <info>' . count($columns) . '</info>');
        $io->newLine();

        $stats = $this->backlogClientV2->benchCsv($request, $columns, $out === 'null' ? null : $out, $limit);

        $total = max($stats['total'], 0.000001);
        $rows  = max($stats['rows'], 1);

        $io->section('Résultats');
        $io->definitionList(
            ['Temps total'                 => sprintf('%.1f s', $stats['total'])],
            ['Préparation + 1re ligne'     => sprintf('%.1f s', $stats['first_row'])],
            ['Attente des lignes (MSSQL)'  => sprintf('%.1f s  (%.0f %%)', $stats['fetch'], 100 * $stats['fetch'] / $total)],
            ['Formatage + écriture (PHP)'  => sprintf('%.1f s  (%.0f %%)', $stats['format'], 100 * $stats['format'] / $total)],
            ['Lignes'                      => number_format($stats['rows'], 0, ',', ' ')],
            ['Octets écrits'               => sprintf('%.1f Mo', $stats['bytes'] / 1048576)],
            ['Débit lignes'                => sprintf('%s lignes/s', number_format($rows / $total, 0, ',', ' '))],
            ['Débit données'               => sprintf('%.2f Mo/s', ($stats['bytes'] / 1048576) / $total)],
        );

        $io->note(
            'Comparer ces chiffres entre la préprod et le poste local. Si "Attente des lignes" '
            . 'domine et diffère fortement, le goulot est la liaison serveur → SEI Cube. '
            . 'Si c\'est "Formatage + écriture", le goulot est le CPU du serveur.'
        );

        return Command::SUCCESS;
    }
}
