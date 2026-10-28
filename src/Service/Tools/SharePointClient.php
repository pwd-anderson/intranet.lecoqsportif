<?php

namespace App\Service\Tools;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * Lecture (Sites.Read.All) et dépôt (Sites.ReadWrite.All, à accorder) de fichiers SharePoint via Microsoft Graph, avec
 * l'application MailGraph, cf. GraphAccessTokenService). Le fichier est désigné par son URL SharePoint
 * "claire" (https://<tenant>.sharepoint.com/<bibliothèque>/<dossier>/<fichier>), encodée au format
 * « shares » de Graph : aucun identifiant de site ou de bibliothèque à connaître.
 */
class SharePointClient
{
    private const GRAPH = 'https://graph.microsoft.com/v1.0';
    /** Multiple de 320 Kio exigé par Graph. */
    private const CHUNK_SIZE = 10485760;
    /** Pauses (secondes) avant chaque nouvel essai quand le fichier à remplacer est verrouillé. */
    private const LOCK_RETRY_DELAYS = [20, 60, 120];

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

    /**
     * Dépose (ou remplace) un fichier dans un dossier SharePoint désigné par son URL « claire ».
     * Session de téléversement par blocs de 10 Mo : fonctionne pour toutes les tailles et ne charge jamais le fichier
     * en mémoire. Nécessite le droit d'application Sites.ReadWrite.All (403 sinon).
     */
    public function upload(string $folderUrl, string $fileName, string $localPath): void
    {
        // 409 / 423 : le fichier existant est verrouillé (ouvert dans Excel, actualisation Power Query / Power Automate,
        // synchronisation en cours). Le verrou est souvent bref : on réessaie avant de signaler l'échec.
        for ($attempt = 0; ; $attempt++) {
            try {
                $this->uploadOnce($folderUrl, $fileName, $localPath);

                return;
            } catch (SharePointFileLockedException $e) {
                if (!isset(self::LOCK_RETRY_DELAYS[$attempt])) {
                    throw $e;
                }
                sleep(self::LOCK_RETRY_DELAYS[$attempt]);
            }
        }
    }

    private function uploadOnce(string $folderUrl, string $fileName, string $localPath): void
    {
        $size = filesize($localPath);
        if ($size === false || $size === 0) {
            throw new \RuntimeException("Fichier vide ou illisible : $localPath");
        }

        $auth = ['Authorization' => 'Bearer ' . $this->tokenService->getAccessToken()];

        $folder = $this->httpClient->request('GET', self::GRAPH . '/shares/' . $this->shareId($folderUrl) . '/driveItem', [
            'headers' => $auth,
        ])->toArray();
        if (!isset($folder['folder'], $folder['id'], $folder['parentReference']['driveId'])) {
            throw new \RuntimeException("L'URL ne désigne pas un dossier SharePoint : $folderUrl");
        }

        $response = $this->httpClient->request('POST', sprintf(
            '%s/drives/%s/items/%s:/%s:/createUploadSession',
            self::GRAPH,
            $folder['parentReference']['driveId'],
            $folder['id'],
            rawurlencode($fileName)
        ), [
            'headers' => $auth,
            'json' => ['item' => ['@microsoft.graph.conflictBehavior' => 'replace']],
        ]);
        $this->assertNotLocked($response, $fileName);
        $session = $response->toArray();
        $uploadUrl = (string) ($session['uploadUrl'] ?? '');
        if ($uploadUrl === '') {
            throw new \RuntimeException("Session de dépôt SharePoint non créée pour $fileName.");
        }

        $handle = fopen($localPath, 'rb');
        if ($handle === false) {
            throw new \RuntimeException("Impossible de lire : $localPath");
        }

        try {
            $offset = 0;
            while ($offset < $size) {
                $chunk = (string) fread($handle, self::CHUNK_SIZE);
                $end = $offset + strlen($chunk) - 1;
                $response = $this->httpClient->request('PUT', $uploadUrl, [
                    'headers' => [
                        'Content-Length' => (string) strlen($chunk),
                        'Content-Range' => sprintf('bytes %d-%d/%d', $offset, $end, $size),
                    ],
                    'body' => $chunk,
                    'timeout' => 120,
                ]);
                $this->assertNotLocked($response, $fileName);
                $status = $response->getStatusCode();
                if ($status !== 200 && $status !== 201 && $status !== 202) {
                    throw new \RuntimeException("Dépôt de $fileName refusé (HTTP $status).");
                }
                $offset = $end + 1;
            }
        } finally {
            fclose($handle);
        }
    }

    /** 409 / 423 → fichier verrouillé ou en conflit, avec le code et le message renvoyés par Graph. */
    private function assertNotLocked(ResponseInterface $response, string $fileName): void
    {
        $status = $response->getStatusCode();
        if ($status !== 409 && $status !== 423) {
            return;
        }

        $error = json_decode($response->getContent(false), true)['error'] ?? [];

        throw new SharePointFileLockedException(sprintf(
            '%s : dépôt refusé (HTTP %d, %s) — le fichier est probablement ouvert ou verrouillé dans SharePoint (Excel, actualisation Power Query, synchronisation). %s',
            $fileName,
            $status,
            $error['code'] ?? 'code inconnu',
            $error['message'] ?? ''
        ));
    }

    /** Encodage « shares » de Graph : "u!" + base64 URL-safe sans padding. */
    private function shareId(string $url): string
    {
        return 'u!' . rtrim(strtr(base64_encode($url), '+/', '-_'), '=');
    }
}
