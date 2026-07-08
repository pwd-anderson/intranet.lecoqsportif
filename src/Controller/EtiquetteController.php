<?php

namespace App\Controller;

use App\Service\Etiquette;
use App\Service\Tools\Helpers;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class EtiquetteController extends AbstractController
{
    #[Route('/etiquette/expedition', name: 'app_etiquette_expedition')]
    public function index(): Response
    {
        return $this->render('etiquette/index.html.twig');
    }

    #[Route('/etiquette/expedition/livraison', name: 'app_etiquette_livraison', methods: ['POST'])]
    public function getLivraison(Request $request, Etiquette $etiquette, Helpers $helpers): JsonResponse
    {
        $data    = json_decode($request->getContent(), true);
        $numero  = trim($data['numero'] ?? '');

        if ($numero === '') {
            return $this->json(['error' => 'Numéro de livraison manquant'], 400);
        }

        $rows = $etiquette->getLignesLivraison($numero);

        return $this->json($helpers->convertArrayToUtf8($rows));
    }
}
