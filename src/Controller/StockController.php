<?php

namespace App\Controller;

use App\Repository\AggridOptionRepository;
use App\Service\AgGrid\AgGridColumnBuilder;
use App\Service\Divers;
use App\Service\Stock;
use App\Service\Tools\Helpers;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class StockController extends AbstractController
{
    public function __construct(
        private AggridOptionRepository $aggridOptionRepository,
        private AgGridColumnBuilder $columnBuilder,
    ) {}

    #[Route('/stock/stock_a_terme', name: 'app_stock_a_terme')]
    public function stockATermeAlias(): Response
    {
        return $this->stockGeneric('stock_a_terme');
    }

    #[Route('/stock/stock_allocation', name: 'app_stock_allocation')]
    public function stockAllocationAlias(): Response
    {
        return $this->stockGeneric('stock_allocation');
    }

    #[Route('/stock/stock_composant', name: 'app_stock_composant')]
    public function stockComposantAlias(): Response
    {
        return $this->stockGeneric('stock_composant');
    }

    #[Route('/stock/stock_collection', name: 'app_stock_collection')]
    public function stockCollection(): Response
    {
        return $this->render('stock/stock_collection.html.twig', [
            'title' => 'Stock par Collection',
            'dataUrl' => $this->generateUrl('stock_collection_json'),
        ]);
    }

    #[Route('/stock/stock_a_terme_segmentation_produits', name: 'app_stock_a_terme_segmentation_produits')]
    public function stockATermeSegmentationProduitAlias(): Response
    {
        return $this->stockGeneric('stock_a_terme_segmentation_produits');
    }

    #[Route('/stock/stock_a_terme_x3', name: 'app_stock_a_terme_x3')]
    public function stockATermeX3Alias(): Response
    {
        return $this->stockGeneric('stock_a_terme_x3');
    }

    #[Route('/stock/stock_produits', name: 'app_stock_produits')]
    public function stockProduitsAlias(): Response
    {
        return $this->stockGeneric('stock_produits');
    }

    #[Route('/stock/stock_produits_shop_non_coches', name: 'app_stock_produits_shop_non_coches')]
    public function stockProduitsShopNonCochesAlias(): Response
    {
        return $this->stockGeneric('stock_produits_shop_non_coches');
    }

    // ################## ROUTES JSON (inchangées) #####################

    #[Route('/stock/stock_a_terme_json', name: 'stock_a_terme_json')]
    public function stockAtermeJson(Stock $stock, Helpers $helpers): JsonResponse
    {
        $data = $stock->getStockATerme();
        $dataUtf8 = $helpers->convertArrayToUtf8($data);

        return new JsonResponse($dataUtf8);
    }

    #[Route('/stock/stock_allocation_json', name: 'stock_allocation_json')]
    public function stockAllocationJson(
        Request $request,
        Stock $stock,
        Helpers $helpers
    ): JsonResponse {
        $siteGroup = $request->query->get('siteGroup');

        $data = $stock->getStockAllocation($siteGroup);
        $dataUtf8 = $helpers->convertArrayToUtf8($data);

        return new JsonResponse($dataUtf8);
    }

    #[Route('/stock/stock_collection_json', name: 'stock_collection_json')]
    public function stockCollectionJson(Request $request, Stock $stock): JsonResponse
    {
        $location = $request->query->get('location');
        $status   = $request->query->get('status');

        $data = $stock->getStockParCollection($location, $status);

        return new JsonResponse($data);
    }

    #[Route('/stock/stock_composant_json', name: 'stock_composant_json')]
    public function stockComposantJson(Request $request, Stock $stock, Helpers $helpers): JsonResponse
    {
        $famille = $request->query->get('famille');

        $data = $stock->getStockComposant($famille);
        $dataUtf8 = $helpers->convertArrayToUtf8($data);

        return new JsonResponse($dataUtf8);
    }

    #[Route('/stock/stock_a_terme_segmentation_produits_json', name: 'stock_a_terme_segmentation_produits_json')]
    public function stockATermeSegmentationProduitJson(Stock $stock, Helpers $helpers): JsonResponse
    {
        $data = $stock->getStockATermeAvecSegmentationProduit();
        $dataUtf8 = $helpers->convertArrayToUtf8($data);

        return new JsonResponse($dataUtf8);
    }

    #[Route('/stock/stock_a_terme_x3_json', name: 'stock_a_terme_x3_json')]
    public function stockAtermeX3Json(Stock $stock, Helpers $helpers): JsonResponse
    {
        $data = $stock->getStockATermeX3();
        $dataUtf8 = $helpers->convertArrayToUtf8($data);

        return new JsonResponse($dataUtf8);
    }

    #[Route('/stock/stock_produits_shop_non_coches_json', name: 'stock_produits_shop_non_coches_json')]
    public function stockProduitsShopNonCochesJson(Stock $stock, Helpers $helpers): JsonResponse
    {
        $data = $stock->getStockProduitsShopifyNonCoches();
        $dataUtf8 = $helpers->convertArrayToUtf8($data);

        return new JsonResponse($dataUtf8);
    }

    // Appel divers
    #[Route('/stock/familles_json', name: 'stock_familles_json')]
    public function stockFamillesJson(Divers $divers): JsonResponse
    {
        return new JsonResponse($divers->getFamilles());
    }

    #[Route('/stock/stock_produits_json', name: 'stock_produits_json')]
    public function stockProduitsJson(Request $request, Stock $stock, Helpers $helpers): JsonResponse
    {
        $collection = $request->query->get('collection');
        $famille    = $request->query->get('famille');
        $genre      = $request->query->get('genre');

        $data = $stock->getStockProduits($collection, $famille, $genre);
        $dataUtf8 = $helpers->convertArrayToUtf8($data);

        return new JsonResponse($dataUtf8);
    }

    ####################### Route Divers ####################

    #[Route('/stock/collections_json', name: 'stock_collections_json')]
    public function stockCollectionsJson(Divers $divers): JsonResponse
    {
        return new JsonResponse($divers->getCollections());
    }

    #[Route('/stock/genres_json', name: 'stock_genres_json')]
    public function stockGenresJson(Divers $divers): JsonResponse
    {
        return new JsonResponse($divers->getGenres());
    }

    #[Route(
        '/stock/{type}',
        name: 'app_stock_generic',
        requirements: ['type' => 'stock_a_terme|stock_allocation|stock_composant|stock_a_terme_segmentation_produits|stock_a_terme_x3|stock_produits|stock_produits_shop_non_coches']
    )]
    public function stockGeneric(string $type): Response
    {
        $config = [
            'stock_a_terme' => [
                'gridName' => 'stock_a_terme_grid',
                'title' => 'Stock à terme',
                'jsonRoute' => 'stock_a_terme_json',
                'gridWidthMode' => 'full',
            ],
            'stock_allocation' => [
                'gridName' => 'stock_allocation_grid',
                'title' => 'Stock allocation',
                'jsonRoute' => 'stock_allocation_json',
                'gridWidthMode' => 'full',
            ],
            'stock_composant' => [
                'gridName' => 'stock_composant_grid',
                'title' => 'Stock par site et famille',
                'jsonRoute' => 'stock_composant_json',
                'gridWidthMode' => 'full',
            ],
            'stock_a_terme_segmentation_produits' => [
                'gridName' => 'stock_a_terme_segmentation_produits_grid',
                'title' => 'Stock à terme Amazon',
                'jsonRoute' => 'stock_a_terme_segmentation_produits_json',
                'gridWidthMode' => 'full',
            ],
            'stock_a_terme_x3' => [
                'gridName' => 'stock_a_terme_x3_grid',
                'title' => 'Stock à terme',
                'jsonRoute' => 'stock_a_terme_x3_json',
                'gridWidthMode' => 'full',
            ],
            'stock_produits' => [
                'gridName'  => 'stock_produits_grid',
                'title'     => 'Produits',
                'jsonRoute' => 'stock_produits_json',
                'gridWidthMode' => 'auto',
            ],
            'stock_produits_shop_non_coches' => [
                'gridName'  => 'stock_produits_shop_non_coches_grid',
                'title'     => 'Produits en stock mais pas sur Shopify',
                'jsonRoute' => 'stock_produits_shop_non_coches_json',
                'gridWidthMode' => 'auto',
            ],
        ];

        if (!isset($config[$type])) {
            throw $this->createNotFoundException(sprintf('Unknown stock type "%s"', $type));
        }

        $gridConfig = $config[$type];

        $agridOptions = $this->aggridOptionRepository->findBy(
            ['gridName' => $gridConfig['gridName']],
            ['orderIndex' => 'ASC']
        );

        $grid = $this->columnBuilder->build($agridOptions);

        // Construction du dataUrl avec valeurs par défaut éventuelles
        $dataUrl = $this->generateUrl($gridConfig['jsonRoute']);

        if ($type === 'stock_produits') {
            $dataUrl .= '?collection=2026-01-SS';
        }

        return $this->render('stock/stock_generic.html.twig', [
            'title' => $gridConfig['title'],
            'columns' => $grid['columns'],
            'numericColumns' => $grid['numericColumns'],
            'integerColumns' => $grid['integerColumns'],
            'totalColumns' => $grid['totalColumns'],
            'dataUrl' => $dataUrl,
            'type' => $type,
            'gridWidthMode' => $gridConfig['gridWidthMode'] ?? 'full',
        ]);
    }
}
