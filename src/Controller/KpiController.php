<?php

namespace App\Controller;

use App\Service\Kpi;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class KpiController extends AbstractController
{
    #[Route('/kpi/ecommerce', name: 'app_kpi_ecommerce')]
    public function ecommerce(Kpi $kpi): Response
    {
        $eshop = $kpi->getEshopData();
        $products = $kpi->getTopProducts();
        $commentaires = $kpi->getCommentaires();

        return $this->render('kpi/ecommerce.html.twig', [
            'eshop' => $eshop,
            'products' => $products,
            'commentaires' => $commentaires
        ]);
    }
}
