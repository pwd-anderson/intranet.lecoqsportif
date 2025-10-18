<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class KpiController extends AbstractController
{
    #[Route('/kpi', name: 'app_kpi')]
    public function index(): Response
    {
        return $this->render('kpi/index.html.twig', [
            'controller_name' => 'KpiController',
        ]);
    }
}
