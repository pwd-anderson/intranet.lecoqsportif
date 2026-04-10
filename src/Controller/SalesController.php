<?php

namespace App\Controller;

use App\Repository\AggridOptionRepository;
use App\Service\AgGrid\AgGridColumnBuilder;
use App\Service\Sales;
use App\Service\Tools\Helpers;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class SalesController extends AbstractController
{
    public function __construct(
        private AggridOptionRepository $aggridOptionRepository,
        private AgGridColumnBuilder $columnBuilder,
        private HttpClientInterface $httpClient,
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

    #[Route('/sales/commandes_a_facturer_x3', name: 'app_sales_commandes_a_facturer_x3')]
    public function commandesAFacturerX3Alias(): Response
    {
        return $this->salesGeneric('commandes_a_facturer_x3');
    }

    // ################## Action customisés #####################
    #[Route('/sales/reassort', name: 'app_sales_reassort')]
    public function reassort(): Response
    {
        return $this->render('sales/reassort.html.twig', [
            'title' => 'Réassort Magasin',
            'dataUrl' => $this->generateUrl('sales_reassort_json'),
        ]);
    }

    #[Route('/sales/excess_for_sales', name: 'app_sales_excess_for_sales')]
    public function excessForSales(): Response
    {
        return $this->render('sales/excess_for_sales.html.twig', [
            'title' => 'Excess for Sales',
            'dataUrl' => $this->generateUrl('sales_excess_for_sales_json'),
            'tariffGroupsUrl' => $this->generateUrl('sales_excess_for_sales_tariff_groups_json'),
        ]);
    }

    // ################## ROUTES JSON (inchangées) #####################
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

    #[Route('/sales/reassort_json', name: 'sales_reassort_json')]
    public function reassortJson(Sales $sales, Helpers $helpers): JsonResponse
    {
        $data = $sales->getReassort();

        return new JsonResponse($helpers->convertArrayToUtf8($data));
    }

    #[Route('/sales/commandes_a_facturer_x3_json', name: 'commandes_a_facturer_x3_json')]
    public function commandesAFacturerX3Json(Sales $sales, Helpers $helpers): JsonResponse
    {
        $data = $sales->getCommandesAFacturerX3();
        return new JsonResponse($helpers->convertArrayToUtf8($data));
    }

    #[Route('/sales/excess_for_sales_json', name: 'sales_excess_for_sales_json')]
    public function excessForSalesJson(Request $request, Sales $sales, Helpers $helpers): JsonResponse
    {
        $tariffGroup = $request->query->get('tariffGroup');
        $family = $request->query->get('family');

        $data = $sales->getExcessForSales($tariffGroup, $family);

        return new JsonResponse([
            'variants' => $data['variants'],
            'rows' => $helpers->convertArrayToUtf8($data['rows']),
        ]);
    }

    #[Route('/sales/excess_for_sales_tariff_groups_json', name: 'sales_excess_for_sales_tariff_groups_json')]
    public function excessForSalesTariffGroupsJson(Sales $sales, Helpers $helpers): JsonResponse
    {
        $data = $sales->getExcessForSalesTariffGroups();

        return new JsonResponse($helpers->convertArrayToUtf8($data));
    }

    ####################### Route Divers ####################
    #[Route('/sales/excess_for_sales_image/{article}', name: 'sales_excess_for_sales_image', methods: ['GET'])]
    public function excessForSalesImage(string $article): Response
    {
        $article = preg_replace('/[^A-Za-z0-9_-]/', '', $article);

        if (!$article) {
            return new Response('', Response::HTTP_NOT_FOUND);
        }

        $remoteUrls = [
            sprintf('http://www.lecoqbiz.com/CMS/Images/Medium/%s.jpg', $article),
            sprintf('http://www.lecoqbiz.com/CMS/Images/Small/%s.jpg', $article),
            sprintf('http://www.lecoqbiz.com/CMS/Images/Medium/%s.png', $article),
            sprintf('http://www.lecoqbiz.com/CMS/Images/Small/%s.png', $article),
        ];

        foreach ($remoteUrls as $remoteUrl) {
            try {
                $response = $this->httpClient->request('GET', $remoteUrl);

                if ($response->getStatusCode() !== 200) {
                    continue;
                }

                $headers = $response->getHeaders(false);
                $contentType = $headers['content-type'][0] ?? 'image/jpeg';
                $content = $response->getContent();

                return new Response($content, Response::HTTP_OK, [
                    'Content-Type' => $contentType,
                    'Cache-Control' => 'public, max-age=86400',
                ]);
            } catch (\Throwable $e) {
                continue;
            }
        }

        return new Response('', Response::HTTP_NOT_FOUND);
    }

    #[Route('/sales/excess_for_sales_image_base64/{article}', name: 'sales_excess_for_sales_image_base64', methods: ['GET'])]
    public function excessForSalesImageBase64(string $article): JsonResponse
    {
        $article = preg_replace('/[^A-Za-z0-9_-]/', '', $article);

        if (!$article) {
            return new JsonResponse([
                'success' => false,
            ], Response::HTTP_NOT_FOUND);
        }

        $remoteUrls = [
            sprintf('http://www.lecoqbiz.com/CMS/Images/Small/%s.jpg', $article),
            sprintf('http://www.lecoqbiz.com/CMS/Images/Small/%s.jpg', $article),
            sprintf('http://www.lecoqbiz.com/CMS/Images/Small/%s.png', $article),
            sprintf('http://www.lecoqbiz.com/CMS/Images/Small/%s.png', $article),
        ];

        foreach ($remoteUrls as $remoteUrl) {
            try {
                $response = $this->httpClient->request('GET', $remoteUrl);

                if ($response->getStatusCode() !== 200) {
                    continue;
                }

                $headers = $response->getHeaders(false);
                $contentType = $headers['content-type'][0] ?? 'image/jpeg';
                $content = $response->getContent();
                // On enlève tout risque de chunking ou de retour à la ligne
                $b64 = base64_encode($content);
                $b64 = str_replace(["\r", "\n", "\t"], '', $b64);

                $imageType = 'jpg';
                if (str_contains($contentType, 'png')) {
                    $imageType = 'png';
                } elseif (str_contains($contentType, 'gif')) {
                    $imageType = 'gif';
                }
                return new JsonResponse([
                    'success' => true,
                    'imageType' => 'jpg',
                    'base64' => $b64,
                ]);

            } catch (\Throwable $e) {
                continue;
            }
        }

        return new JsonResponse([
            'success' => false,
        ], Response::HTTP_NOT_FOUND);
    }

    /*
    |--------------------------------------------------------------------------
    |  ROUTE GENERIQUE
    |--------------------------------------------------------------------------
    */

    #[Route(
        '/sales/{type}',
        name: 'app_sales_generic',
        requirements: ['type' => 'livraison_non_facturees|backlog_clients|commandes_a_facturer|livraison_non_facturees_x3']
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
            'commandes_a_facturer_x3' => [
                'gridName'  => 'commandes_a_facturer_x3_grid',
                'title'     => 'Commandes à Facturer (Vue Balance âgée)',
                'jsonRoute' => 'commandes_a_facturer_x3_json',
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
            'type' => $type,
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
