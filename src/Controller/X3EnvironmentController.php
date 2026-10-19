<?php

namespace App\Controller;

use App\Service\X3\X3Environment;
use App\Service\X3\X3EnvironmentProvider;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/** Choix de l'environnement X3 (module OC). Réservé à APP_ENV=dev : ailleurs la route répond 404. */
final class X3EnvironmentController extends AbstractController
{
    #[Route('/x3-environment', name: 'app_x3_environment', methods: ['POST'])]
    public function switch(Request $request, X3EnvironmentProvider $provider): Response
    {
        if (!$provider->isSwitchable()) {
            throw $this->createNotFoundException();
        }
        if (!$this->isCsrfTokenValid('x3_environment', (string) $request->request->get('_token'))) {
            return new Response('Jeton CSRF invalide.', Response::HTTP_FORBIDDEN);
        }
        $environment = X3Environment::tryFrom((string) $request->request->get('environment'))
            ?? throw $this->createNotFoundException();
        $provider->set($environment);

        // Retour à la page d'origine, uniquement vers l'intranet (jamais vers une adresse externe).
        $referer = (string) $request->headers->get('referer', '');
        $host = $request->getSchemeAndHttpHost();
        $target = str_starts_with($referer, $host . '/') ? $referer : '/';

        return new RedirectResponse($target);
    }
}
