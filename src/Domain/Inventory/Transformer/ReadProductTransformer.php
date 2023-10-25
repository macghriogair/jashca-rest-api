<?php

namespace Domain\Inventory\Transformer;

use Domain\Entity\Product;
use Domain\Inventory\Model\ReadPrice;
use Domain\Inventory\Model\ReadProduct;
use Domain\Inventory\Service\ProductInventoryTrackerInterface;

final class ReadProductTransformer
{
    private const LOW_STOCK_LIMIT = 10;
    private const LOW_STOCK_MESSAGE_KEY = 'W_LOW_STOCK_AVAILABILITY';
    private const OUT_OF_STOCK_MESSAGE_KEY = 'E_CURRENTLY_OUT_OF_STOCK';

    public function mapToReadModel(
        Product $product,
        ProductInventoryTrackerInterface $inventoryTracker
    ): ReadProduct {
        $amountAvailable = $inventoryTracker->getActualAvailableAmount($product);
        $extra = [];
        if (0 >= $amountAvailable) {
            $extra['stockStatus'] = self::OUT_OF_STOCK_MESSAGE_KEY;
        } elseif ($amountAvailable < self::LOW_STOCK_LIMIT) {
            $extra['stockStatus'] = self::LOW_STOCK_MESSAGE_KEY;
        }

        return new ReadProduct(
            identifier: $product->getIdentifier(),
            name: $product->getName(),
            price: new ReadPrice(
                $product->getPrice()->getValue(),
                $product->getPrice()->getCurrency()->value,
                $product->getPrice()->getVat()->value,
            ),
            amountAvailable: $amountAvailable,
            extra: $extra
        );
    }
}
