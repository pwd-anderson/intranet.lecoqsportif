<?php

namespace App\Controller\Module;

use App\Service\Module\ImportOdService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ImportOdController extends AbstractController
{
    #[Route('/module/import_od', name: 'app_module_import_od')]
    public function index(): Response
    {
        return $this->render('module/import_od.html.twig', [
            'title'       => 'Import OD',
            'societesUrl' => $this->generateUrl('divers_societes_json'),
            'typesUrl'    => $this->generateUrl('divers_types_accent_json'),
            'journauxUrl' => $this->generateUrl('divers_journaux_json'),
            'devisesUrl'  => $this->generateUrl('divers_devises_json'),
            'generateUrl' => $this->generateUrl('app_module_import_od_generate'),
        ]);
    }

    #[Route('/module/import_od/generate', name: 'app_module_import_od_generate', methods: ['POST'])]
    public function generate(Request $request, ImportOdService $service): JsonResponse
    {
        $body   = json_decode($request->getContent(), true);
        $entete = $body['entete'] ?? [];
        $lignes = $body['lignes'] ?? [];

        if (empty($lignes)) {
            return new JsonResponse(['success' => false, 'message' => 'Aucune ligne à traiter.'], 400);
        }

        $ws = $service->generate($entete, $lignes);

        return new JsonResponse([
            'success' => true,
            'id'      => $ws->getId(),
            'message' => sprintf('%d ligne(s) mise(s) en file d\'attente.', count($lignes)),
        ]);
    }
}
