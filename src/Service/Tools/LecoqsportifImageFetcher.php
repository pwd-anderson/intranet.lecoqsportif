<?php

namespace App\Service\Tools;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class LecoqsportifImageFetcher
{
    /**
     * Duree de mise en cache de la recherche Shopify (lookupImageUrl) : meme cle article
     * -> meme photo, qui ne change pas d'un jour a l'autre. Sans ce cache, chaque appel
     * declenche un aller-retour en direct vers Shopify (3 a 7s observes en pratique) —
     * insoutenable des qu'une page affiche plusieurs dizaines d'images dont la plupart
     * ratent la cascade de noms devines (cas d'Arrivees Logtex : references d'entrepot,
     * pas toutes publiees sur l'e-shop). Le resultat "rien trouve" est mis en cache
     * lui aussi : c'est justement le cas le plus frequent et le plus couteux a repeter.
     */
    private const LOOKUP_CACHE_TTL = 86400;
    private const BASE_URL = 'https://www.lecoqsportif.com/cdn/shop/files/';

    /**
     * Endpoint public de recherche prédictive Shopify — renvoie la vraie URL image
     * du produit, fiable même quand le nom de fichier ne suit aucune convention
     * devinable (ex: suffixe UUID ajouté par Shopify à l'upload,
     * 2410410_1_7c3d672a-fc7e-4a40-bc4f-032303c44289.webp).
     */
    private const SEARCH_URL = 'https://www.lecoqsportif.com/search/suggest.json';

    /**
     * Cascade de suffixes essayés dans l'ordre. Le webp suit les mêmes suffixes que
     * le jpg (_2 / _new_1 / _1, ex: 2421731_1.webp) et est essayé en priorité, avant
     * la cascade jpg historique en secours.
     */
    private const SUFFIX_CASCADE = [
        '_2.webp', '_new_1.webp', '_1.webp',
        '_2.jpg', '_new_1.jpg', '_1.jpg',
    ];

    public function __construct(
        private HttpClientInterface $httpClient,
        private LoggerInterface $logger,
        private CacheInterface $cache,
    ) {}

    /**
     * Récupère l'image d'un article depuis lecoqsportif.com en cascade
     * (_2.webp → _new_1.webp → _1.webp → _2.jpg → _new_1.jpg → _1.jpg) et retourne
     * le base64 prêt à l'emploi.
     *
     * Le .webp est décodé et réencodé en JPEG avant l'envoi : Excel (via ExcelJS
     * côté export) ne sait pas embarquer une image au format webp, seulement
     * jpeg/png/gif.
     *
     * @param string $article Code article (sera nettoyé)
     * @return array|null Tableau ['base64' => ..., 'imageType' => 'jpg'] ou null si rien trouvé
     */
    public function fetchAsBase64(string $article): ?array
    {
        $article = preg_replace('/[^A-Za-z0-9_-]/', '', $article);

        if (!$article) {
            return null;
        }

        foreach (self::SUFFIX_CASCADE as $suffix) {
            $url = self::BASE_URL . $article . $suffix;
            $result = $this->downloadAsBase64($url);

            if ($result !== null) {
                return $result;
            }
        }

        // Aucun des noms de fichier devinés n'existe : dernier recours fiable via
        // l'endpoint de recherche Shopify, qui renvoie la vraie URL de l'image
        // (utile pour les fichiers uploadés avec un suffixe UUID imprévisible).
        $realUrl = $this->lookupImageUrl($article);
        if ($realUrl !== null) {
            $result = $this->downloadAsBase64($realUrl);
            if ($result !== null) {
                return $result;
            }
        }

        $this->logger->info('LecoqsportifImageFetcher : image introuvable', ['article' => $article]);

        return null;
    }

    /**
     * Interroge la recherche prédictive Shopify pour retrouver la vraie URL image
     * du produit correspondant au code article, indépendamment du nom de fichier
     * réel. Retourne null si aucun produit ne correspond ou si l'appel échoue.
     */
    public function lookupImageUrl(string $article): ?string
    {
        $article = preg_replace('/[^A-Za-z0-9_-]/', '', $article);

        if (!$article) {
            return null;
        }

        // Le resultat (trouve ou non) est mis en cache : voir LOOKUP_CACHE_TTL.
        return $this->cache->get(
            'lcs_image_lookup_' . $article,
            function (ItemInterface $item) use ($article): ?string {
                $item->expiresAfter(self::LOOKUP_CACHE_TTL);

                try {
                    $response = $this->httpClient->request('GET', self::SEARCH_URL, [
                        'query' => [
                            'q' => $article,
                            'resources[type]' => 'product',
                            'resources[limit]' => 1,
                        ],
                    ]);

                    if ($response->getStatusCode() !== 200) {
                        return null;
                    }

                    $data = $response->toArray(false);
                    $products = $data['resources']['results']['products'] ?? [];

                    if (empty($products)) {
                        return null;
                    }

                    return $products[0]['image'] ?? $products[0]['featured_image']['url'] ?? null;

                } catch (\Throwable $e) {
                    return null;
                }
            }
        );
    }

    /**
     * Télécharge une URL d'image et retourne son contenu en base64 (converti en
     * JPEG si c'est un .webp). Retourne null si le téléchargement échoue.
     */
    private function downloadAsBase64(string $url): ?array
    {
        try {
            $response = $this->httpClient->request('GET', $url);

            if ($response->getStatusCode() !== 200) {
                return null;
            }

            $content = $response->getContent();

            if (str_contains(strtolower($url), '.webp')) {
                $content = $this->convertWebpToJpeg($content);
                if ($content === null) {
                    return null;
                }
            }

            return [
                'base64'    => str_replace(["\r", "\n", "\t"], '', base64_encode($content)),
                'imageType' => 'jpg',
            ];

        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Décode un webp (GD, disponible sur ce projet) et le réencode en JPEG.
     */
    private function convertWebpToJpeg(string $webpContent): ?string
    {
        $image = @imagecreatefromstring($webpContent);

        if ($image === false) {
            return null;
        }

        ob_start();
        imagejpeg($image, null, 90);
        $jpeg = ob_get_clean();
        imagedestroy($image);

        return $jpeg !== false ? $jpeg : null;
    }
}
