<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Force Symfony a utiliser le nom d'hote public du tunnel de dev (devtunnel,
 * ngrok...) pour toute generation d'URL absolue (redirections, callback OAuth
 * Azure AD...).
 *
 * `devtunnel host` ne transmet aucun en-tete X-Forwarded-Host/-Proto a l'appli
 * locale : Symfony recoit `Host: localhost:8000` quelle que soit l'URL publique
 * utilisee, cassant toutes les redirections absolues (dont le callback Azure).
 * Actif uniquement si DEV_TUNNEL_PUBLIC_HOST est renseigne (jamais en
 * preprod/prod — la variable n'existe pas sur ces environnements).
 *
 * Doit tourner AVANT le RouterListener (priorite 32) qui construit le
 * RequestContext a partir de ces en-tetes : priorite elevee ici.
 */
final class DevTunnelHostSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private ?string $devTunnelPublicHost,
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::REQUEST => ['onKernelRequest', 1000]];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$this->devTunnelPublicHost || !$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $request->headers->set('X-Forwarded-Host', $this->devTunnelPublicHost);
        $request->headers->set('X-Forwarded-Proto', 'https');
    }
}
