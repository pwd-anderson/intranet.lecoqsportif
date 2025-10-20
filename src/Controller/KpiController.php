<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class KpiController extends AbstractController
{
    #[Route('/kpi/ecommerce', name: 'app_kpi_ecommerce')]
    public function index(): Response
    {
        return $this->render('kpi/ecommerce.html.twig');
    }
}
