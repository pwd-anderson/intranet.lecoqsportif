<?php

namespace App\Controller;

use App\Repository\AggridOptionRepository;
use App\Service\Sales;
use App\Service\Stock;
use App\Service\Tools\Helpers;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class SalesController extends AbstractController
{
    #[Route('/sales/livraison_non_facturees', name: 'app_sales_livraison_non_facturees')]
    public function livraisonNonFacturees(AggridOptionRepository $aggridOptionRepository): Response
    {
        $agridOptions = $aggridOptionRepository->findBy(
            ['gridName' => 'liv_non_facturees_grid'],
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
                    'cellClass' => $option->getCellClass(),
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
                    'cellClass' => $option->getCellClass(),
                    'cellStyle' => $option->getCellStyle(),
                    'aggFunc' => $option->getAggFunc(),
                    'flex' => $option->getFlex(),
                ];
            }
        }

        return $this->render('sales/livraison_non_facturees.html.twig', [
            'columns' => $columns,
            'numericColumns' => $numericColumns,
            'integerColumns' => $integerColumns,
            'totalColumns' => $totalColumns,
        ]);
    }

    #[Route('/sales/livraison_non_facturees_json', name: 'livraison_non_facturees_json')]
    public function livraisonNonFactureesJson(Sales $sales, Helpers $helpers): JsonResponse
    {
        $dataSales = $sales->getLivraisonsNonFacturees();
        $dataSalesUtf8 = $helpers->convertArrayToUtf8($dataSales);
        return new JsonResponse($dataSalesUtf8);
    }
}
