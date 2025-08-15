<?php

namespace App\Controller;

use League\OAuth2\Client\Provider\GenericProvider;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

final class SecurityController extends AbstractController
{
    #[Route('/connect', name: 'login')]
    public function login(SessionInterface $session): RedirectResponse
    {
        $clientId = $this->getParameter('azure_client_id');
        $clientSecret = $this->getParameter('azure_client_secret');
        $redirectUri = $this->getParameter('azure_redirect_uri');
        $urlAuthorize = $this->getParameter('azure_url_authorize');
        $urlAccessToken = $this->getParameter('azure_url_access_token');
        $urlResourceOwnerDetails = $this->getParameter('azure_url_resource_owner_details');

        // Initialisation du GenericProvider
        $provider = new GenericProvider([
            'clientId'                => $clientId,
            'clientSecret'            => $clientSecret,
            'redirectUri'             => $redirectUri,
            'urlAuthorize'            => $urlAuthorize,
            'urlAccessToken'          => $urlAccessToken,
            'urlResourceOwnerDetails' => $urlResourceOwnerDetails,
            'scopes'                  => 'openid email profile',
        ]);

        $authorizationUrl = $provider->getAuthorizationUrl();
        $session->set('oauth2state', $provider->getState());
        return new RedirectResponse($authorizationUrl);
    }

    #[Route('/callback', name: 'app_callback')]
    public function callback(Request $request, SessionInterface $session): Response
    {
        if ($request->get('state') !== $session->get('oauth2state')) {
            $session->remove('oauth2state');
            return new Response('Invalid state', Response::HTTP_FORBIDDEN);
        }

        $clientId = $this->getParameter('azure_client_id');
        $clientSecret = $this->getParameter('azure_client_secret');
        $redirectUri = $this->getParameter('azure_redirect_uri');
        $urlAuthorize = $this->getParameter('azure_url_authorize');
        $urlAccessToken = $this->getParameter('azure_url_access_token');
        $urlResourceOwnerDetails = $this->getParameter('azure_url_resource_owner_details');

        try {
            // Initialisation du GenericProvider
            // Initialisation du GenericProvider
            $provider = new GenericProvider([
                'clientId'                => $clientId,
                'clientSecret'            => $clientSecret,
                'redirectUri'             => $redirectUri,
                'urlAuthorize'            => $urlAuthorize,
                'urlAccessToken'          => $urlAccessToken,
                'urlResourceOwnerDetails' => $urlResourceOwnerDetails,
                'scopes'                  => 'openid email profile',
            ]);

            $accessToken = $provider->getAccessToken('authorization_code', [
                'code' => $request->get('code'),
            ]);

            $user = $provider->getResourceOwner($accessToken);

            $session->set('access_token', $accessToken->getToken());
            $session->set('user', $user->toArray());

            return $this->redirectToRoute('dashboard');
        } catch (\Throwable $e) {
            return new Response('Erreur : ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/logout', name: 'logout')]
    public function logout(Request $request): Response
    {
        $request->getSession()->invalidate();

        // Rediriger aussi vers Azure logout si tu veux
        $azureLogout = 'https://login.microsoftonline.com/common/oauth2/v2.0/logout';

        return $this->redirect($azureLogout);
    }
}
