<?php

namespace App\Twig;

use App\Entity\User;
use App\Repository\UserStatExclusionRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class StatAccessExtension extends AbstractExtension
{
    private ?array $exclusions = null;

    public function __construct(
        private UserStatExclusionRepository $repo,
        private Security $security,
    ) {}

    public function getFunctions(): array
    {
        return [
            new TwigFunction('is_stat_excluded', $this->isStatExcluded(...)),
        ];
    }

    public function isStatExcluded(string $statKey): bool
    {
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            return false;
        }

        if ($this->exclusions === null) {
            $this->exclusions = $this->repo->findExcludedStatKeysForUser($user);
        }

        return in_array($statKey, $this->exclusions, true);
    }
}
