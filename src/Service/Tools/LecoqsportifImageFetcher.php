<?php

namespace App\Service\Tools;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class LecoqsportifImageFetcher
{
    private const BASE_URL = 'https://www.lecoqsportif.com/cdn/shop/files/';

    /**
     * Cascade de suffixes essayés dans l'ordre.
     */
    private const SUFFIX_CASCADE = ['_2.jpg', '_new_1.jpg', '_1.jpg'];

    public function __construct(
        private HttpClientInterface $httpClient,
        private LoggerInterface $logger,
    ) {}

    /**
     * Récupère l'image d'un article depuis lecoqsportif.com en cascade
     * (_2.jpg → _new_1.jpg → _1.jpg) et retourne le base64 prêt à l'emploi.
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

            try {
                $response = $this->httpClient->request('GET', $url);

                if ($response->getStatusCode() !== 200) {
                    continue;
                }

                $content = $response->getContent();
                $b64 = str_replace(["\r", "\n", "\t"], '', base64_encode($content));

                return [
                    'base64'    => $b64,
                    'imageType' => 'jpg',
                ];

            } catch (\Throwable $e) {
                continue;
            }
        }

        $this->logger->info('LecoqsportifImageFetcher : image introuvable', ['article' => $article]);

        return null;
    }
}
