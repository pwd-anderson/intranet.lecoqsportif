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

    // Routes JSON
    #[Route('/stock/stock_a_terme_json', name: 'stock_a_terme_json')]
    public function stockAtermeJson(Stock $stock, Helpers $helpers): JsonResponse
    {
        $data = $stock->getStockATerme();
        $dataUtf8 = $helpers->convertArrayToUtf8($data);

        return new JsonResponse($dataUtf8);
    }

    #[Route('/stock/stock_allocation_json', name: 'stock_allocation_json')]
    public function stockAllocationJson(Stock $stock, Helpers $helpers): JsonResponse
    {
        $data = $stock->getStockAllocation();
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

    // Appel divers
    #[Route('/stock/familles_json', name: 'stock_familles_json')]
    public function stockFamillesJson(Divers $divers): JsonResponse
    {
        return new JsonResponse($divers->getFamilles());
    }

    #[Route(
        '/stock/{type}',
        name: 'app_stock_generic',
        requirements: ['type' => 'stock_a_terme|stock_allocation|stock_composant']
    )]
    public function stockGeneric(string $type): Response
    {
        $config = [
            'stock_a_terme' => [
                'gridName' => 'stock_a_terme_grid',
                'title' => 'Stock à terme',
                'jsonRoute' => 'stock_a_terme_json',
            ],
            'stock_allocation' => [
                'gridName' => 'stock_allocation_grid',
                'title' => 'Stock allocation',
                'jsonRoute' => 'stock_allocation_json',
            ],
            'stock_composant' => [
                'gridName' => 'stock_composant_grid',
                'title' => 'Stock Composant',
                'jsonRoute' => 'stock_composant_json',
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

        return $this->render('stock/stock_generic.html.twig', [
            'title' => $gridConfig['title'],
            'columns' => $grid['columns'],
            'numericColumns' => $grid['numericColumns'],
            'integerColumns' => $grid['integerColumns'],
            'totalColumns' => $grid['totalColumns'],
            'dataUrl' => $this->generateUrl($gridConfig['jsonRoute']),
            'type' => $type,
        ]);
    }
}
