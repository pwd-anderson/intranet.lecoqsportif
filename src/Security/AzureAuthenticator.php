<?php

namespace App\Security;

use App\Service\Tools\UserManagerAzure;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

class AzureAuthenticator extends AbstractAuthenticator implements AuthenticationEntryPointInterface
{
    private RouterInterface $router;
    private UserManagerAzure $userManager;

    public function __construct(RouterInterface $router, UserManagerAzure $userManager)
    {
        $this->router = $router;
        $this->userManager = $userManager;
    }

    public function supports(Request $request): ?bool
    {
        return preg_match('#^/[a-z]{2}/callback$#', $request->getPathInfo()) &&
            $request->query->has('code');
    }

    public function authenticate(Request $request): SelfValidatingPassport
    {
        $code = $request->query->get('code');

        if (!$code) {
            throw new AuthenticationException('Code d\'autorisation manquant');
        }

        try {
            $accessToken = $this->userManager->fetchAccessToken($code);

            $userDetails = $this->fetchUserDetails($accessToken);

            $userData = [
                'email' => $userDetails['mail'] ?? $userDetails['userPrincipalName'],
                'roles' => $this->userManager->fetchUserRoles($accessToken),
                'firstname' => $userDetails['givenName'] ?? '',
                'lastname' => $userDetails['surname'] ?? '',
            ];

            return new SelfValidatingPassport(
                new UserBadge($userData['email'], function () use ($userData, $accessToken) {
                    return $this->userManager->saveUser($userData, $accessToken);
                })
            );
        } catch (\Throwable $e) {
            throw new AuthenticationException('Erreur d\'authentification Azure : ' . $e->getMessage(), 0, $e);
        }
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return new RedirectResponse('/');
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return new RedirectResponse('/logout');
    }

    private function fetchUserDetails(string $accessToken): array
    {
        $client = new \GuzzleHttp\Client();
        $response = $client->get('https://graph.microsoft.com/v1.0/me', [
            'headers' => [
                'Authorization' => 'Bearer ' . $accessToken,
            ]
        ]);

        return json_decode($response->getBody()->getContents(), true);
    }

    public function start(Request $request, ?AuthenticationException $authException = null): Response
    {
        return new RedirectResponse($this->router->generate('login'));
    }
}
