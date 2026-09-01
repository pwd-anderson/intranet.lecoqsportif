<?php

namespace App\Controller;

use App\Entity\SoaRequest;
use App\Repository\SoaRequestRepository;
use App\Repository\SoaStatusRepository;
use App\Service\SoaX3Service;
use App\Service\Tools\Helpers;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
final class SoaController extends AbstractController
{
    // ── Pages ────────────────────────────────────────────────────────────────

    #[Route('/soa', name: 'app_soa_index')]
    public function index(): Response
    {
        return $this->render('soa/index.html.twig');
    }

    #[Route('/soa/new', name: 'app_soa_new')]
    public function new(SoaRequestRepository $soaRepo): Response
    {
        $numero = $soaRepo->generateNumero();

        return $this->render('soa/new.html.twig', [
            'numero'       => $numero,
            'representant' => $this->getUser()->getUserIdentifier(),
        ]);
    }

    #[Route('/soa/{id}', name: 'app_soa_show', requirements: ['id' => '\d+'])]
    public function show(int $id, SoaRequestRepository $soaRepo): Response
    {
        $soa = $soaRepo->find($id);

        if (!$soa) {
            throw $this->createNotFoundException('SOA introuvable.');
        }

        return $this->render('soa/show.html.twig', ['soa' => $soa]);
    }

    // ── API X3 ───────────────────────────────────────────────────────────────

    #[Route('/soa/api/clients', name: 'api_soa_clients', methods: ['GET'])]
    public function searchClients(Request $request, SoaX3Service $x3, Helpers $helpers): JsonResponse
    {
        $q = trim($request->query->get('q', ''));

        if (mb_strlen($q) < 2) {
            return $this->json([]);
        }

        return $this->json($helpers->convertArrayToUtf8($x3->searchClients($q)));
    }

    #[Route('/soa/api/articles', name: 'api_soa_articles', methods: ['GET'])]
    public function searchArticles(Request $request, SoaX3Service $x3, Helpers $helpers): JsonResponse
    {
        $q = trim($request->query->get('q', ''));

        if (mb_strlen($q) < 2) {
            return $this->json([]);
        }

        return $this->json($helpers->convertArrayToUtf8($x3->searchArticles($q)));
    }

    #[Route('/soa/api/articles/prix', name: 'api_soa_article_prix', methods: ['GET'])]
    public function getArticlePrix(Request $request, SoaX3Service $x3): JsonResponse
    {
        $modele = trim($request->query->get('modele', ''));

        if ($modele === '') {
            return $this->json(['prix' => null]);
        }

        return $this->json(['prix' => $x3->getPrixAchat($modele)]);
    }
}
