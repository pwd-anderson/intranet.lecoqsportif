<?php

namespace App\Controller;

use App\Service\Tools\LecoqsportifImageFetcher;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class ImagesController extends AbstractController
{
    #[Route(
        '/images/lecoqsportif_base64/{article}',
        name: 'lecoqsportif_image_base64',
        methods: ['GET']
    )]
    public function lecoqsportifBase64(
        string $article,
        LecoqsportifImageFetcher $fetcher
    ): JsonResponse {
        // Permet d'arrêter l'exécution si le client se déconnecte (export Excel annulé)
        ignore_user_abort(false);

        $result = $fetcher->fetchAsBase64($article);

        if ($result === null) {
            return new JsonResponse(['success' => false]);
        }

        return new JsonResponse([
            'success'   => true,
            'base64'    => $result['base64'],
            'imageType' => $result['imageType'],   // pour excess_for_sales
            'extension' => $result['imageType'],   // pour best_demand_per_style + stock_produits
        ]);
    }
}
