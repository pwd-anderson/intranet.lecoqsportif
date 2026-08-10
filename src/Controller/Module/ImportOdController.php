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
            'listesUrl'   => $this->generateUrl('divers_import_od_listes_json'),
            'generateUrl' => $this->generateUrl('app_module_import_od_generate'),
        ]);
    }

    #[Route('/module/import_od/validate-lignes', name: 'app_module_import_od_validate_lignes', methods: ['POST'])]
    public function validateLignes(Request $request, ImportOdService $service): JsonResponse
    {
        $body   = json_decode($request->getContent(), true);
        $lignes = $body['lignes'] ?? [];

        if (empty($lignes)) {
            return new JsonResponse(['success' => false, 'message' => 'Aucune ligne à valider.'], 400);
        }

        $errors = $service->validateLignes($lignes);

        return new JsonResponse([
            'success' => empty($errors),
            'errors'  => $errors,
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

        $result = $service->generate($entete, $lignes);

        return new JsonResponse([
            'success'       => true,
            'erpDocumentId' => $result['erpDocumentId'],
            'message'       => $result['message'],
            'nbLignes'      => count($lignes),
        ]);
    }
}
