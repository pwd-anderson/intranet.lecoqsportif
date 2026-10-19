<?php

namespace App\Service\X3;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Environnement X3 choisi par l'utilisateur (module OC), mémorisé en session.
 *
 * Le choix n'existe qu'en APP_ENV=dev : partout ailleurs (préprod, prod, test, ligne de commande) l'environnement
 * est TOUJOURS PROD, même si une valeur traîne en session.
 */
class X3EnvironmentProvider
{
    private const SESSION_KEY = 'x3_environment';

    public function __construct(
        private readonly RequestStack $requestStack,
        #[Autowire('%kernel.environment%')]
        private readonly string $appEnv,
    ) {
    }

    public function isSwitchable(): bool
    {
        return $this->appEnv === 'dev';
    }

    public function current(): X3Environment
    {
        if (!$this->isSwitchable()) {
            return X3Environment::PROD;
        }

        try {
            $value = $this->requestStack->getSession()->get(self::SESSION_KEY);
        } catch (\Throwable) {
            return X3Environment::PROD; // pas de session (commande, requête sans session)
        }

        return (is_string($value) ? X3Environment::tryFrom($value) : null) ?? X3Environment::PROD;
    }

    /** @throws \LogicException hors APP_ENV=dev */
    public function set(X3Environment $environment): void
    {
        if (!$this->isSwitchable()) {
            throw new \LogicException('Le choix de l\'environnement X3 n\'est possible qu\'en environnement dev.');
        }
        $this->requestStack->getSession()->set(self::SESSION_KEY, $environment->value);
    }
}
