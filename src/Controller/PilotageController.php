<?php

namespace App\Controller;

use App\Service\Pilotage;
use App\Service\Tools\Helpers;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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
    public function backlogClientJson(Request $request, Pilotage $pilotage, Helpers $helpers): Response
    {
        $collections = array_filter((array) $request->query->all('collections'));
        $data = $pilotage->getBacklogClient(array_values($collections));

        return $this->jsonGzip($helpers->convertArrayToUtf8($data), $request);
    }

    #[Route('/pilotage/api/backlog-fournisseur', name: 'app_pilotage_backlog_fournisseur_json', methods: ['GET'])]
    public function backlogFournisseurJson(Request $request, Pilotage $pilotage, Helpers $helpers): Response
    {
        $data = $pilotage->getBacklogFournisseur();

        return $this->jsonGzip($helpers->convertArrayToUtf8($data), $request);
    }

    #[Route('/pilotage/api/stock', name: 'app_pilotage_stock_json', methods: ['GET'])]
    public function stockJson(Request $request, Pilotage $pilotage, Helpers $helpers): Response
    {
        $data = $pilotage->getStock();

        return $this->jsonGzip($helpers->convertArrayToUtf8($data), $request);
    }

    /**
     * Ces routes renvoient des dizaines de milliers de lignes (jusqu'à ~100 Mo de JSON
     * pour le backlog client) — la moulinette Pilotage a besoin du jeu de données complet
     * en mémoire (pas de pagination possible pour une allocation globale). La compression
     * gzip réduit ce payload de 80-90 %, ce qui domine largement le temps de chargement
     * sur un réseau lent (bien plus que le temps de la requête SQL elle-même).
     */
    private function jsonGzip(mixed $data, Request $request): Response
    {
        $json = json_encode($data);
        $acceptsGzip = str_contains((string) $request->headers->get('Accept-Encoding'), 'gzip');

        if ($acceptsGzip && $json !== false) {
            $response = new Response(gzencode($json, 6));
            $response->headers->set('Content-Encoding', 'gzip');
        } else {
            $response = new Response($json !== false ? $json : '[]');
        }

        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }
}
