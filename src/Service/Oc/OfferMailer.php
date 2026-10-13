<?php

namespace App\Service\Oc;

use App\Entity\Oc\Offer;
use App\Service\Tools\GraphMailer;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Mails du module OC (envoyés par Microsoft Graph via GraphMailer).
 *
 * Règles d'envoi :
 *  - prod : destinataires réels ;
 *  - dev  : TOUS les mails sont redirigés vers l'adresse admin (ADMIN_EMAIL), comme SOA ; le destinataire prévu est
 *           rappelé dans l'objet pour pouvoir vérifier la logique d'adressage ;
 *  - test : aucun envoi (un test ne doit jamais pouvoir écrire à une vraie personne).
 *
 * Un échec d'envoi ne casse JAMAIS l'action métier déjà enregistrée (soumission, rejet...) : il est journalisé et notifié
 * à l'admin (GraphMailer::notifyError), et la méthode renvoie false.
 */
class OfferMailer
{
    public function __construct(
        private readonly GraphMailer $graphMailer,
        private readonly UrlGeneratorInterface $router,
        private readonly LoggerInterface $logger,
        #[Autowire(env: 'MAIL_HEAD_SALE')]
        private readonly string $headSaleEmails,
        #[Autowire('%admin.email%')]
        private readonly string $adminEmail,
        #[Autowire('%kernel.environment%')]
        private readonly string $environment,
    ) {
    }

    /** Journalise et notifie à l'admin une erreur survenue après une action métier déjà enregistrée. Ne lève jamais. */
    public function reportError(string $title, \Throwable $e): void
    {
        $this->logger->error($title, ['exception' => $e]);
        if (!$this->isEnabled()) {
            return;
        }
        try {
            $this->graphMailer->notifyError('❌ LCS Erreur OC : ' . $title, $e);
        } catch (\Throwable) {
        }
    }

    /** Faux en environnement de test : aucun mail n'y part (voir la règle en tête de classe). */
    public function isEnabled(): bool
    {
        return $this->environment !== 'test';
    }

    /** Prévient le head commercial qu'une OC attend sa validation. */
    public function notifySubmitted(Offer $offer): bool
    {
        return $this->send(
            $this->splitAddresses($this->headSaleEmails),
            sprintf('[OC] Nouvelle OC à valider — %s', $offer->getNumber()),
            'Nouvelle offre commerciale en attente de validation',
            sprintf('<p>Bonjour,</p><p>%s a soumis une offre commerciale à votre validation.</p>%s%s<p>Merci,<br>L\'intranet Le Coq Sportif</p>',
                $this->e($offer->getCreatedByName()), $this->infoBlock($offer), $this->button($offer, 'Consulter et valider l\'OC')),
        );
    }

    /** Prévient le commercial créateur que son OC est rejetée, avec le motif. */
    public function notifyRejected(Offer $offer, string $reason): bool
    {
        return $this->send(
            $this->creatorAddress($offer),
            sprintf('[OC] OC rejetée — %s', $offer->getNumber()),
            'Votre offre commerciale a été rejetée',
            sprintf('<p>Bonjour,</p><p>Votre offre commerciale a été <strong>rejetée</strong> par la Direction.</p><p style="background:#fdeaec;border-left:4px solid #c8102e;padding:10px 14px;"><strong>Motif :</strong> %s</p>%s<p>Un rejet est définitif : vous pouvez créer une nouvelle OC à partir de celle-ci depuis la page de l\'OC.</p>%s<p>Merci,<br>L\'intranet Le Coq Sportif</p>',
                nl2br($this->e($reason)), $this->infoBlock($offer), $this->button($offer, 'Consulter l\'OC')),
        );
    }

    /** Envoie au commercial créateur son OC validée, avec le PDF de l'offre en pièce jointe. */
    public function notifyValidated(Offer $offer, string $pdf, string $pdfFilename): bool
    {
        return $this->send(
            $this->creatorAddress($offer),
            sprintf('[OC] OC validée — %s', $offer->getNumber()),
            'Votre offre commerciale a été validée',
            sprintf('<p>Bonjour,</p><p>Votre offre commerciale a été <strong>validée</strong> par la Direction. Le PDF de l\'offre est joint à ce message.</p>%s%s<p>Merci,<br>L\'intranet Le Coq Sportif</p>',
                $this->infoBlock($offer), $this->button($offer, 'Consulter l\'OC')),
            [[$pdf, $pdfFilename]],
        );
    }

    /**
     * @param list<string>                      $intended    destinataires réels prévus
     * @param list<array{0: string, 1: string}> $attachments [contenu binaire, nom de fichier] PDF
     */
    private function send(array $intended, string $subject, string $title, string $bodyHtml, array $attachments = []): bool
    {
        if ($this->environment === 'test') {
            return false;
        }
        if ($intended === []) {
            $this->logger->warning('OfferMailer : aucun destinataire pour « ' . $subject . ' ».');

            return false;
        }

        $to = $this->environment === 'dev' ? [$this->adminEmail] : $intended;
        if ($this->environment === 'dev') {
            $subject = sprintf('[DEV → prévu : %s] %s', implode(', ', $intended), $subject);
        }

        try {
            $email = (new Email())->to(...$to)->subject($subject)->html($this->wrap($title, $bodyHtml));
            foreach ($attachments as [$content, $filename]) {
                $email->attach($content, $filename, 'application/pdf');
            }
            $this->graphMailer->send($email);

            return true;
        } catch (\Throwable $e) {
            $this->logger->error('OfferMailer : échec d\'envoi', ['subject' => $subject, 'exception' => $e]);
            try {
                $this->graphMailer->notifyError('❌ LCS Erreur OC : envoi de mail', $e);
            } catch (\Throwable) {
                // La notification d'erreur ne doit pas elle-même faire échouer l'action métier.
            }

            return false;
        }
    }

    /** @return list<string> */
    private function splitAddresses(string $value): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/[,;]/', $value) ?: []), static fn (string $a): bool => $a !== ''));
    }

    /** @return list<string> */
    private function creatorAddress(Offer $offer): array
    {
        $email = $offer->getCreatedBy()?->getEmail();

        return $email ? [$email] : [];
    }

    private function link(Offer $offer): string
    {
        return $this->router->generate('app_oc_show', ['id' => $offer->getId(), '_locale' => 'fr'], UrlGeneratorInterface::ABSOLUTE_URL);
    }

    private function infoBlock(Offer $offer): string
    {
        $row = static fn (string $k, string $v): string => sprintf('<tr><td style="padding:3px 12px 3px 0;color:#6b7683;">%s</td><td style="padding:3px 0;"><strong>%s</strong></td></tr>', $k, $v);

        return '<table style="border-collapse:collapse;margin:12px 0;">'
            . $row('N° OC', $this->e($offer->getNumber()))
            . $row('Client', $this->e($offer->getCustomerCode() . ' - ' . $offer->getCustomerName()))
            . $row('Créée par', $this->e($offer->getCreatedByName()))
            . $row('Validité', $offer->getStartDate()->format('d/m/Y') . ' → ' . $offer->getEndDate()->format('d/m/Y'))
            . $row('Articles', (string) $offer->getLines()->count())
            . '</table>';
    }

    private function button(Offer $offer, string $label): string
    {
        return sprintf('<p><a href="%s" style="display:inline-block;background:#1a3767;color:#fff;padding:10px 22px;border-radius:5px;text-decoration:none;font-weight:bold;font-size:13px;">%s</a></p>', $this->e($this->link($offer)), $this->e($label));
    }

    private function wrap(string $title, string $body): string
    {
        return '<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body style="font-family:Arial,sans-serif;font-size:14px;color:#222;max-width:600px;margin:0 auto;padding:20px;">'
            . '<div style="border-top:4px solid #1a3767;padding-top:20px;margin-bottom:20px;"><span style="font-size:18px;font-weight:bold;color:#1a3767;">Le Coq Sportif — Intranet</span></div>'
            . '<h2 style="color:#1a3767;font-size:16px;">' . $this->e($title) . '</h2>' . $body
            . '<hr style="margin-top:30px;border:none;border-top:1px solid #eee;"><p style="font-size:11px;color:#aaa;">Ce message est généré automatiquement par l\'intranet Le Coq Sportif. Merci de ne pas y répondre directement.</p>'
            . '</body></html>';
    }

    private function e(string $s): string
    {
        return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
    }
}
