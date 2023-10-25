<?php

declare(strict_types=1);

namespace Domain\Basket\Service;

use Domain\Basket\Model\ReadBasket;
use Domain\Basket\Model\ReadBasketItem;
use Domain\Basket\Query\ShowBasketItemQuery;
use Domain\Basket\Query\ShowBasketQuery;
use Domain\Inventory\Service\ProductQueryServiceInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

final class BasketQueryService implements BasketQueryServiceInterface
{
    public function __construct(private readonly ProductQueryServiceInterface $productQueryService)
    {
    }

    #[AsMessageHandler]
    public function showBasket(ShowBasketQuery $query): ReadBasket
    {
        $basketEntity = $query->getBasketEntity();

        $items = [];
        foreach ($basketEntity->getBasketItems() as $basketItem) {
            $product = $this->productQueryService->mapToReadModel($basketItem->getProduct());
            $items[] = new ReadBasketItem(
                identifier: $basketItem->getIdentifier(),
                product: $product,
                amount: $basketItem->getQuantity()
            );
        }

        return new ReadBasket(
            identifier: $basketEntity->getIdentifier(),
            items: $items,
            status: $basketEntity->getStatus(),
            lastChangedAt: $basketEntity->getUpdatedAt()
        );
    }

    #[AsMessageHandler]
    public function showBasketItem(ShowBasketItemQuery $query): ReadBasketItem
    {
        $basketItem = $query->getBasketItemEntity();
        $product = $this->productQueryService->mapToReadModel($basketItem->getProduct());

        return new ReadBasketItem(
            identifier: $basketItem->getIdentifier(),
            product: $product,
            amount: $basketItem->getQuantity()
        );
    }
}
