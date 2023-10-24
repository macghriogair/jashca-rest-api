<?php

namespace Domain\Inventory\Service;

use Domain\Entity\Product;

interface ProductInventoryTrackerInterface
{
    public function isStockSufficient(Product $product, int $targetAmount): bool;

    public function getActualAvailableAmount(Product $product): int;
}
