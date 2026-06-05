<?php

namespace App\Controller;

use App\Repository\AggridOptionRepository;
use App\Service\AgGrid\AgGridColumnBuilder;
use App\Service\It;
use App\Service\Tools\Helpers;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ItController extends AbstractController
{
    public function __construct(
        private AggridOptionRepository $aggridOptionRepository,
        private AgGridColumnBuilder $columnBuilder,
    ) {}

    /*
    |--------------------------------------------------------------------------
    |  ROUTES ALIAS
    |--------------------------------------------------------------------------
    */

    #[Route('/it/tcd_cmd_non_soldees', name: 'app_it_tcd_cmd_non_soldees')]
    public function tcdCmdNonSoldeesAlias(): Response
    {
        return $this->itGeneric('tcd_cmd_non_soldees');
    }

    #[Route('/it/detail_tcd', name: 'app_it_detail_tcd')]
    public function detailTcdAlias(): Response
    {
        return $this->itGeneric('detail_tcd');
    }

    #[Route('/it/suivi_commandes_web_sav', name: 'app_it_suivi_commandes_web_sav')]
    public function suiviCommandesWebSavAlias(): Response
    {
        return $this->itGeneric('suivi_commandes_web_sav');
    }

    /*
    |--------------------------------------------------------------------------
    |  ROUTES JSON
    |--------------------------------------------------------------------------
    */

    #[Route('/it/tcd_cmd_non_soldees_json', name: 'it_tcd_cmd_non_soldees_json')]
    public function tcdCmdNonSoldeesJson(It $it, Helpers $helpers): JsonResponse
    {
        $data = $it->getTcdCmdNonSoldees();
        return new JsonResponse($helpers->convertArrayToUtf8($data));
    }

    #[Route('/it/detail_tcd_json', name: 'it_detail_tcd_json')]
    public function detailTcdJson(It $it, Helpers $helpers): JsonResponse
    {
        $data = $it->getDetailTcd();
        return new JsonResponse($helpers->convertArrayToUtf8($data));
    }

    #[Route('/it/suivi_commandes_web_sav_json', name: 'it_suivi_commandes_web_sav_json')]
    public function suiviCommandesWebSavJson(It $it, Helpers $helpers): JsonResponse
    {
        $data = $it->getSuiviCommandesWebSav();
        return new JsonResponse($helpers->convertArrayToUtf8($data));
    }

    /*
    |--------------------------------------------------------------------------
    |  ROUTE GENERIQUE
    |--------------------------------------------------------------------------
    */

    #[Route(
        '/it/{type}',
        name: 'app_it_generic',
        requirements: ['type' => 'tcd_cmd_non_soldees|detail_tcd|suivi_commandes_web_sav']
    )]
    public function itGeneric(string $type): Response
    {
        $config = [
            'tcd_cmd_non_soldees' => [
                'gridName'      => 'tcd_cmd_non_soldees_grid',
                'title'         => 'TCD Commandes web non Soldées',
                'jsonRoute'     => 'it_tcd_cmd_non_soldees_json',
                'template'      => 'it/it_generic.html.twig',
                'gridWidthMode' => 'auto',
            ],
            'detail_tcd' => [
                'gridName'      => 'detail_tcd_grid',
                'title'         => 'Détail du TCD web',
                'jsonRoute'     => 'it_detail_tcd_json',
                'template'      => 'it/it_generic.html.twig',
                'gridWidthMode' => 'full',
            ],
            'suivi_commandes_web_sav' => [
                'gridName'      => 'suivi_commandes_web_sav_grid',
                'title'         => 'Suivi Commandes web pour SAV',
                'jsonRoute'     => 'it_suivi_commandes_web_sav_json',
                'template'      => 'it/it_generic.html.twig',
                'gridWidthMode' => 'full',
            ],
        ];

        if (!isset($config[$type])) {
            throw $this->createNotFoundException(sprintf('Unknown IT type "%s"', $type));
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
            'type'           => $type,
            'gridWidthMode'  => $gridConfig['gridWidthMode'] ?? 'full',
        ]);
    }
}
