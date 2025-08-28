<?php

namespace App\Controller;

use App\Repository\AggridOptionRepository;
use App\Service\Stock;
use App\Service\Tools\Helpers;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class StockController extends AbstractController
{
    #[Route('/stock/stock_a_terme', name: 'app_stock_a_terme')]
    public function stockATerme(AggridOptionRepository $aggridOptionRepository): Response
    {
        ini_set('display_errors', '1');
        ini_set('display_startup_errors', '1');
        error_reporting(E_ALL);
        ini_set('max_execution_time', 300); // 5 minutes
        ini_set('memory_limit', '-1');

        $agridOptions = $aggridOptionRepository->findBy(
            ['gridName' => 'stock_a_terme_grid'],
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
        return $this->render('stock/stock_a_terme.html.twig', [
            'columns' => $columns,
            'numericColumns' => $numericColumns,
            'integerColumns' => $integerColumns,
            'totalColumns' => $totalColumns,
        ]);
    }

    #[Route('/stock/stock_a_terme_json', name: 'stock_a_terme_json')]
    public function stockAtermeJson(Stock $stock, Helpers $helpers): JsonResponse
    {
        $dataStock = $stock->getStockATerme();
        $dataStockUtf8 = $helpers->convertArrayToUtf8($dataStock);
        return new JsonResponse($dataStockUtf8);
    }
}
