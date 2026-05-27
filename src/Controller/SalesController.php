<?php

namespace App\Controller;

use App\Repository\AggridOptionRepository;
use App\Service\AgGrid\AgGridColumnBuilder;
use App\Service\Divers;
use App\Service\Sales;
use App\Service\Tools\Helpers;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use App\Service\AgGrid\Ssrm\SsrmRequest;

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

    #[Route('/sales/backlog_clients_x3', name: 'app_sales_backlog_clients_x3')]
    public function backlogClientsX3Alias(): Response
    {
        return $this->salesGeneric('backlog_clients_x3');
    }

    #[Route('/sales/sales_best_demand_per_style', name: 'app_sales_best_demand_per_style')]
    public function bestDemandPerStyleAlias(): Response
    {
        return $this->salesGeneric('best_demand_per_style');
    }

    #[Route('/sales/sales_poid_famille_par_variant', name: 'app_sales_poid_famille_par_variant')]
    public function poidFamileParVariantAlias(): Response
    {
        return $this->salesGeneric('poid_famille_par_variant');
    }

    #[Route('/sales/commandes_allouees_sans_bp', name: 'app_sales_commandes_allouees_sans_bp')]
    public function commandesAlloueesSansBpAlias(): Response
    {
        return $this->salesGeneric('commandes_allouees_sans_bp');
    }

    #[Route('/sales/commandes_bp_sans_livraison', name: 'app_sales_commandes_bp_sans_livraison')]
    public function commandesBpSansLivraisonAlias(): Response
    {
        return $this->salesGeneric('commandes_bp_sans_livraison');
    }

    #[Route('/sales/commandes_non_soldees_par_date', name: 'app_sales_commandes_non_soldees_par_date')]
    public function commandesNonSoldeesParDateAlias(): Response
    {
        return $this->salesGeneric('commandes_non_soldees_par_date');
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

    #[Route('/sales/sell_in_suivi_ps', name: 'app_sales_sell_in_suivi_ps')]
    public function sellInSuiviPs(): Response
    {
        $gridName = 'sell_in_suivi_ps_grid';

        $agridOptions = $this->aggridOptionRepository->findBy(
            ['gridName' => $gridName],
            ['orderIndex' => 'ASC']
        );

        $grid = $this->columnBuilder->build($agridOptions);

        return $this->render('sales/sell_in_suivi_ps.html.twig', [
            'title'          => 'Sell-In : Suivi PS',
            'columns'        => $grid['columns'],
            'numericColumns' => $grid['numericColumns'],
            'integerColumns' => $grid['integerColumns'],
            'totalColumns'   => $grid['totalColumns'],
            'dataUrl'        => $this->generateUrl('sales_sell_in_suivi_ps_json'),
            'saveUrl'        => $this->generateUrl('sales_sell_in_suivi_ps_save'),
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
        $family      = $request->query->get('family');
        $collection  = $request->query->get('collection');

        $data = $sales->getExcessForSales($tariffGroup, $family, $collection);

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

    #[Route('/sales/poid_famille_par_variant_json', name: 'poid_famille_par_variant_json')]
    public function poidFamilleParVariantJson(Request $request, Sales $sales, Helpers $helpers): JsonResponse
    {
        $collection = $request->query->get('collection');
        $family = $request->query->get('family');
        $type = $request->query->get('type');

        $data = $sales->getPoidFamilleParVariant($collection, $family, $type);

        return new JsonResponse($helpers->convertArrayToUtf8($data));
    }

    #[Route('/sales/best_demand_per_style_json', name: 'best_demand_per_style_json')]
    public function bestDemandPerStyleJson(Request $request, Sales $sales, Helpers $helpers): JsonResponse
    {
        $collection = $request->query->get('collection');
        $family     = $request->query->get('family');

        $data = $sales->getBestDemandPerStyle($collection, $family);

        return new JsonResponse($helpers->convertArrayToUtf8($data));
    }

    #[Route('/sales/backlog_clients_x3_json', name: 'backlog_clients_x3_json')]
    public function backlogClientsX3Json(Sales $sales, Helpers $helpers): JsonResponse
    {
        $data = $sales->getBacklogClientsX3();
        return new JsonResponse($helpers->convertArrayToUtf8($data));
    }

    #[Route('/sales/backlog_clients_x3_ssrm_json', name: 'backlog_clients_x3_ssrm_json', methods: ['POST'])]
    public function backlogClientsX3SsrmJson(Request $request, Sales $sales, Helpers $helpers): JsonResponse
    {
        $payload = json_decode($request->getContent(), true) ?? [];
        $ssrmRequest = SsrmRequest::fromArray($payload);

        $response = $sales->getBacklogClientsX3Paginated($ssrmRequest);

        return new JsonResponse([
            'rows'    => $helpers->convertArrayToUtf8($response->rows),
            'lastRow' => $response->lastRow,
            'totals'  => $response->totals,   // 🆕
        ]);
    }

    #[Route('/sales/backlog_clients_x3_export_json', name: 'backlog_clients_x3_export_json', methods: ['POST'])]
    public function backlogClientsX3ExportJson(Request $request, Sales $sales, Helpers $helpers): JsonResponse
    {
        $payload = json_decode($request->getContent(), true) ?? [];
        $ssrmRequest = SsrmRequest::fromArray($payload);

        $rows = $sales->getBacklogClientsX3Full($ssrmRequest);

        return new JsonResponse($helpers->convertArrayToUtf8($rows));
    }

    #[Route('/sales/best_demand_per_style_item_groups_json', name: 'best_demand_per_style_item_groups_json')]
    public function bestDemandPerStyleItemGroupsJson(Divers $divers, Helpers $helpers): JsonResponse
    {
        $data = $divers->getItemGroups();

        return new JsonResponse($helpers->convertArrayToUtf8($data));
    }

    #[Route('/sales/commandes_allouees_sans_bp_json', name: 'commandes_allouees_sans_bp_json')]
    public function commandesAlloueesSansBpJson(Sales $sales, Helpers $helpers): JsonResponse
    {
        $data = $sales->getCommandesAlloueesSansBp();
        return new JsonResponse($helpers->convertArrayToUtf8($data));
    }

    #[Route('/sales/commandes_bp_sans_livraison_json', name: 'commandes_bp_sans_livraison_json')]
    public function commandesBpSansLivraisonJson(Sales $sales, Helpers $helpers): JsonResponse
    {
        $data = $sales->getCommandesBpSansLivraison();
        return new JsonResponse($helpers->convertArrayToUtf8($data));
    }

    #[Route('/sales/commandes_non_soldees_par_date_json', name: 'commandes_non_soldees_par_date_json')]
    public function commandesNonSoldeesParDateJson(Sales $sales, Helpers $helpers): JsonResponse
    {
        $data = $sales->getCommandesNonSoldeesParDate();
        return new JsonResponse($helpers->convertArrayToUtf8($data));
    }

    #[Route('/sales/sell_in_suivi_ps_json', name: 'sales_sell_in_suivi_ps_json')]
    public function sellInSuiviPsJson(Sales $sales, Helpers $helpers): JsonResponse
    {
        $data = $sales->getSellInSuiviPs();
        return new JsonResponse($helpers->convertArrayToUtf8($data));
    }

    ####################### Route divers ####################
    #[Route('/sales/poid_famille_par_variant_collections_json', name: 'poid_famille_par_variant_collections_json')]
    public function poidFamilleParVariantCollectionsJson(Divers $divers, Helpers $helpers): JsonResponse
    {
        $data = $divers->getCollections();

        return new JsonResponse($helpers->convertArrayToUtf8($data));
    }

    #[Route('/sales/poid_famille_par_variant_types_json', name: 'poid_famille_par_variant_types_json')]
    public function poidFamilleParVariantTypesJson(Divers $divers, Helpers $helpers): JsonResponse
    {
        $data = $divers->getTypes();

        return new JsonResponse($helpers->convertArrayToUtf8($data));
    }

    #[Route('/sales/sell_in_suivi_ps_save', name: 'sales_sell_in_suivi_ps_save', methods: ['POST'])]
    public function sellInSuiviPsSave(Request $request, Sales $sales): JsonResponse
    {
        $payload = json_decode($request->getContent(), true);

        if (!is_array($payload)) {
            return new JsonResponse(['success' => false, 'message' => 'Payload invalide'], 400);
        }

        $required = ['CUSTOMER_CODE', 'CITY', 'FAMILY', 'GENDER', 'SERIESNO', 'field', 'value'];
        foreach ($required as $key) {
            if (!array_key_exists($key, $payload)) {
                return new JsonResponse(['success' => false, 'message' => "Champ '$key' manquant"], 400);
            }
        }

        $allowedFields = ['SIMPLE_STATUS', 'FORECAST', 'TARGET'];
        if (!in_array($payload['field'], $allowedFields, true)) {
            return new JsonResponse(['success' => false, 'message' => 'Champ non éditable'], 403);
        }

        $userIdentifier = $this->getUser()?->getUserIdentifier() ?? 'unknown';

        try {
            $ok = $sales->saveSellInSuiviPs(
                customerCode: (string) $payload['CUSTOMER_CODE'],
                city:         (string) ($payload['CITY'] ?? ''),
                family:       (string) $payload['FAMILY'],
                gender:       (string) ($payload['GENDER'] ?? ''),
                seriesNo:     (string) $payload['SERIESNO'],
                field:        (string) $payload['field'],
                value:        $payload['value'],
                updatedBy:    $userIdentifier,
            );

            return new JsonResponse(['success' => $ok]);

        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['success' => false, 'message' => $e->getMessage()], 400);
        } catch (\Throwable $e) {
            return new JsonResponse(['success' => false, 'message' => 'Erreur serveur'], 500);
        }
    }

####################### Route NON généric ####################

####################### Route généric ####################

    #[Route(
        '/sales/{type}',
        name: 'app_sales_generic',
        requirements: ['type' => 'livraison_non_facturees|backlog_clients|commandes_a_facturer|commandes_a_facturer_x3|backlog_clients_x3|poid_famille_par_variant|best_demand_per_style|commandes_allouees_sans_bp|commandes_bp_sans_livraison|commandes_non_soldees_par_date']
    )]
    public function salesGeneric(string $type): Response
    {
        $config = [
            'livraison_non_facturees' => [
                'gridName'      => 'liv_non_facturees_grid',
                'title'         => 'Livraisons non facturées',
                'jsonRoute'     => 'livraison_non_facturees_json',
                'template'      => 'sales/sales_generic.html.twig',
                'gridWidthMode' => 'full',
            ],
            'backlog_clients' => [
                'gridName'      => 'backlog_clients_grid',
                'title'         => 'Backlog clients',
                'jsonRoute'     => 'backlog_clients_json',
                'template'      => 'sales/sales_generic.html.twig',
                'gridWidthMode' => 'full',
            ],
            'commandes_a_facturer' => [
                'gridName'      => 'commandes_a_facturer_grid',
                'title'         => 'Commandes à Facturer (Vue Balance âgée)',
                'jsonRoute'     => 'commandes_a_facturer_json',
                'template'      => 'sales/sales_generic.html.twig',
                'gridWidthMode' => 'full',
            ],
            'commandes_a_facturer_x3' => [
                'gridName'      => 'commandes_a_facturer_x3_grid',
                'title'         => 'Commandes à Facturer (Vue Balance âgée)',
                'jsonRoute'     => 'commandes_a_facturer_x3_json',
                'template'      => 'sales/sales_generic.html.twig',
                'gridWidthMode' => 'full',
            ],
            'backlog_clients_x3' => [
                'gridName'      => 'backlog_client_x3_grid',
                'title'         => 'Backlog Clients',
                'jsonRoute'     => 'backlog_clients_x3_ssrm_json',          // 🆕 route SSRM
                'exportRoute'   => 'backlog_clients_x3_export_json',        // 🆕 route export
                'template'      => 'sales/sales_generic.html.twig',
                'gridWidthMode' => 'full',
                'serverSide'    => true,                                     // 🆕 flag SSRM
                'blockSize'     => 200,                                      // 🆕 taille bloc
            ],
            'poid_famille_par_variant' => [
                'gridName'      => 'poid_famille_par_variant_grid',
                'title'         => 'Poids des tailles',
                'jsonRoute'     => 'poid_famille_par_variant_json',
                'template'      => 'sales/sales_generic.html.twig',
                'gridWidthMode' => 'auto',
            ],
            'best_demand_per_style' => [
                'gridName'      => 'best_demand_per_style_grid',
                'title'         => 'Best Demand per Style',
                'jsonRoute'     => 'best_demand_per_style_json',
                'template'      => 'sales/sales_generic.html.twig',
                'gridWidthMode' => 'auto',
            ],
            'commandes_allouees_sans_bp' => [
                'gridName'      => 'commandes_allouees_sans_bp_grid',
                'title'         => 'Commandes allouées sans BP',
                'jsonRoute'     => 'commandes_allouees_sans_bp_json',
                'template'      => 'sales/sales_generic.html.twig',
                'gridWidthMode' => 'full',
            ],
            'commandes_bp_sans_livraison' => [
                'gridName'      => 'commandes_bp_sans_livraison_grid',
                'title'         => 'Commandes avec BP sans livraison',
                'jsonRoute'     => 'commandes_bp_sans_livraison_json',
                'template'      => 'sales/sales_generic.html.twig',
                'gridWidthMode' => 'full',
            ],
            'commandes_non_soldees_par_date' => [
                'gridName'      => 'commandes_non_soldees_par_date_grid',
                'title'         => 'Suivi des commandes non soldées',
                'jsonRoute'     => 'commandes_non_soldees_par_date_json',
                'template'      => 'sales/sales_generic.html.twig',
                'gridWidthMode' => 'auto',
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
            'exportUrl'      => isset($gridConfig['exportRoute'])           // 🆕
                ? $this->generateUrl($gridConfig['exportRoute'])
                : null,
            'serverSide'     => $gridConfig['serverSide'] ?? false,         // 🆕
            'blockSize'      => $gridConfig['blockSize'] ?? 200,            // 🆕
            'type'           => $type,
            'gridWidthMode'  => $gridConfig['gridWidthMode'] ?? 'full',
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
