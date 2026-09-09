<?php

namespace App\Command\Pilotage;

use App\Service\Pilotage;
use App\Service\Pilotage\PilotageEngine;
use App\Service\Pilotage\PilotageExcelExporter;
use App\Service\Tools\GraphMailer;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mime\Email;

#[AsCommand(
    name: 'app:pilotage:export-livraisons',
    description: 'Génère l\'export Excel du Pilotage Livraisons (9 onglets) et l\'envoie par e-mail',
)]
class ExportPilotageLivraisonsCommand extends Command
{
    /**
     * Collections actives — mêmes valeurs que les options cochées par défaut
     * dans le sélecteur web (templates/pilotage/pilotage.html.twig, #pil-collections).
     * À tenir synchronisé manuellement si le PM fait évoluer les collections suivies.
     */
    private const array COLLECTIONS = ['2026-02-FW', '2027-01-SS'];

    public function __construct(
        private Pilotage $pilotage,
        private PilotageEngine $engine,
        private PilotageExcelExporter $exporter,
        private GraphMailer $graphMailer,
        #[Autowire('%kernel.project_dir%')]
        private string $projectDir,
        #[Autowire(env: 'MAIL_PILOTAGE_LIVRAISON')]
        private string $recipients,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('<comment>Pilotage Livraisons — chargement des données…</comment>');

        $clientRows = $this->pilotage->getBacklogClient(self::COLLECTIONS);
        $fournRows = $this->pilotage->getBacklogFournisseur();
        $stockRows = $this->pilotage->getStock();

        if ($clientRows === [] || $fournRows === [] || $stockRows === []) {
            $output->writeln('<error>Une des trois sources est vide, export annulé.</error>');
            return Command::FAILURE;
        }

        $output->writeln(sprintf(
            '<comment>%d lignes client, %d lignes fournisseur, %d références stock — calcul…</comment>',
            count($clientRows),
            count($fournRows),
            count($stockRows)
        ));

        $result = $this->engine->compute($clientRows, $fournRows, $stockRows);

        $nbFR = count($result['pfr']);
        $nbINT = count($result['pint']);
        $ctrl = $result['ctrl'];

        $output->writeln(sprintf(
            '<comment>%d commandes France, %d commandes International (%d pcs demandées, %d à temps, %d en retard, %d annulées) — génération du fichier Excel…</comment>',
            $nbFR,
            $nbINT,
            (int) round($ctrl['demande']),
            (int) round($ctrl['onT']),
            (int) round($ctrl['late']),
            (int) round($ctrl['canc'])
        ));

        $dir = $this->projectDir . '/var/upload/export/pilotage_livraison';
        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            $output->writeln('<error>Impossible de créer le dossier d\'export : ' . $dir . '</error>');
            return Command::FAILURE;
        }

        $filename = 'Pilotage_livraisons_' . (new \DateTimeImmutable())->format('Y-m-d') . '.xlsx';
        $path = $dir . '/' . $filename;

        $spreadsheet = $this->exporter->build($result);
        $this->exporter->save($spreadsheet, $path);

        $output->writeln('<info>Fichier généré : ' . $path . '</info>');

        $recipients = array_filter(array_map('trim', explode(',', $this->recipients)));
        if ($recipients === []) {
            $output->writeln('<comment>Aucun destinataire configuré (MAIL_PILOTAGE_LIVRAISON) — envoi e-mail ignoré.</comment>');
            return Command::SUCCESS;
        }

        $email = (new Email())
            ->subject('Pilotage Livraisons — export du ' . (new \DateTimeImmutable())->format('d/m/Y'))
            ->html(sprintf(
                '<p>Bonjour,</p><p>Veuillez trouver ci-joint l\'export automatique du Pilotage Livraisons (%d commandes France, %d commandes International).</p><p>Le Coq Sportif — Intranet</p>',
                $nbFR,
                $nbINT
            ))
            ->attachFromPath($path);

        foreach ($recipients as $to) {
            $email->addTo($to);
        }

        try {
            $this->graphMailer->send($email);
            $output->writeln('<info>E-mail envoyé à : ' . implode(', ', $recipients) . '</info>');
        } catch (\Throwable $e) {
            $output->writeln('<error>Échec de l\'envoi e-mail : ' . $e->getMessage() . '</error>');
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
