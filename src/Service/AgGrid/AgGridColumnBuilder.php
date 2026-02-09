<?php

namespace App\Service\AgGrid;

use App\Entity\AggridOption;

class AgGridColumnBuilder
{
    /**
     * @param AggridOption[] $agridOptions
     */
    public function build(array $agridOptions): array
    {
        $columns = [];
        $numericColumns = [];
        $integerColumns = [];
        $totalColumns = [];

        foreach ($agridOptions as $option) {
            if ($option->getType() === 'decimal') {
                $numericColumns[] = $option->getField();
            }

            if ($option->getType() === 'integer') {
                $integerColumns[] = $option->getField();
            }

            if ($option->getAggFunc()) {
                $totalColumns[] = $option->getField();
            }

            $column = [
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

            if ($option->getFilter() === 'agDateColumnFilter') {
                $column['valueFormatter'] = 'dateFormatter';
                $column['comparator'] = 'dateComparator';
                $column['filterParams'] = [
                    'comparator' => 'dateFilterComparator',
                ];
            }

            if ($option->isComputed()) {
                $column['computed'] = true;
            }

            $columns[] = $column;
        }

        return [
            'columns' => $columns,
            'numericColumns' => $numericColumns,
            'integerColumns' => $integerColumns,
            'totalColumns' => $totalColumns,
        ];
    }
}
