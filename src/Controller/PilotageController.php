<?php

namespace App\Controller;

use App\Service\Pilotage;
use App\Service\Tools\Helpers;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class PilotageController extends AbstractController
{
    #[Route('/pilotage/livraisons', name: 'app_pilotage_livraisons')]
    public function index(): Response
    {
        return $this->render('pilotage/pilotage.html.twig');
    }

    #[Route('/pilotage/api/backlog-client', name: 'app_pilotage_backlog_client_json', methods: ['GET'])]
    public function backlogClientJson(Request $request, Pilotage $pilotage, Helpers $helpers): JsonResponse
    {
        $collections = array_filter((array) $request->query->all('collections'));
        $data = $pilotage->getBacklogClient(array_values($collections));

        return new JsonResponse($helpers->convertArrayToUtf8($data));
    }

    #[Route('/pilotage/api/backlog-fournisseur', name: 'app_pilotage_backlog_fournisseur_json', methods: ['GET'])]
    public function backlogFournisseurJson(Pilotage $pilotage, Helpers $helpers): JsonResponse
    {
        $data = $pilotage->getBacklogFournisseur();

        return new JsonResponse($helpers->convertArrayToUtf8($data));
    }

    #[Route('/pilotage/api/stock', name: 'app_pilotage_stock_json', methods: ['GET'])]
    public function stockJson(Pilotage $pilotage, Helpers $helpers): JsonResponse
    {
        $data = $pilotage->getStock();

        return new JsonResponse($helpers->convertArrayToUtf8($data));
    }
}
