<?php

declare(strict_types=1);

namespace Domain\Inventory\Service;

use Domain\Entity\Product;
use Domain\Inventory\Model\ReadPrice;
use Domain\Inventory\Model\ReadProduct;
use Domain\Inventory\Model\ReadProductCollection;
use Domain\Inventory\Query\ListProductsQuery;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

final class ProductQueryService implements ProductQueryServiceInterface
{
    private const LOW_STOCK_LIMIT = 10;
    private const LOW_STOCK_MESSAGE_KEY = 'W_LOW_STOCK_AVAILABILITY';
    private const OUT_OF_STOCK_MESSAGE_KEY = 'E_CURRENTLY_OUT_OF_STOCK';

    public function __construct(
        private readonly ProductDataAccess $productDataAccess,
        private readonly ProductInventoryTrackerInterface $inventoryTracker
    ) {
    }

    #[AsMessageHandler]
    public function handleListQuery(ListProductsQuery $query): ReadProductCollection
    {
        // TODO: apply $query->getFilters() + pagination
        $mappedItems = array_map(
            fn (Product $p): ReadProduct => $this->mapProductEntity($p),
            (array)$this->productDataAccess->fetchProducts()
        );

        return new ReadProductCollection($mappedItems);
    }

    private function mapProductEntity(Product $product): ReadProduct
    {
        $amountAvailable = $this->inventoryTracker->getActualAvailableAmount($product);
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
