<?php

namespace App\Controller;

use App\Service\Divers;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class DiversController extends AbstractController
{
    #[Route('/divers/collections_json', name: 'divers_collections_json')]
    public function collections(Divers $divers): JsonResponse
    {
        return new JsonResponse($divers->getCollections());
    }

    #[Route('/divers/familles_json', name: 'divers_familles_json')]
    public function familles(Divers $divers): JsonResponse
    {
        return new JsonResponse($divers->getFamilles());
    }

    #[Route('/divers/customers_json', name: 'divers_customers_json')]
    public function customers(Divers $divers): JsonResponse
    {
        return new JsonResponse($divers->getCustomers());
    }

    #[Route('/divers/societes_json', name: 'divers_societes_json')]
    public function societes(Divers $divers): JsonResponse
    {
        return new JsonResponse($divers->getSocietes());
    }

    #[Route('/divers/types_accent_json', name: 'divers_types_accent_json')]
    public function typesAccent(Divers $divers): JsonResponse
    {
        return new JsonResponse($divers->getTypesAccent());
    }

    #[Route('/divers/journaux_json', name: 'divers_journaux_json')]
    public function journaux(Divers $divers): JsonResponse
    {
        return new JsonResponse($divers->getJournaux());
    }

    #[Route('/divers/devises_json', name: 'divers_devises_json')]
    public function devises(Divers $divers): JsonResponse
    {
        return new JsonResponse($divers->getDevises());
    }

    #[Route('/divers/import_od_listes_json', name: 'divers_import_od_listes_json')]
    public function importOdListes(Divers $divers): JsonResponse
    {
        return new JsonResponse([
            'societes' => $divers->getSocietes(),
            'sites'    => $divers->getSites(),
            'types'    => $divers->getTypesAccent(),
            'journaux' => $divers->getJournaux(),
            'devises'  => $divers->getDevises(),
        ]);
    }
}
