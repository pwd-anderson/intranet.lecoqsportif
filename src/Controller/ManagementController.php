<?php

namespace App\Controller;

use App\Repository\AggridOptionRepository;
use App\Service\Management;
use App\Service\Tools\Helpers;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ManagementController extends AbstractController
{
    #[Route('/management/ca_marges_year', name: 'app_ca_marges_year')]
    public function caMargeYear(AggridOptionRepository $aggridOptionRepository): Response
    {

        ini_set('display_errors', '1');
        ini_set('display_startup_errors', '1');
        error_reporting(E_ALL);
        ini_set('max_execution_time', 300); // 5 minutes
        ini_set('memory_limit', '-1');
        $agridOptions = $aggridOptionRepository->findBy(
            ['gridName' => 'ca_marge_by_year_grid'],
            ['orderIndex' => 'ASC']
        );
        $numericColumns = [];
        $totalColumns = [];
        $columns = [];
        $integerColumns = [];

        foreach ($agridOptions as $option) {

            if (in_array($option->getType(), ['decimal'])) {
                $numericColumns[] = $option->getField();
            }

            if (in_array($option->getType(), ['integer'])) {
                $integerColumns[] = $option->getField();
            }

            if ($option->getAggFunc()) {
                $totalColumns[] = $option->getField();
            }

            if ($option->getFilter() == 'agDateColumnFilter'){
                $columns[] = [
                    'field' => $option->getField(),
                    'headerName' => $option->getHeaderName(),
                    'filter' => $option->getFilter(),
                    'sortable' => $option->isSortable(),
                    'minWidth' => $option->getMinWidth(),
                    'cellStyle' => $option->getCellStyle(),
                    'aggFunc' => $option->getAggFunc(),
                    'flex' => $option->getFlex(),
                    'valueFormatter' => 'dateFormatter',
                    'comparator' => 'dateComparator',
                    'filterParams' => [
                        'comparator' => 'dateFilterComparator'
                    ]
                ];
            }else{
                $columns[] = [
                    'field' => $option->getField(),
                    'headerName' => $option->getHeaderName(),
                    'filter' => $option->getFilter(),
                    'sortable' => $option->isSortable(),
                    'minWidth' => $option->getMinWidth(),
                    'cellStyle' => $option->getCellStyle(),
                    'aggFunc' => $option->getAggFunc(),
                    'flex' => $option->getFlex(),
                ];
            }
        }
        return $this->render('management/ca_marges_year.html.twig', [
            'columns' => $columns,
            'numericColumns' => $numericColumns,
            'integerColumns' => $integerColumns,
            'totalColumns' => $totalColumns,
        ]);
    }

    #[Route('/management/ca_marges_year_json/{year}', name: 'ca_marges_year_json')]
    public function salesByYearJson(Management $management, int $year, Helpers $helpers): JsonResponse
    {
        $dataSales = $management->getCAMargesByYear($year);
        $dataSalesUtf8 = $helpers->convertArrayToUtf8($dataSales);
        return new JsonResponse($dataSalesUtf8);
    }
}
