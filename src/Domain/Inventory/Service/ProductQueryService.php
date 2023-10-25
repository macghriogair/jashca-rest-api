<?php

declare(strict_types=1);

namespace Domain\Inventory\Service;

use Domain\Entity\Product;
use Domain\Inventory\Model\ReadProduct;
use Domain\Inventory\Model\ReadProductCollection;
use Domain\Inventory\Query\ListProductsQuery;
use Domain\Inventory\Transformer\ReadProductTransformer;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

final class ProductQueryService implements ProductQueryServiceInterface
{
    public function __construct(
        private readonly ProductDataAccess $productDataAccess,
        private readonly ProductInventoryTrackerInterface $inventoryTracker,
        private readonly ReadProductTransformer $readProductTransformer
    ) {
    }

    #[AsMessageHandler]
    public function handleListQuery(ListProductsQuery $query): ReadProductCollection
    {
        // TODO: apply $query->getFilters() + pagination
        $mappedItems = array_map(
            fn (Product $p): ReadProduct => $this->mapToReadModel($p),
            (array)$this->productDataAccess->fetchProducts()
        );

        return new ReadProductCollection($mappedItems);
    }

    public function mapToReadModel(Product $product): ReadProduct
    {
        return $this->readProductTransformer->mapToReadModel($product, $this->inventoryTracker);
    }
}
