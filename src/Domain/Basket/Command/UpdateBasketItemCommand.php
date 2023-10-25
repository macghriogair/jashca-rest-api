<?php

namespace Domain\Basket\Command;

use Domain\Basket\Model\WriteBasketItem;
use Domain\Entity\Basket;

final readonly class UpdateBasketItemCommand
{
    public function __construct(
        private Basket $basket,
        private WriteBasketItem $item
    ) {
    }

    public function getBasket(): Basket
    {
        return $this->basket;
    }

    public function getItem(): WriteBasketItem
    {
        return $this->item;
    }
}
