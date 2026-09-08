<?php

namespace App\Service;

use App\Entity\MdfRequest;
use App\Service\Tools\GraphMailer;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class MdfMailer
{
    public function __construct(
        private GraphMailer           $graphMailer,
        private UrlGeneratorInterface $router,
        private string                $mailHeadSale,
        private string                $adminEmail,
        #[Autowire('%kernel.environment%')]
        private string $env,
    ) {}

    public function sendSoumissionDirection(MdfRequest $mdf): void
    {
        $to   = $this->resolve($this->mailHeadSale);
        $link = $this->mdfLink($mdf);

        $email = (new Email())
            ->to($to)
            ->subject("[MDF] Nouveau MDF en attente de validation — {$mdf->getNumero()}")
            ->html($this->htmlWrapper(
                "Nouveau MDF en attente de validation",
                "<p>Bonjour,</p>
                 <p>Un nouveau MDF vient d'être créé et est en attente de votre validation.</p>
                 {$this->mdfInfoBlock($mdf)}
                 <p><a href=\"{$link}\" style=\"{$this->btnStyle()}\">Consulter le MDF</a></p>
                 <p>Merci,<br>L'intranet Le Coq Sportif</p>"
            ));

        $this->graphMailer->send($email);
    }

    public function sendValidationRepresentant(MdfRequest $mdf): void
    {
        $to   = $this->resolve($mdf->getRepresentant());
        $link = $this->mdfLink($mdf);

        $email = (new Email())
            ->to($to)
            ->subject("[MDF] Votre MDF a été validé par la Direction — {$mdf->getNumero()}")
            ->html($this->htmlWrapper(
                "MDF validé par la Direction",
                "<p>Bonjour,</p>
                 <p>Votre MDF a été validé par la Direction commerciale.</p>
                 {$this->mdfInfoBlock($mdf)}
                 <p><a href=\"{$link}\" style=\"{$this->btnStyle()}\">Consulter le MDF</a></p>
                 <p>Merci,<br>L'intranet Le Coq Sportif</p>"
            ));

        $this->graphMailer->send($email);
    }

    public function sendValidationFinaleRepresentant(MdfRequest $mdf): void
    {
        $to   = $this->resolve($mdf->getRepresentant());
        $link = $this->mdfLink($mdf);

        $email = (new Email())
            ->to($to)
            ->subject("[MDF] Votre MDF est en attente de validation finale — {$mdf->getNumero()}")
            ->html($this->htmlWrapper(
                "MDF en attente de validation finale",
                "<p>Bonjour,</p>
                 <p>Votre MDF est désormais en attente de validation finale après soumission des preuves.</p>
                 {$this->mdfInfoBlock($mdf)}
                 <p><a href=\"{$link}\" style=\"{$this->btnStyle()}\">Consulter le MDF</a></p>
                 <p>Merci,<br>L'intranet Le Coq Sportif</p>"
            ));

        $this->graphMailer->send($email);
    }

    public function sendRefus(MdfRequest $mdf): void
    {
        $to   = $this->resolve($mdf->getRepresentant());
        $link = $this->mdfLink($mdf);

        $email = (new Email())
            ->to($to)
            ->subject("[MDF] Votre MDF a été refusé — {$mdf->getNumero()}")
            ->html($this->htmlWrapper(
                "MDF refusé",
                "<p>Bonjour,</p>
                 <p>Votre MDF a été refusé par la Direction commerciale.</p>
                 {$this->mdfInfoBlock($mdf)}
                 <p><a href=\"{$link}\" style=\"{$this->btnStyle()}\">Consulter le MDF</a></p>
                 <p>Merci,<br>L'intranet Le Coq Sportif</p>"
            ));

        $this->graphMailer->send($email);
    }

    public function sendArchive(MdfRequest $mdf): void
    {
        $to   = $this->resolve($mdf->getRepresentant());
        $link = $this->mdfLink($mdf);

        $email = (new Email())
            ->to($to)
            ->subject("[MDF] Votre MDF a été validé et archivé — {$mdf->getNumero()}")
            ->html($this->htmlWrapper(
                "MDF validé et archivé",
                "<p>Bonjour,</p>
                 <p>Votre MDF a été validé définitivement et archivé.</p>
                 {$this->mdfInfoBlock($mdf)}
                 <p><a href=\"{$link}\" style=\"{$this->btnStyle()}\">Consulter le MDF</a></p>
                 <p>Merci,<br>L'intranet Le Coq Sportif</p>"
            ));

        $this->graphMailer->send($email);
    }

    private function resolve(string $email): string
    {
        return $this->env === 'dev' ? $this->adminEmail : $email;
    }

    private function mdfLink(MdfRequest $mdf): string
    {
        return $this->router->generate(
            'app_mdf_show',
            ['id' => $mdf->getId(), '_locale' => 'fr'],
            UrlGeneratorInterface::ABSOLUTE_URL
        );
    }

    private function mdfInfoBlock(MdfRequest $mdf): string
    {
        return sprintf(
            '<table style="border-collapse:collapse;margin:16px 0;font-size:13px;">
                <tr><td style="padding:4px 12px 4px 0;color:#666;">N° MDF</td><td><strong>%s</strong></td></tr>
                <tr><td style="padding:4px 12px 4px 0;color:#666;">Client</td><td>%s (%s)</td></tr>
                <tr><td style="padding:4px 12px 4px 0;color:#666;">Représentant</td><td>%s</td></tr>
                <tr><td style="padding:4px 12px 4px 0;color:#666;">Type d\'activité</td><td>%s</td></tr>
                <tr><td style="padding:4px 12px 4px 0;color:#666;">Période</td><td>%s → %s</td></tr>
            </table>',
            htmlspecialchars($mdf->getNumero()),
            htmlspecialchars($mdf->getClientNom()),
            htmlspecialchars($mdf->getClientCode()),
            htmlspecialchars($mdf->getRepresentant()),
            htmlspecialchars($mdf->getTypeActivite()),
            $mdf->getDateDebut()->format('d/m/Y'),
            $mdf->getDateFin()->format('d/m/Y')
        );
    }

    private function htmlWrapper(string $title, string $body): string
    {
        return '<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body style="font-family:Arial,sans-serif;font-size:14px;color:#222;max-width:600px;margin:0 auto;padding:20px;">
            <div style="border-top:4px solid #1a3767;padding-top:20px;margin-bottom:20px;">
                <span style="font-size:18px;font-weight:bold;color:#1a3767;">Le Coq Sportif — Intranet</span>
            </div>
            <h2 style="color:#1a3767;font-size:16px;">' . $title . '</h2>
            ' . $body . '
            <hr style="margin-top:30px;border:none;border-top:1px solid #eee;">
            <p style="font-size:11px;color:#aaa;">Ce message est généré automatiquement par l\'intranet Le Coq Sportif. Merci de ne pas y répondre directement.</p>
        </body></html>';
    }

    private function btnStyle(): string
    {
        return 'display:inline-block;background:#1a3767;color:#fff;padding:10px 22px;border-radius:5px;text-decoration:none;font-weight:bold;font-size:13px;margin-top:12px;';
    }
}
