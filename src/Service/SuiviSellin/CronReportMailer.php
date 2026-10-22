<?php

namespace App\Service\SuiviSellin;

use App\Service\Tools\GraphMailer;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mime\Email;

/**
 * Compte rendu par e-mail des commandes planifiées du module Suivi Sellin (rafraîchissement des données, index des
 * images) : « tout s'est bien passé » ou « échec » avec le détail. Destinataires : variable d'env MAIL_SUIVI_SELLIN
 * (liste séparée par des virgules) ; absente ou vide, aucun e-mail n'est envoyé et la commande fonctionne comme avant.
 * Un échec d'envoi ne masque jamais le résultat de la commande : il est écrit dans le journal.
 */
final class CronReportMailer
{
    public function __construct(
        private readonly GraphMailer $graphMailer,
        private readonly LoggerInterface $logger,
        #[Autowire(env: 'default::MAIL_SUIVI_SELLIN')]
        private readonly ?string $recipients,
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
    ) {
    }

    /** @return list<string> */
    public function recipients(): array
    {
        return array_values(array_filter(array_map('trim', explode(',', (string) $this->recipients))));
    }

    /**
     * @param list<array{0:string,1:string,2?:string,3?:string}> $rows lignes du tableau : libellé, état (OK / ÉCHEC / ATTENTION),
     *                                                                   détail, durée
     * @param list<string> $notes phrases libres sous le tableau
     * @return bool vrai si un e-mail est parti
     */
    public function send(string $command, bool $ok, array $rows, array $notes = [], ?\Throwable $error = null): bool
    {
        $to = $this->recipients();
        if ($to === []) {
            return false;
        }

        $server = preg_match('#/vhosts/([^/]+)/#', $this->projectDir, $m) ? $m[1] : basename($this->projectDir);
        $subject = sprintf('[Suivi Sellin] %s — %s (%s)', $command, $ok ? 'OK' : 'ÉCHEC', $server);

        $body = sprintf('<p><b>%s</b> : %s</p>', htmlspecialchars($command), $ok ? 'terminée sans erreur.' : '<span style="color:#b00020">ÉCHEC, voir le détail ci-dessous.</span>');
        if ($rows !== []) {
            $body .= '<table cellpadding="6" style="border-collapse:collapse;font-family:Arial,sans-serif;font-size:13px">'
                . '<tr style="background:#eee"><th align="left">Élément</th><th align="left">État</th><th align="left">Détail</th><th align="left">Durée</th></tr>';
            foreach ($rows as $r) {
                $color = match ($r[1] ?? '') {'OK' => '#1a7f37', 'ATTENTION' => '#b45309', default => '#b00020'};
                $body .= sprintf(
                    '<tr style="border-top:1px solid #ccc"><td>%s</td><td style="color:%s"><b>%s</b></td><td>%s</td><td>%s</td></tr>',
                    htmlspecialchars($r[0]), $color, htmlspecialchars($r[1] ?? ''), htmlspecialchars($r[2] ?? ''), htmlspecialchars($r[3] ?? ''),
                );
            }
            $body .= '</table>';
        }
        foreach ($notes as $n) {
            $body .= '<p>' . htmlspecialchars($n) . '</p>';
        }
        if ($error !== null) {
            $body .= '<p><b>Erreur :</b> ' . htmlspecialchars($error->getMessage()) . '</p><pre style="font-size:11px;white-space:pre-wrap">'
                . htmlspecialchars(mb_substr($error->getTraceAsString(), 0, 2500)) . '</pre>';
        }
        $body .= sprintf('<p style="color:#777;font-size:11px">Serveur : %s · %s · %s</p>', htmlspecialchars($this->projectDir), htmlspecialchars((string) gethostname()), date('d/m/Y H:i'));

        try {
            $email = (new Email())->subject($subject)->html($body);
            foreach ($to as $address) {
                $email->addTo($address);
            }
            $this->graphMailer->send($email);

            return true;
        } catch (\Throwable $e) {
            $this->logger->error('CronReportMailer : e-mail de compte rendu non envoyé', ['command' => $command, 'error' => $e->getMessage()]);

            return false;
        }
    }
}
