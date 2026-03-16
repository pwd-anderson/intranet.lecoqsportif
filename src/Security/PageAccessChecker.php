<?php

namespace App\Security;

use App\Entity\User;
use App\Repository\IntranetPageRepository;

class PageAccessChecker
{
    public function __construct(
        private IntranetPageRepository $intranetPageRepository
    ) {
    }

    public function canAccess(?User $user, string $routeName): bool
    {
        if (!$user) {
            return false;
        }

        if (in_array('ROLE_ADMIN', $user->getRoles(), true)) {
            return true;
        }

        $page = $this->intranetPageRepository->findOneBy([
            'routeName' => $routeName,
        ]);

        if (!$page) {
            return false;
        }

        $pageGroups = $page->getAccessGroups();

        if ($pageGroups->isEmpty()) {
            return false;
        }

        foreach ($user->getAccessGroups() as $userGroup) {
            if ($pageGroups->contains($userGroup)) {
                return true;
            }
        }

        return false;
    }
}
