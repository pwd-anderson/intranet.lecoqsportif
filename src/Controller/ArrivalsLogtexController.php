<?php

namespace App\Controller;

use App\Service\ArrivalsLogtex;
use App\Service\Tools\Helpers;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ArrivalsLogtexController extends AbstractController
{
    #[Route('/arrivees-logtex', name: 'app_arrivals_logtex')]
    public function index(): Response
    {
        return $this->render('arrivals_logtex/arrivals_logtex.html.twig');
    }

    #[Route('/arrivees-logtex/api/data', name: 'api_arrivals_logtex_data', methods: ['GET'])]
    public function data(ArrivalsLogtex $service, Helpers $helpers): JsonResponse
    {
        return new JsonResponse($helpers->convertArrayToUtf8($service->getArrivals()));
    }
}
