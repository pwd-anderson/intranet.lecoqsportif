<?php

namespace App\Controller;

use App\Service\DistributorAvailability;
use App\Service\Tools\Helpers;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class DistributorAvailabilityController extends AbstractController
{
    #[Route('/distributor-availability', name: 'app_distributor_availability')]
    public function index(): \Symfony\Component\HttpFoundation\Response
    {
        return $this->render('distributor_availability/distributor_availability.html.twig');
    }

    /**
     * Volumétrie faible (~27 000 lignes au total sur les 4 sources, <1s chacune) :
     * pas besoin de la compression gzip utilisée par Pilotage Livraisons.
     */
    #[Route('/distributor-availability/api/backlog-client', name: 'api_distributor_availability_backlog_client', methods: ['GET'])]
    public function backlogClientJson(DistributorAvailability $service, Helpers $helpers): JsonResponse
    {
        return new JsonResponse($helpers->convertArrayToUtf8($service->getBacklogClient()));
    }

    #[Route('/distributor-availability/api/france-reserve', name: 'api_distributor_availability_france_reserve', methods: ['GET'])]
    public function franceReserveJson(DistributorAvailability $service, Helpers $helpers): JsonResponse
    {
        return new JsonResponse($helpers->convertArrayToUtf8($service->getFranceReserve()));
    }

    #[Route('/distributor-availability/api/backlog-fournisseur', name: 'api_distributor_availability_backlog_fournisseur', methods: ['GET'])]
    public function backlogFournisseurJson(DistributorAvailability $service, Helpers $helpers): JsonResponse
    {
        return new JsonResponse($helpers->convertArrayToUtf8($service->getBacklogFournisseur()));
    }

    #[Route('/distributor-availability/api/stock', name: 'api_distributor_availability_stock', methods: ['GET'])]
    public function stockJson(DistributorAvailability $service, Helpers $helpers): JsonResponse
    {
        return new JsonResponse($helpers->convertArrayToUtf8($service->getStock()));
    }
}
