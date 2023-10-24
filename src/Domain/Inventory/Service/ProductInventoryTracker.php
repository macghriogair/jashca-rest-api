<?php

declare(strict_types=1);

namespace Domain\Inventory\Service;

use Domain\Entity\BasketItem;
use Domain\Entity\Product;

final class ProductInventoryTracker implements ProductInventoryTrackerInterface
{
    public function isStockSufficient(Product $product, int $targetAmount): bool
    {
        return $this->getActualAvailableAmount($product) > $targetAmount;
    }

    public function getActualAvailableAmount(Product $product): int
    {
        return $product->getStockQuantity() - $this->getReservedQuantity($product);
    }

    // TODO: caching or view
    private function getReservedQuantity(Product $product): int
    {
        return $product->getBasketItems()->reduce(
            fn (int | null $carry, BasketItem $item) => ($carry ?? 0) + $item->getQuantity(),
            0
        );
    }
}
