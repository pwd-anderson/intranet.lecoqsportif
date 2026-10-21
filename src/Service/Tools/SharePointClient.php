<?php

namespace App\Service\Tools;

use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Lecture de fichiers SharePoint via Microsoft Graph (droit d'application Sites.Read.All accordé à
 * l'application MailGraph, cf. GraphAccessTokenService). Le fichier est désigné par son URL SharePoint
 * "claire" (https://<tenant>.sharepoint.com/<bibliothèque>/<dossier>/<fichier>), encodée au format
 * « shares » de Graph : aucun identifiant de site ou de bibliothèque à connaître.
 */
class SharePointClient
{
    private const GRAPH = 'https://graph.microsoft.com/v1.0';

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly GraphAccessTokenService $tokenService,
    ) {
    }

    /**
     * @return array{name:string,size:int,lastModified:string}
     */
    public function getFileInfo(string $url): array
    {
        $item = $this->httpClient->request('GET', self::GRAPH . '/shares/' . $this->shareId($url) . '/driveItem', [
            'headers' => ['Authorization' => 'Bearer ' . $this->tokenService->getAccessToken()],
        ])->toArray();

        return [
            'name' => (string) ($item['name'] ?? ''),
            'size' => (int) ($item['size'] ?? 0),
            'lastModified' => (string) ($item['lastModifiedDateTime'] ?? ''),
        ];
    }

    /**
     * Liste le contenu d'un dossier (lecture seule). Un dossier de plus de 200 éléments est suivi page par page.
     *
     * @return list<array{name:string,size:int,lastModified:string,isFolder:bool,id:string,driveId:string}>
     */
    public function listFolder(string $url): array
    {
        $next = self::GRAPH . '/shares/' . $this->shareId($url) . '/driveItem/children?$top=200';
        $items = [];
        while ($next !== null) {
            $page = $this->httpClient->request('GET', $next, [
                'headers' => ['Authorization' => 'Bearer ' . $this->tokenService->getAccessToken()],
            ])->toArray();
            foreach ($page['value'] ?? [] as $item) {
                $items[] = [
                    'name' => (string) ($item['name'] ?? ''),
                    'size' => (int) ($item['size'] ?? 0),
                    'lastModified' => (string) ($item['lastModifiedDateTime'] ?? ''),
                    'isFolder' => isset($item['folder']),
                    'id' => (string) ($item['id'] ?? ''),
                    'driveId' => (string) ($item['parentReference']['driveId'] ?? ''),
                ];
            }
            $next = $page['@odata.nextLink'] ?? null;
        }

        return $items;
    }

    /**
     * Télécharge le fichier en flux (fichiers de plusieurs dizaines de Mo : jamais en mémoire).
     */
    public function download(string $url, string $targetPath): void
    {
        $response = $this->httpClient->request('GET', self::GRAPH . '/shares/' . $this->shareId($url) . '/driveItem/content', [
            'headers' => ['Authorization' => 'Bearer ' . $this->tokenService->getAccessToken()],
            'timeout' => 120,
            'max_duration' => 600,
        ]);

        $handle = fopen($targetPath, 'wb');
        if ($handle === false) {
            throw new \RuntimeException("Impossible d'écrire le fichier temporaire : $targetPath");
        }

        try {
            foreach ($this->httpClient->stream($response) as $chunk) {
                fwrite($handle, $chunk->getContent());
            }
        } finally {
            fclose($handle);
        }
    }

    /**
     * Télécharge un élément désigné par son identifiant (bibliothèque + élément, tels que rendus par listFolder()).
     */
    public function downloadItem(string $driveId, string $itemId, string $targetPath): void
    {
        $response = $this->httpClient->request('GET', self::GRAPH . '/drives/' . rawurlencode($driveId) . '/items/' . rawurlencode($itemId) . '/content', [
            'headers' => ['Authorization' => 'Bearer ' . $this->tokenService->getAccessToken()],
            'timeout' => 60,
            'max_duration' => 180,
        ]);

        $handle = fopen($targetPath, 'wb');
        if ($handle === false) {
            throw new \RuntimeException("Impossible d'écrire le fichier temporaire : $targetPath");
        }

        try {
            foreach ($this->httpClient->stream($response) as $chunk) {
                fwrite($handle, $chunk->getContent());
            }
        } finally {
            fclose($handle);
        }
    }

    /**
     * Miniature générée par SharePoint (small, medium ou large = 800 px), ou null si l'élément n'en a pas.
     */
    public function thumbnail(string $driveId, string $itemId, string $size = 'large'): ?string
    {
        $response = $this->httpClient->request('GET', self::GRAPH . '/drives/' . rawurlencode($driveId) . '/items/' . rawurlencode($itemId) . '/thumbnails/0/' . $size . '/content', [
            'headers' => ['Authorization' => 'Bearer ' . $this->tokenService->getAccessToken()],
            'timeout' => 30,
        ]);

        return $response->getStatusCode() === 200 ? $response->getContent() : null;
    }

    /** Encodage « shares » de Graph : "u!" + base64 URL-safe sans padding. */
    private function shareId(string $url): string
    {
        return 'u!' . rtrim(strtr(base64_encode($url), '+/', '-_'), '=');
    }
}
