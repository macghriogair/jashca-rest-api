<?php

declare(strict_types=1);

namespace Domain\Inventory\Service;

use Domain\Inventory\Model\ReadProductCollection;
use Domain\Inventory\Query\ListProductsQuery;

interface ProductQueryServiceInterface
{
    /**
     * Get the product collection.
     */
    public function handleListQuery(ListProductsQuery $query): ReadProductCollection;
}
