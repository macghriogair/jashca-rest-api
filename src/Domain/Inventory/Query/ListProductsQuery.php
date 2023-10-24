<?php

declare(strict_types=1);

namespace Domain\Inventory\Query;

final readonly class ListProductsQuery
{
    /**
     * @param array<string,mixed>|null $filters
     */
    public function __construct(
        private ?int $offset = 0,
        private ?int $limit = 100,
        private ?array $filters = [],
    ) {
    }

    public function getOffset(): ?int
    {
        return $this->offset;
    }

    public function getLimit(): ?int
    {
        return $this->limit;
    }

    /**
     * @return array<string,mixed>|null
     */
    public function getFilters(): ?array
    {
        return $this->filters;
    }
}
