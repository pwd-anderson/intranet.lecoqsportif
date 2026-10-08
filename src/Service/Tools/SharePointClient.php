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

    /** Encodage « shares » de Graph : "u!" + base64 URL-safe sans padding. */
    private function shareId(string $url): string
    {
        return 'u!' . rtrim(strtr(base64_encode($url), '+/', '-_'), '=');
    }
}
