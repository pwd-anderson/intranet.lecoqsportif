<?php

namespace App\Controller;

use App\Entity\SuiviSellinNote;
use App\Entity\User;
use App\Repository\SuiviSellinNoteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Identité de l'utilisateur et notes de suivi client du module Suivi Sellin global. L'application (JavaScript du
 * template) les lisait dans l'outil Claude où le tableau de bord a été créé ; elle les lit ici, via un petit adaptateur
 * (voir « intégration intranet » en fin de suivi.js). Mêmes droits d'accès que le module ; écritures protégées par le
 * jeton CSRF (en-tête X-CSRF-Token).
 */
final class SuiviSellinNotesController extends AbstractController
{
    public const CSRF_ID = 'suivi_sellin';

    #[Route('/suivi-sellin/me', name: 'app_suivi_sellin_me', methods: ['GET'])]
    public function me(): JsonResponse
    {
        $this->denyUnlessAccess();
        $user = $this->currentUser();

        return new JsonResponse(['id' => (string) $user->getId(), 'name' => $this->displayName($user)]);
    }

    #[Route('/suivi-sellin/notes', name: 'app_suivi_sellin_notes', methods: ['GET'])]
    public function list(SuiviSellinNoteRepository $notes): JsonResponse
    {
        $this->denyUnlessAccess();

        return new JsonResponse(array_map(static fn (SuiviSellinNote $n): array => [
            'id'         => (string) $n->getId(),
            'c'          => $n->getCustomerCode(),
            'text'       => $n->getText(),
            'author'     => (string) $n->getAuthorId(),
            'authorName' => $n->getAuthorName(),
            'at'         => $n->getCreatedAt()->format(\DATE_ATOM),
        ], $notes->findAllRecentFirst()));
    }

    #[Route('/suivi-sellin/notes', name: 'app_suivi_sellin_notes_add', methods: ['POST'])]
    public function add(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $this->denyUnlessAccess();
        $this->checkCsrf($request);

        $body = json_decode($request->getContent(), true);
        $code = is_array($body) ? trim((string) ($body['c'] ?? '')) : '';
        $text = is_array($body) ? trim((string) ($body['text'] ?? '')) : '';
        if ($code === '' || mb_strlen($code) > 30 || $text === '') {
            return new JsonResponse(['error' => 'Note ou client manquant.'], 422);
        }

        $user = $this->currentUser();
        $note = (new SuiviSellinNote())
            ->setCustomerCode($code)
            ->setText(mb_substr($text, 0, 2000))
            ->setAuthorId((int) $user->getId())
            ->setAuthorName($this->displayName($user));
        $em->persist($note);
        $em->flush();

        return new JsonResponse(['id' => (string) $note->getId()], 201);
    }

    #[Route('/suivi-sellin/notes/{id}', name: 'app_suivi_sellin_notes_delete', requirements: ['id' => '\d+'], methods: ['DELETE'])]
    public function delete(int $id, Request $request, SuiviSellinNoteRepository $notes, EntityManagerInterface $em): JsonResponse
    {
        $this->denyUnlessAccess();
        $this->checkCsrf($request);

        $note = $notes->find($id);
        if ($note === null) {
            return new JsonResponse(['error' => 'Note introuvable.'], 404);
        }
        // Chacun supprime ses propres notes (l'administrateur peut tout supprimer).
        if ($note->getAuthorId() !== (int) $this->currentUser()->getId() && !$this->isGranted('ROLE_ADMIN')) {
            return new JsonResponse(['error' => 'Vous ne pouvez supprimer que vos notes.'], 403);
        }
        $em->remove($note);
        $em->flush();

        return new JsonResponse(['ok' => true]);
    }

    private function currentUser(): User
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        return $user;
    }

    private function displayName(User $user): string
    {
        $name = trim(($user->getFirstname() ?? '') . ' ' . ($user->getLastname() ?? ''));

        return $name !== '' ? $name : (string) $user->getEmail();
    }

    private function checkCsrf(Request $request): void
    {
        if (!$this->isCsrfTokenValid(self::CSRF_ID, (string) $request->headers->get('X-CSRF-Token'))) {
            throw new \Symfony\Component\HttpKernel\Exception\HttpException(419, 'Jeton CSRF invalide.');
        }
    }

    private function denyUnlessAccess(): void
    {
        foreach (['ROLE_SALES', 'ROLE_ADV', 'ROLE_MANAGEMENT', 'ROLE_CONTROLLING', 'ROLE_ADMIN'] as $role) {
            if ($this->isGranted($role)) {
                return;
            }
        }

        throw $this->createAccessDeniedException();
    }
}
