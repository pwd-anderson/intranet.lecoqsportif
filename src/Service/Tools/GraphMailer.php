<?php

namespace App\Service\Tools;

use Symfony\Component\Mime\Email;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;

class GraphMailer
{
    private string $senderEmail;

    public function __construct(
        private GraphAccessTokenService $tokenService,
        private HttpClientInterface $httpClient,
        private LoggerInterface $logger,
        string $senderEmail,
        string $adminEmail
    ) {
        $this->senderEmail = $senderEmail;
        $this->adminEmail = $adminEmail;
    }

    public function send(Email $email): void
    {
        $token = $this->tokenService->getAccessToken();

        $buildRecipients = fn(array $addresses) => array_map(fn($addr) => [
            'emailAddress' => ['address' => $addr->getAddress()],
        ], $addresses);

        $message = [
            'message' => [
                'subject' => $email->getSubject(),
                'body' => [
                    'contentType' => 'HTML',
                    'content' => $email->getHtmlBody() ?? $email->getTextBody(),
                ],
                'toRecipients' => $buildRecipients($email->getTo()),
                'ccRecipients' => $buildRecipients($email->getCc()),
                'bccRecipients' => $buildRecipients($email->getBcc()),
                'attachments' => [],
            ],
            'saveToSentItems' => true,
        ];

        foreach ($email->getAttachments() as $attachment) {
            $message['message']['attachments'][] = [
                '@odata.type' => '#microsoft.graph.fileAttachment',
                'name' => $attachment->getFilename(),
                'contentBytes' => base64_encode($attachment->getBody()),
                'contentType' => $attachment->getContentType(),
            ];
        }

        try {
            $response = $this->httpClient->request('POST',
                "https://graph.microsoft.com/v1.0/users/{$this->senderEmail}/sendMail", [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $token,
                        'Content-Type' => 'application/json',
                    ],
                    'json' => $message,
                ]
            );

            $statusCode = $response->getStatusCode();

            if ($statusCode !== 202) {
                throw new \RuntimeException("Microsoft Graph mail failed with code $statusCode");
            }

        } catch (\Throwable $e) {
            $this->logger->error('GraphMailer: erreur envoi e-mail', [
                'error' => $e->getMessage(),
            ]);
            throw new \RuntimeException('❌ Envoi via Microsoft Graph échoué.');
        }
    }

    public function notifyError(string $subject, \Throwable $e, ?string $to = null): void
    {
        $email = (new Email())
            ->to($to ?? $this->adminEmail)
            ->subject($subject)
            ->html('<p><strong>Erreur :</strong> '.$e->getMessage().'</p><pre>'.$e->getTraceAsString().'</pre>');

        $this->send($email);
    }
}