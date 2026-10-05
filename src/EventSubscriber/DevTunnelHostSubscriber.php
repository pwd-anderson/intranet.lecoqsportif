<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Force Symfony a utiliser le nom d'hote public du tunnel de dev (devtunnel, ngrok...)
 * pour toute generation d'URL absolue (redirections, callback OAuth Azure AD...).
 *
 * `devtunnel host` reecrit l'en-tete Host en `localhost:8000` avant de transmettre la requete
 * a l'appli, et envoie le vrai hote dans X-Forwarded-Host. Symfony ne tient compte de ces
 * en-tetes que si l'adresse source (REMOTE_ADDR) figure dans les proxys de confiance : or
 * devtunnel se connecte tantot en IPv4 (127.0.0.1), tantot en IPv6 (::1), et la liste de
 * confiance ne couvre pas l'IPv6. Resultat : une requete sur deux repartait en
 * https://localhost:8000/... (ERR_SSL_PROTOCOL_ERROR au retour du login Azure).
 *
 * On reecrit donc directement hote, port et schema de la requete, sans dependre de la
 * confiance accordee a l'adresse source. Actif uniquement si DEV_TUNNEL_PUBLIC_HOST est
 * renseigne (jamais en preprod/prod — la variable n'existe pas sur ces environnements).
 *
 * Doit tourner AVANT le RouterListener (priorite 32) qui construit le RequestContext :
 * priorite elevee ici.
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

        $request->headers->set('Host', $this->devTunnelPublicHost);
        $request->server->set('HTTP_HOST', $this->devTunnelPublicHost);
        $request->server->set('SERVER_PORT', '443');
        $request->server->set('HTTPS', 'on');

        $request->headers->set('X-Forwarded-Host', $this->devTunnelPublicHost);
        $request->headers->set('X-Forwarded-Proto', 'https');
        $request->headers->set('X-Forwarded-Port', '443');
    }
}
