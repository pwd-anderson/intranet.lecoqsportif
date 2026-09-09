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

    /**
     * Dernier recours pour l'affichage à l'écran, utilisé uniquement quand la
     * cascade de noms de fichiers devinés (_2.webp, _1.jpg, etc.) a échoué côté
     * front. Interroge la recherche Shopify pour retrouver la vraie URL de
     * l'image (gère notamment les fichiers uploadés avec un suffixe UUID).
     */
    #[Route(
        '/images/lecoqsportif_lookup/{article}',
        name: 'lecoqsportif_image_lookup',
        methods: ['GET']
    )]
    public function lecoqsportifLookup(
        string $article,
        LecoqsportifImageFetcher $fetcher
    ): JsonResponse {
        $url = $fetcher->lookupImageUrl($article);

        if ($url === null) {
            return new JsonResponse(['success' => false]);
        }

        return new JsonResponse(['success' => true, 'url' => $url]);
    }
}
