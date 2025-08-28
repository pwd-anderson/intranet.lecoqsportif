<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class AchatController extends AbstractController
{
    #[Route('/achat', name: 'app_achat')]
    public function reception(): Response
    {
        return $this->render('achat/reception.html.twig', [
            'controller_name' => 'AchatController',
        ]);
    }
}
