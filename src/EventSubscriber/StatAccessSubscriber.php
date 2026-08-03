<?php

namespace App\EventSubscriber;

use App\Entity\User;
use App\Repository\UserStatExclusionRepository;
use App\Service\StatRegistry;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\RouterInterface;

final class StatAccessSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private Security $security,
        private UserStatExclusionRepository $repo,
        private RouterInterface $router,
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::REQUEST => ['onKernelRequest', 10]];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $route = $event->getRequest()->attributes->get('_route');
        if (!$route) {
            return;
        }

        $allKeys = array_keys(StatRegistry::all());
        // Pour les routes stat standards + app_dashboard_sellout
        if (!in_array($route, $allKeys, true)) {
            return;
        }

        $user = $this->security->getUser();
        if (!$user instanceof User) {
            return;
        }

        $exclusions = $this->repo->findExcludedStatKeysForUser($user);
        if (in_array($route, $exclusions, true)) {
            $event->getRequest()->getSession()->getFlashBag()->add('warning', 'Vous n\'avez pas accès à cette statistique.');
            $event->setResponse(new RedirectResponse($this->router->generate('app_home')));
        }
    }
}
