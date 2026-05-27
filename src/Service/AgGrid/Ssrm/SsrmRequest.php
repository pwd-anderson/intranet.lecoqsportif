<?php

namespace App\Service\AgGrid\Ssrm;

final class SsrmRequest
{
    public function __construct(
        public int $startRow,
        public int $endRow,
        public array $filterModel = [],
        public array $sortModel = [],
        public array $options = [],   // 🆕 options libres
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            startRow: (int) ($data['startRow'] ?? 0),
            endRow: (int) ($data['endRow'] ?? 200),
            filterModel: is_array($data['filterModel'] ?? null) ? $data['filterModel'] : [],
            sortModel:   is_array($data['sortModel']   ?? null) ? $data['sortModel']   : [],
            options:     is_array($data['options']     ?? null) ? $data['options']     : [],
        );
    }

    public function getLimit(): int  { return max(1, $this->endRow - $this->startRow); }
    public function getOffset(): int { return max(0, $this->startRow); }

    // 🆕 Helper typé
    public function getOption(string $key, mixed $default = null): mixed
    {
        return $this->options[$key] ?? $default;
    }
}
