<?php

namespace App\Service;

use App\Entity\SoaRequest;
use App\Service\Tools\GraphMailer;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class SoaMailer
{
    public function __construct(
        private GraphMailer           $graphMailer,
        private UrlGeneratorInterface $router,
        private string                $mailHeadSale,
        private string                $adminEmail,
        private string                $projectDir,
        #[Autowire('%kernel.environment%')]
        private string $env,
    ) {}

    // ── Envoi soumission à la Direction ──────────────────────────
    public function sendSoumissionDirection(SoaRequest $soa): void
    {
        $to   = $this->resolve($this->mailHeadSale);
        $link = $this->soaLink($soa);

        $email = (new Email())
            ->to($to)
            ->subject("[SOA] Nouveau SOA en attente de validation — {$soa->getNumero()}")
            ->html($this->htmlWrapper(
                "Nouveau SOA en attente de validation",
                "<p>Bonjour,</p>
                 <p>Un nouveau SOA vient d'être créé et est en attente de votre validation.</p>
                 {$this->soaInfoBlock($soa)}
                 <p><a href=\"{$link}\" style=\"{$this->btnStyle()}\">Consulter le SOA</a></p>
                 <p>Merci,<br>L'intranet Le Coq Sportif</p>"
            ));

        $this->graphMailer->send($email);
    }

    // ── Envoi validation Direction → Représentant ─────────────────
    public function sendValidationRepresentant(SoaRequest $soa): void
    {
        $to   = $this->resolve($soa->getRepresentant());
        $link = $this->soaLink($soa);

        $email = (new Email())
            ->to($to)
            ->subject("[SOA] Votre SOA a été validé par la Direction — {$soa->getNumero()}")
            ->html($this->htmlWrapper(
                "SOA validé par la Direction",
                "<p>Bonjour,</p>
                 <p>Votre SOA a été validé par la Direction commerciale.</p>
                 {$this->soaInfoBlock($soa)}
                 <p><a href=\"{$link}\" style=\"{$this->btnStyle()}\">Consulter le SOA</a></p>
                 <p>Merci,<br>L'intranet Le Coq Sportif</p>"
            ));

        $this->graphMailer->send($email);
    }

    // ── Envoi contrat PDF au client ───────────────────────────────
    public function sendContratClient(SoaRequest $soa): void
    {
        $emails = $soa->getClientEmails();
        if (empty($emails)) {
            return;
        }

        $to  = $this->resolve($emails[0]);
        $pdf = $this->generateContratPdf($soa);

        $emailMsg = (new Email())
            ->to($to)
            ->subject("[Le Coq Sportif] Confirmation de participation marketing — {$soa->getNumero()}")
            ->html($this->htmlWrapper(
                "Confirmation de votre accord de participation marketing",
                "<p>Cher Distributeur,</p>
                 <p>Veuillez trouver ci-joint la confirmation de notre offre de Participation Marketing pour vos activités de promotion de la marque.</p>
                 <p>Ce document récapitule les conditions convenues. Nous vous remercions de votre confiance.</p>
                 <p>Cordialement,<br>Le Coq Sportif</p>"
            ))
            ->attach($pdf, "{$soa->getNumero()}_contrat.pdf", 'application/pdf');

        $this->graphMailer->send($emailMsg);
    }

    // ── Envoi validation finale → Représentant ────────────────────
    public function sendValidationFinaleRepresentant(SoaRequest $soa): void
    {
        $to   = $this->resolve($soa->getRepresentant());
        $link = $this->soaLink($soa);

        $email = (new Email())
            ->to($to)
            ->subject("[SOA] Votre SOA est en attente de validation finale — {$soa->getNumero()}")
            ->html($this->htmlWrapper(
                "SOA en attente de validation finale",
                "<p>Bonjour,</p>
                 <p>Votre SOA est désormais en attente de validation finale après soumission des preuves.</p>
                 {$this->soaInfoBlock($soa)}
                 <p><a href=\"{$link}\" style=\"{$this->btnStyle()}\">Consulter le SOA</a></p>
                 <p>Merci,<br>L'intranet Le Coq Sportif</p>"
            ));

        $this->graphMailer->send($email);
    }

    // ── Envoi refus → Représentant ────────────────────────────────
    public function sendRefus(SoaRequest $soa): void
    {
        $to   = $this->resolve($soa->getRepresentant());
        $link = $this->soaLink($soa);

        $email = (new Email())
            ->to($to)
            ->subject("[SOA] Votre SOA a été refusé — {$soa->getNumero()}")
            ->html($this->htmlWrapper(
                "SOA refusé",
                "<p>Bonjour,</p>
                 <p>Votre SOA a été refusé par la Direction commerciale.</p>
                 {$this->soaInfoBlock($soa)}
                 <p><a href=\"{$link}\" style=\"{$this->btnStyle()}\">Consulter le SOA</a></p>
                 <p>Merci,<br>L'intranet Le Coq Sportif</p>"
            ));

        $this->graphMailer->send($email);
    }

    // ── Envoi archivage → Représentant ────────────────────────────
    public function sendArchive(SoaRequest $soa): void
    {
        $to   = $this->resolve($soa->getRepresentant());
        $link = $this->soaLink($soa);

        $email = (new Email())
            ->to($to)
            ->subject("[SOA] Votre SOA a été validé et archivé — {$soa->getNumero()}")
            ->html($this->htmlWrapper(
                "SOA validé et archivé",
                "<p>Bonjour,</p>
                 <p>Votre SOA a été validé définitivement et archivé.</p>
                 {$this->soaInfoBlock($soa)}
                 <p><a href=\"{$link}\" style=\"{$this->btnStyle()}\">Consulter le SOA</a></p>
                 <p>Merci,<br>L'intranet Le Coq Sportif</p>"
            ));

        $this->graphMailer->send($email);
    }

    // ── Génération PDF contrat ────────────────────────────────────
    private function generateContratPdf(SoaRequest $soa): string
    {
        $logoPath = $this->projectDir . '/public/assets/images/logo_main.png';
        $logoB64  = base64_encode(file_get_contents($logoPath));
        $logoSrc  = 'data:image/png;base64,' . $logoB64;

        $montantTotal = array_reduce(
            $soa->getProducts()->toArray(),
            fn($c, $p) => $c + (float) $p->getMontantMax(),
            0.0
        );
        $devise = $soa->getClientDevise();

        $lignesHtml = '';
        foreach ($soa->getProducts() as $p) {
            $lignesHtml .= sprintf(
                '<tr>
                    <td style="border:1px solid #ccc;padding:6px 10px;">%s</td>
                    <td style="border:1px solid #ccc;padding:6px 10px;text-align:center;">%d</td>
                    <td style="border:1px solid #ccc;padding:6px 10px;text-align:right;">%s</td>
                    <td style="border:1px solid #ccc;padding:6px 10px;text-align:center;">%s</td>
                </tr>',
                htmlspecialchars($p->getArticleCode()),
                $p->getQteMax(),
                number_format((float) $p->getMontantSoa(), 2, ',', ' '),
                htmlspecialchars($devise)
            );
        }

        $html = '<!DOCTYPE html>
<html lang="fr">
<head><meta charset="UTF-8">
<style>
    body { font-family: Arial, sans-serif; font-size: 12px; color: #222; margin: 40px; }
    h2 { font-size: 14px; font-weight: bold; margin-top: 28px; margin-bottom: 6px; }
    table.produits { border-collapse: collapse; width: 100%; margin: 14px 0; }
    table.produits th { background: #f0f0f0; border: 1px solid #ccc; padding: 6px 10px; text-align: center; font-size: 11px; }
    .montant-max { font-weight: bold; text-decoration: underline; margin: 10px 0; }
    .conditions p { text-align: justify; margin: 8px 0; }
    .signature { text-align: right; margin-top: 40px; }
</style>
</head>
<body>
<img src="' . $logoSrc . '" style="height:50px; margin-bottom:30px;" alt="Le Coq Sportif">

<p>Cher Distributeur,</p>
<p>Suite à nos différents échanges, nous vous confirmons par la présente notre offre de Participation Marketing pour vos activités de promotion de la marque, selon les indications figurant ci-dessous :</p>

<h2>Description</h2>
<p>
    <strong>Distributeur :</strong> ' . htmlspecialchars($soa->getClientNom()) . '<br>
    <strong>Numéro de campagne SOA (à mentionner dans votre correspondance) :</strong> ' . htmlspecialchars($soa->getNumero()) . '<br>
    <strong>Titre de l\'action marketing :</strong> ' . htmlspecialchars($soa->getTitre()) . '<br>
    <strong>Description de l\'action marketing :</strong> ' . htmlspecialchars($soa->getFocusProduit() ?? '—') . '<br>
    <strong>Début de l\'action marketing :</strong> ' . $soa->getDateDebut()->format('d/m/Y') . '<br>
    <strong>Fin de l\'action marketing :</strong> ' . $soa->getDateFin()->format('d/m/Y') . '
</p>

<table class="produits">
    <thead>
        <tr>
            <th>Produit</th>
            <th>Quantité</th>
            <th>Montant</th>
            <th>Devise</th>
        </tr>
    </thead>
    <tbody>' . $lignesHtml . '</tbody>
</table>

<p class="montant-max">Montant Maximum de Participation Marketing : ' . number_format($montantTotal, 2, ',', ' ') . ' ' . $devise . '</p>

<h2>Conditions contractuelles de cette offre</h2>
<div class="conditions">
<p>Le Coq Sportif s\'engage à vous soutenir dans la Participation Marketing mentionnée ci-dessus, à condition que votre Action Marketing respecte les conditions définies ci-dessous :</p>
<p><strong>1.</strong> Le Client doit réaliser l\'Action Marketing exactement telle que décrite ci-dessus et s\'engage à transmettre à Le Coq Sportif les documents permettant de justifier que l\'Action Marketing satisfait aux conditions convenues, au moins 10 jours avant la date de début prévue.</p>
<p>Le Coq Sportif peut demander la soumission de preuves supplémentaires pour vérifier l\'exécution de ces conditions.</p>
<p><strong>2.</strong> Le Client doit émettre une facture à Le Coq Sportif pour un montant n\'excédant pas le montant maximum de Participation Marketing, au plus tôt le dernier jour de l\'Action Marketing et au plus tard 30 jours après la fin de l\'Action Marketing.</p>
<p>Le Client devra joindre à la facture les justificatifs prouvant que l\'Action Marketing a été réalisée conformément aux prévisions. Ces justificatifs comprennent, sans s\'y limiter, les fichiers Excel contenant les preuves de ventes, les extraits des systèmes ERP du Client relatifs aux ventes et au stock, les visuels publicitaires ou captures d\'écran des campagnes marketing associées, ainsi que tout engagement contractuel ou paiement effectué auprès de tiers.</p>
<p><strong>3.</strong> Le montant final de la Participation Marketing sera établi sur la base de la quantité justifiée dans les preuves de ventes fournies. Si cette quantité est inférieure au montant maximum convenu, la Participation Marketing sera calculée au prorata. En aucun cas, le montant de la Participation Marketing ne pourra dépasser le montant maximum convenu.</p>
<p><strong>4.</strong> Lorsque le montant de la Participation Marketing est exigible, Le Coq Sportif se réserve le droit de procéder au règlement, à sa discrétion, soit par virement bancaire vers le compte du Client, soit par émission d\'un avoir correspondant sur le compte du Client.</p>
<p><strong>5.</strong> Le Client ne peut pas compenser le montant de la Participation Marketing avec d\'autres montants dus à Le Coq Sportif.</p>
<p>Cette offre annule et remplace toutes les offres et conditions précédemment discutées ou convenues concernant l\'Action Marketing décrite ci-dessus.</p>
<p>La présente offre et l\'ensemble des droits et obligations des parties sont soumis exclusivement au droit français. Le tribunal compétent est celui du siège social de Le Coq Sportif.</p>
<p>La présente offre est conditionnée à la soumission et à l\'acceptation par Le Client auprès de Le Coq Sportif de l\'ensemble des documents requis aux points 1 et 2 ci-dessus. Si les documents fournis ne constituent pas une preuve suffisante de la réalisation de l\'Action Marketing, cette offre sera considérée comme nulle et non avenue.</p>
<p>Pour être éligible à ce programme de participation marketing, le Client doit avoir communiqué ses informations mensuelles de ventes et de stock de manière régulière, conformément à son accord de distribution.</p>
<p>En acceptant cette offre, vous acceptez l\'ensemble des conditions énoncées ci-dessus.</p>
<p>Nous restons à votre disposition pour toute question ou information complémentaire.</p>
<p>Cordialement,</p>
</div>
<div class="signature">Le Coq Sportif</div>
</body>
</html>';

        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', false);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->output();
    }

    // ── Helpers ───────────────────────────────────────────────────
    private function resolve(string $email): string
    {
        return $this->env === 'dev' ? $this->adminEmail : $email;
    }

    private function soaLink(SoaRequest $soa): string
    {
        return $this->router->generate(
            'app_soa_show',
            ['id' => $soa->getId(), '_locale' => 'fr'],
            UrlGeneratorInterface::ABSOLUTE_URL
        );
    }

    private function soaInfoBlock(SoaRequest $soa): string
    {
        return sprintf(
            '<table style="border-collapse:collapse;margin:16px 0;font-size:13px;">
                <tr><td style="padding:4px 12px 4px 0;color:#666;">N° SOA</td><td><strong>%s</strong></td></tr>
                <tr><td style="padding:4px 12px 4px 0;color:#666;">Client</td><td>%s (%s)</td></tr>
                <tr><td style="padding:4px 12px 4px 0;color:#666;">Représentant</td><td>%s</td></tr>
                <tr><td style="padding:4px 12px 4px 0;color:#666;">Période</td><td>%s → %s</td></tr>
            </table>',
            htmlspecialchars($soa->getNumero()),
            htmlspecialchars($soa->getClientNom()),
            htmlspecialchars($soa->getClientCode()),
            htmlspecialchars($soa->getRepresentant()),
            $soa->getDateDebut()->format('d/m/Y'),
            $soa->getDateFin()->format('d/m/Y')
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
