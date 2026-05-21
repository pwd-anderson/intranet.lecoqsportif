<?php

namespace App\Service\AgGrid\Ssrm;

/**
 * Convertit les filtres/tris Ag-Grid SSRM en clauses SQL Server.
 *
 * IMPORTANT : utilise un échappement manuel (compatible dblib/FreeTDS)
 *             avec whitelist stricte des colonnes pour éviter les injections.
 *
 * Usage :
 *   $builder = new AgGridSqlBuilder($ssrmRequest, [
 *       'CLIENT' => 'BPC_INV.BPCNAM_0',
 *       'PAYS'   => 'SOH.BPCCRYNAM_0',
 *   ]);
 *   $where   = $builder->buildWhereClause();              // " AND ..."
 *   $orderBy = $builder->buildOrderByClause('SOQ.SOHNUM_0 ASC');
 */
final class AgGridSqlBuilder
{
    public function __construct(
        private SsrmRequest $request,
        /** @var array<string, string> field Ag-Grid → colonne SQL */
        private array $allowedFields,
    ) {}

    /**
     * Clause WHERE (sans le mot-clé, à concaténer après " WHERE 1=1 ").
     */
    public function buildWhereClause(): string
    {
        $clauses = [];

        foreach ($this->request->filterModel as $field => $filter) {
            $sqlColumn = $this->allowedFields[$field] ?? null;
            if ($sqlColumn === null) {
                continue; // protection : on ignore les champs hors whitelist
            }

            if (!is_array($filter)) {
                continue;
            }

            $clause = $this->buildFilterClause($sqlColumn, $filter);
            if ($clause !== null) {
                $clauses[] = $clause;
            }
        }

        return $clauses === [] ? '' : ' AND ' . implode(' AND ', $clauses);
    }

    /**
     * Clause ORDER BY (avec le mot-clé), tolère un tri par défaut.
     */
    public function buildOrderByClause(string $defaultOrderBy): string
    {
        if ($this->request->sortModel === []) {
            return ' ORDER BY ' . $defaultOrderBy;
        }

        $orders = [];
        foreach ($this->request->sortModel as $sort) {
            $field = $sort['colId'] ?? null;
            $direction = strtoupper((string) ($sort['sort'] ?? 'ASC'));

            $sqlColumn = $this->allowedFields[$field] ?? null;
            if ($sqlColumn === null) {
                continue;
            }
            if (!in_array($direction, ['ASC', 'DESC'], true)) {
                continue;
            }

            $orders[] = "$sqlColumn $direction";
        }

        return $orders === []
            ? ' ORDER BY ' . $defaultOrderBy
            : ' ORDER BY ' . implode(', ', $orders);
    }

    /**
     * Construit la clause OFFSET ... FETCH NEXT (SQL Server 2012+).
     */
    public function buildPaginationClause(): string
    {
        $offset = $this->request->getOffset();
        $limit  = $this->request->getLimit();

        return " OFFSET $offset ROWS FETCH NEXT $limit ROWS ONLY";
    }

    // ----------------------------------------------------------------
    // INTERNAL : conversion filtre Ag-Grid → SQL
    // ----------------------------------------------------------------

    private function buildFilterClause(string $sqlColumn, array $filter): ?string
    {
        // Filtre combiné (operator + conditions[])
        if (isset($filter['operator'], $filter['conditions']) && is_array($filter['conditions'])) {
            $operator = strtoupper((string) $filter['operator']) === 'OR' ? 'OR' : 'AND';
            $sub = [];
            foreach ($filter['conditions'] as $cond) {
                if (!is_array($cond)) continue;
                $clause = $this->buildSingleFilter($sqlColumn, $cond);
                if ($clause !== null) {
                    $sub[] = $clause;
                }
            }
            return $sub === [] ? null : '(' . implode(" $operator ", $sub) . ')';
        }

        return $this->buildSingleFilter($sqlColumn, $filter);
    }

    private function buildSingleFilter(string $sqlColumn, array $filter): ?string
    {
        $type = $filter['filterType'] ?? 'text';
        $op   = (string) ($filter['type'] ?? 'equals');

        return match ($type) {
            'text'   => $this->buildTextFilter($sqlColumn, $op, (string) ($filter['filter'] ?? '')),
            'number' => $this->buildNumberFilter($sqlColumn, $op, $filter['filter'] ?? null, $filter['filterTo'] ?? null),
            'date'   => $this->buildDateFilter($sqlColumn, $op, $filter['dateFrom'] ?? null, $filter['dateTo'] ?? null),
            default  => null,
        };
    }

    private function buildTextFilter(string $col, string $op, string $val): ?string
    {
        $escaped = $this->escapeString($val);

        return match ($op) {
            'equals'      => "$col = '$escaped'",
            'notEqual'    => "$col <> '$escaped'",
            'contains'    => "$col LIKE '%$escaped%'",
            'notContains' => "$col NOT LIKE '%$escaped%'",
            'startsWith'  => "$col LIKE '$escaped%'",
            'endsWith'    => "$col LIKE '%$escaped'",
            'blank'       => "($col IS NULL OR $col = '')",
            'notBlank'    => "($col IS NOT NULL AND $col <> '')",
            default       => null,
        };
    }

    private function buildNumberFilter(string $col, string $op, mixed $val, mixed $val2): ?string
    {
        if ($op === 'inRange') {
            if (!is_numeric($val) || !is_numeric($val2)) return null;
            $v1 = (float) $val;
            $v2 = (float) $val2;
            return "$col BETWEEN $v1 AND $v2";
        }
        if (!is_numeric($val) && $op !== 'blank' && $op !== 'notBlank') {
            return null;
        }
        $v = is_numeric($val) ? (float) $val : 0;

        return match ($op) {
            'equals'             => "$col = $v",
            'notEqual'           => "$col <> $v",
            'greaterThan'        => "$col > $v",
            'greaterThanOrEqual' => "$col >= $v",
            'lessThan'           => "$col < $v",
            'lessThanOrEqual'    => "$col <= $v",
            'blank'              => "$col IS NULL",
            'notBlank'           => "$col IS NOT NULL",
            default              => null,
        };
    }

    private function buildDateFilter(string $col, string $op, ?string $from, ?string $to): ?string
    {
        $fromIso = $from !== null ? $this->normalizeDate($from) : null;
        $toIso   = $to   !== null ? $this->normalizeDate($to)   : null;

        if ($fromIso === null && !in_array($op, ['blank', 'notBlank'], true)) {
            return null;
        }

        return match ($op) {
            'equals'      => "$col = '$fromIso'",
            'notEqual'    => "$col <> '$fromIso'",
            'lessThan'    => "$col < '$fromIso'",
            'greaterThan' => "$col > '$fromIso'",
            'inRange'     => $toIso !== null ? "$col BETWEEN '$fromIso' AND '$toIso'" : null,
            'blank'       => "$col IS NULL",
            'notBlank'    => "$col IS NOT NULL",
            default       => null,
        };
    }

    /**
     * Échappement SQL Server standard (doublement des quotes).
     */
    private function escapeString(string $value): string
    {
        return str_replace("'", "''", $value);
    }

    /**
     * Force le format YYYY-MM-DD et vérifie qu'il s'agit bien d'une date valide.
     */
    private function normalizeDate(string $value): ?string
    {
        $value = substr($value, 0, 10);
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }
        return $value;
    }
}
