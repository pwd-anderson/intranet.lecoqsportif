<?php

namespace App\Controller;

use App\Repository\AggridOptionRepository;
use App\Service\AgGrid\AgGridColumnBuilder;
use App\Service\Sales;
use App\Service\Tools\Helpers;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class SalesController extends AbstractController
{
    public function __construct(
        private AggridOptionRepository $aggridOptionRepository,
        private AgGridColumnBuilder $columnBuilder,
    ) {}

    /*
    |--------------------------------------------------------------------------
    |  ROUTES ALIAS (comme StockController)
    |--------------------------------------------------------------------------
    */

    #[Route('/sales/livraison_non_facturees', name: 'app_sales_livraison_non_facturees')]
    public function livraisonAlias(): Response
    {
        return $this->salesGeneric('livraison_non_facturees');
    }

    #[Route('/sales/backlog_clients', name: 'app_sales_backlog_clients')]
    public function backlogAlias(): Response
    {
        return $this->salesGeneric('backlog_clients');
    }

    #[Route('/sales/commandes_a_facturer', name: 'app_sales_commandes_a_facturer')]
    public function commandesAFacturerAlias(): Response
    {
        return $this->salesGeneric('commandes_a_facturer');
    }

    /*
    |--------------------------------------------------------------------------
    |  ROUTES JSON (inchangées)
    |--------------------------------------------------------------------------
    */

    #[Route('/sales/livraison_non_facturees_json', name: 'livraison_non_facturees_json')]
    public function livraisonNonFactureesJson(Sales $sales, Helpers $helpers): JsonResponse
    {
        $data = $sales->getLivraisonsNonFacturees();
        return new JsonResponse($helpers->convertArrayToUtf8($data));
    }

    #[Route('/sales/backlog_clients_json', name: 'backlog_clients_json')]
    public function backlogClientsJson(Sales $sales, Helpers $helpers): JsonResponse
    {
        $data = $sales->getBacklogClients();
        return new JsonResponse($helpers->convertArrayToUtf8($data));
    }

    #[Route('/sales/commandes_a_facturer_json', name: 'commandes_a_facturer_json')]
    public function commandesAFacturerJson(Sales $sales, Helpers $helpers): JsonResponse
    {
        $data = $sales->getCommandesAFacturer();
        return new JsonResponse($helpers->convertArrayToUtf8($data));
    }

    /*
    |--------------------------------------------------------------------------
    |  ROUTE GENERIQUE
    |--------------------------------------------------------------------------
    */

    #[Route(
        '/sales/{type}',
        name: 'app_sales_generic',
        requirements: ['type' => 'livraison_non_facturees|backlog_clients|commandes_a_facturer']
    )]
    public function salesGeneric(string $type): Response
    {
        $config = [
            'livraison_non_facturees' => [
                'gridName'  => 'liv_non_facturees_grid',
                'title'     => 'Livraisons non facturées',
                'jsonRoute' => 'livraison_non_facturees_json',
                'template'  => 'sales/sales_generic.html.twig',
            ],
            'backlog_clients' => [
                'gridName'  => 'backlog_clients_grid',
                'title'     => 'Backlog clients',
                'jsonRoute' => 'backlog_clients_json',
                'template'  => 'sales/sales_generic.html.twig',
            ],
            'commandes_a_facturer' => [
                'gridName'  => 'commandes_a_facturer_grid',
                'title'     => 'Commandes à Facturer (Vue Balance âgée)',
                'jsonRoute' => 'commandes_a_facturer_json',
                'template'  => 'sales/sales_generic.html.twig',
            ],
        ];

        if (!isset($config[$type])) {
            throw $this->createNotFoundException(sprintf('Unknown sales type "%s"', $type));
        }

        $gridConfig = $config[$type];

        $agridOptions = $this->aggridOptionRepository->findBy(
            ['gridName' => $gridConfig['gridName']],
            ['orderIndex' => 'ASC']
        );

        $grid = $this->columnBuilder->build($agridOptions);

        return $this->render($gridConfig['template'], [
            'title'          => $gridConfig['title'],
            'columns'        => $grid['columns'],
            'numericColumns' => $grid['numericColumns'],
            'integerColumns' => $grid['integerColumns'],
            'totalColumns'   => $grid['totalColumns'],
            'dataUrl'        => $this->generateUrl($gridConfig['jsonRoute']),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    |  SELL OUT (pas AgGrid)
    |--------------------------------------------------------------------------
    */

    #[Route('/sales/sell_out', name: 'app_sales_sell_out')]
    public function sellOut(): Response
    {
        return $this->render('sales/sell_out.html.twig');
    }
}
