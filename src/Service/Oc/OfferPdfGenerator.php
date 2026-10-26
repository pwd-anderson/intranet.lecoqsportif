<?php

namespace App\Service\Oc;

use App\Entity\Oc\Offer;
use App\Service\Tools\LecoqsportifImageFetcher;
use Dompdf\Dompdf;
use Dompdf\Options;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Twig\Environment;

/**
 * PDF de l'offre commerciale (voir templates/oc/pdf/offer.html.twig), généré à la validation.
 *
 * Contenu = ce que le commercial peut montrer au client : prix net facturé, prix public indicatif, marge. Jamais le
 * prix envoyé à X3. L'adresse du client est lue dans X3 au moment de la génération (absente si X3 ne répond pas : le
 * PDF se génère quand même). Aucun accès réseau pendant le rendu Dompdf : photos et logo sont intégrés en base64.
 *
 * Photos : même règle que les stats (cascade de noms de fichiers puis recherche Shopify, via LecoqsportifImageFetcher),
 * réduites à 180 px pour garder un PDF léger, mises en cache, avec un budget de temps total : au-delà, les articles
 * restants sont affichés sans photo (la génération ne doit pas bloquer l'écran de validation).
 */
class OfferPdfGenerator
{
    /** Temps total accordé à la recherche des photos pour un PDF (secondes). */
    public const PHOTO_BUDGET_SECONDS = 25;
    private const PHOTO_MAX_WIDTH = 180;
    private const PHOTO_FOUND_TTL = 604800;   // 7 jours
    private const PHOTO_MISSING_TTL = 3600;   // une photo absente est retentée au bout d'une heure

    public function __construct(
        private readonly Environment $twig,
        private readonly LecoqsportifImageFetcher $images,
        private readonly OcX3Lookup $x3,
        private readonly CacheInterface $cache,
        private readonly LoggerInterface $logger,
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
    ) {
    }

    public function filename(Offer $offer): string
    {
        return 'OC_' . preg_replace('/[^A-Za-z0-9_-]/', '', $offer->getNumber()) . '.pdf';
    }

    /** HTML qui sera converti en PDF (séparé de generate() pour pouvoir le vérifier dans les tests). */
    public function renderHtml(Offer $offer): string
    {
        $deadline = microtime(true) + self::PHOTO_BUDGET_SECONDS;

        // Lignes regroupées par article : la photo (celle du parent) n'est cherchée et affichée qu'une fois par article, sur sa première ligne ;
        // les tailles à un prix propre suivent, avec leur taille.
        $rows = [];
        foreach (OfferSizing::groups($offer->getLines()->toArray()) as $group) {
            $photo = $this->photo($group['parent'], $deadline);
            foreach ($group['lines'] as $i => $line) {
                $rows[] = [
                    'line' => $line,
                    'first' => $i === 0,
                    'photo' => $i === 0 ? $photo : null,
                    'sizeLabels' => $group['sizeLabels'],
                    'hasParentLine' => $group['parentLine'] !== null,
                ];
            }
        }

        return $this->twig->render('oc/pdf/offer.html.twig', [
            'offer' => $offer,
            'rows' => $rows,
            'address' => $this->x3->getCustomerAddress($offer->getCustomerCode()),
            'creatorEmail' => $offer->getCreatedBy()?->getEmail(),
            'logo' => $this->logo(),
        ]);
    }

    /** @return string contenu binaire du PDF */
    public function generate(Offer $offer): string
    {
        $html = $this->renderHtml($offer);

        $cacheDir = $this->projectDir . '/var/cache/dompdf';
        if (!is_dir($cacheDir)) {
            @mkdir($cacheDir, 0775, true);
        }

        $options = new Options();
        $options->set('isRemoteEnabled', false);   // jamais d'accès réseau ni de fichier arbitraire pendant le rendu
        $options->set('isPhpEnabled', false);
        $options->set('defaultFont', 'DejaVu Sans'); // accents, « ° », tirets longs
        $options->set('chroot', $this->projectDir . '/public');
        $options->set('tempDir', $cacheDir);
        $options->set('fontCache', $cacheDir);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        // Pied de page sur chaque page : n° d'OC à gauche, pagination à droite.
        $canvas = $dompdf->getCanvas();
        $font = $dompdf->getFontMetrics()->getFont('DejaVu Sans');
        $canvas->page_text(30, 818, 'Offre commerciale ' . $offer->getNumber() . ' — Le Coq Sportif', $font, 7, [0.45, 0.45, 0.45]);
        $canvas->page_text(500, 818, 'Page {PAGE_NUM} / {PAGE_COUNT}', $font, 7, [0.45, 0.45, 0.45]);

        return (string) $dompdf->output();
    }

    private function logo(): ?string
    {
        $file = $this->projectDir . '/public/assets/images/logo_main.png';
        $bytes = is_file($file) ? file_get_contents($file) : false;

        return $bytes !== false ? 'data:image/png;base64,' . base64_encode($bytes) : null;
    }

    /** Photo de l'article en data URI JPEG réduite, ou null (introuvable, ou budget de temps épuisé). */
    private function photo(string $article, float $deadline): ?string
    {
        $key = 'oc_pdf_photo_' . md5($article);

        // Budget épuisé : on ne lance pas de nouvelle recherche, sauf si le résultat est déjà en cache.
        if (microtime(true) > $deadline && !$this->cache->hasItem($key)) {
            return null;
        }

        try {
            return $this->cache->get($key, function (ItemInterface $item) use ($article): ?string {
                $found = $this->images->fetchAsBase64($article);
                $small = $found !== null ? $this->shrink($found['base64']) : null;
                $item->expiresAfter($small !== null ? self::PHOTO_FOUND_TTL : self::PHOTO_MISSING_TTL);

                return $small;
            });
        } catch (\Throwable $e) {
            $this->logger->warning('LCS OC PDF : photo indisponible', ['article' => $article, 'exception' => $e]);

            return null;
        }
    }

    private function shrink(string $base64): ?string
    {
        $binary = base64_decode($base64, true);
        $image = $binary !== false ? @imagecreatefromstring($binary) : false;
        if ($image === false) {
            return null;
        }

        if (imagesx($image) > self::PHOTO_MAX_WIDTH) {
            $scaled = imagescale($image, self::PHOTO_MAX_WIDTH);
            if ($scaled !== false) {
                $image = $scaled;
            }
        }

        ob_start();
        imagejpeg($image, null, 80);
        $jpeg = (string) ob_get_clean();

        return $jpeg !== '' ? 'data:image/jpeg;base64,' . base64_encode($jpeg) : null;
    }
}
