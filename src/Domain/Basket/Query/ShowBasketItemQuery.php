<?php

declare(strict_types=1);

namespace Domain\Basket\Query;

use Domain\Entity\BasketItem;

final readonly class ShowBasketItemQuery
{
    public function __construct(private BasketItem $basketItemEntity)
    {
    }

    public function getBasketItemEntity(): BasketItem
    {
        return $this->basketItemEntity;
    }
}
