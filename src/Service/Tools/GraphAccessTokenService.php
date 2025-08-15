<?php

namespace App\Service\Tools;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;

class GraphAccessTokenService
{
    private string $tokenEndpoint;
    private string $clientId;
    private string $clientSecret;
    private string $scope;

    public function __construct(
        private HttpClientInterface $httpClient,
        private LoggerInterface $logger,
        string $tokenEndpoint,
        string $clientId,
        string $clientSecret,
        string $scope = 'https://graph.microsoft.com/.default'
    ) {
        $this->tokenEndpoint = $tokenEndpoint;
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->scope = $scope;
    }

    public function getAccessToken(): string
    {
        try {
            $response = $this->httpClient->request('POST', $this->tokenEndpoint, [
                'body' => [
                    'client_id' => $this->clientId,
                    'client_secret' => $this->clientSecret,
                    'scope' => $this->scope,
                    'grant_type' => 'client_credentials',
                ],
                'headers' => [
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ],
            ]);

            $data = $response->toArray();

            if (!isset($data['access_token'])) {
                throw new \RuntimeException('Access token missing in response.');
            }

            return $data['access_token'];
        } catch (\Throwable $e) {
            $this->logger->error('GraphAccessTokenService: erreur token', [
                'exception' => $e->getMessage(),
            ]);
            throw new \RuntimeException('❌ Impossible de récupérer le token Microsoft Graph.');
        }
    }
}