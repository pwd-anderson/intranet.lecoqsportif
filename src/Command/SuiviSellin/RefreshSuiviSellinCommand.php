<?php

namespace App\Command\SuiviSellin;

use App\Service\SuiviSellin\CronReportMailer;
use App\Service\SuiviSellin\SuiviSellinData;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Prépare les jeux de données du module Suivi Sellin global (fichiers var/suivi_sellin/*.json) pour que les
 * écrans s'ouvrent instantanément. À planifier une fois par jour tôt le matin (cron), avant l'arrivée des
 * commerciaux. Sans argument : tous les jeux ; sinon ceux qui sont nommés (ex. « data »).
 *
 * Un jeu qui échoue n'empêche pas les suivants (son ancien fichier reste servi) ; la commande rend alors un code d'erreur.
 * Un e-mail de compte rendu (OK ou ÉCHEC) part aux destinataires de MAIL_SUIVI_SELLIN, sauf avec --no-mail.
 */
#[AsCommand(name: 'app:suivi-sellin:refresh', description: 'Reconstruit les jeux de données du module Suivi Sellin global')]
final class RefreshSuiviSellinCommand extends Command
{
    public function __construct(
        private readonly SuiviSellinData $data,
        private readonly CronReportMailer $report,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('names', InputArgument::IS_ARRAY, 'Jeux à reconstruire (tous par défaut)')
            ->addOption('no-mail', null, InputOption::VALUE_NONE, 'Ne pas envoyer l\'e-mail de compte rendu');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $started = microtime(true);
        $rows = [];
        $failed = 0;
        $fatal = null;

        try {
            $names = $input->getArgument('names') ?: $this->data->names();
            foreach ($names as $name) {
                $t = microtime(true);
                try {
                    $built = $this->data->refresh($name);
                    $size = strlen($built['json']);
                    $output->writeln(sprintf('%s : %.1f Mo en %.1f s', $name, $size / 1e6, microtime(true) - $t));
                    // Un fichier quasi vide est presque toujours le signe d'une source muette : on le signale sans le bloquer.
                    $rows[] = [$name, $size < 200 ? 'ATTENTION' : 'OK', sprintf('%.2f Mo', $size / 1e6) . ($size < 200 ? ' (fichier presque vide)' : ''), sprintf('%.0f s', microtime(true) - $t)];
                } catch (\Throwable $e) {
                    ++$failed;
                    $output->writeln(sprintf('<error>%s : ÉCHEC — %s</error>', $name, $e->getMessage()));
                    $rows[] = [$name, 'ÉCHEC', mb_substr($e->getMessage(), 0, 300), sprintf('%.0f s', microtime(true) - $t)];
                }
            }
        } catch (\Throwable $e) {
            $fatal = $e;
            $output->writeln('<error>' . $e->getMessage() . '</error>');
        }

        $ok = $fatal === null && $failed === 0;
        if (!$input->getOption('no-mail')) {
            $notes = [sprintf('Durée totale : %.0f s.', microtime(true) - $started)];
            if ($failed > 0) {
                $notes[] = 'Les jeux en échec gardent leur version précédente : les écrans restent utilisables avec des données plus anciennes.';
            }
            $sent = $this->report->send('app:suivi-sellin:refresh', $ok, $rows, $notes, $fatal);
            $output->writeln($sent ? 'E-mail de compte rendu envoyé.' : 'Aucun e-mail envoyé (MAIL_SUIVI_SELLIN vide ou envoi impossible, voir le journal).');
        }

        return $ok ? Command::SUCCESS : Command::FAILURE;
    }
}
