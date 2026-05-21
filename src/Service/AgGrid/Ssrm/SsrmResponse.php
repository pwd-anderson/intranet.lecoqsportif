<?php

namespace App\Service\AgGrid\Ssrm;

final class SsrmResponse
{
    public function __construct(
        public readonly array $rows,
        public readonly ?int $lastRow = null,
        public readonly array $totals = [],   // 🆕
    ) {}

    public function toArray(): array
    {
        return [
            'rows'    => $this->rows,
            'lastRow' => $this->lastRow,
            'totals'  => $this->totals,        // 🆕
        ];
    }
}
