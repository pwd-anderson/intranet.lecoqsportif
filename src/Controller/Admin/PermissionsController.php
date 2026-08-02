<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Repository\UserStatExclusionRepository;
use App\Service\StatRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class PermissionsController extends AbstractController
{
    private function checkAccess(): void
    {
        if (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_MANAGEMENT')) {
            throw $this->createAccessDeniedException();
        }
    }

    #[Route('/admin/permissions', name: 'admin_permissions')]
    public function index(UserRepository $userRepo): Response
    {
        $this->checkAccess();
        $users = $userRepo->createQueryBuilder('u')
            ->orderBy('u.lastname', 'ASC')
            ->addOrderBy('u.firstname', 'ASC')
            ->getQuery()
            ->getResult();

        return $this->render('admin/permissions.html.twig', [
            'users'   => $users,
            'grouped' => StatRegistry::grouped(),
        ]);
    }

    #[Route('/admin/permissions/user/{id}', name: 'admin_permissions_user', methods: ['GET'])]
    public function getUserData(User $user, UserStatExclusionRepository $repo): JsonResponse
    {
        $this->checkAccess();
        return new JsonResponse([
            'exclusions' => $repo->findExcludedStatKeysForUser($user),
            'roles'      => $user->getRoles(),
        ]);
    }

    #[Route('/admin/permissions/save/{id}', name: 'admin_permissions_save', methods: ['POST'])]
    public function save(User $user, Request $request, UserStatExclusionRepository $repo): JsonResponse
    {
        $this->checkAccess();
        $body = json_decode($request->getContent(), true);
        $exclusions = $body['exclusions'] ?? [];

        // Valider que les clés existent dans le registre
        $validKeys = array_keys(StatRegistry::all());
        $exclusions = array_values(array_intersect($exclusions, $validKeys));

        $repo->setExclusions($user, $exclusions);

        return new JsonResponse(['success' => true, 'count' => count($exclusions)]);
    }
}
