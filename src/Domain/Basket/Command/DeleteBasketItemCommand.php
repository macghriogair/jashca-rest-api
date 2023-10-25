<?php

declare(strict_types=1);

namespace Domain\Basket\Command;

use Domain\Entity\Basket;
use Ramsey\Uuid\UuidInterface;

final class DeleteBasketItemCommand
{
    public function __construct(
        private Basket $basket,
        private UuidInterface $basketItemIdentifier
    ) {
    }

    public function getBasket(): Basket
    {
        return $this->basket;
    }

    public function getBasketItemIdentifier(): UuidInterface
    {
        return $this->basketItemIdentifier;
    }
}
