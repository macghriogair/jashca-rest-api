<?php

declare(strict_types=1);

namespace Domain\Basket\Model;

use Ramsey\Uuid\UuidInterface;

final readonly class WriteBasketItem
{
    public function __construct(
        private UuidInterface $productIdentifier,
        private int $amount,
        private ?UuidInterface $basketItemIdentifier = null,
    ) {
    }
    public function getProductIdentifier(): UuidInterface
    {
        return $this->productIdentifier;
    }

    public function getAmount(): int
    {
        return $this->amount;
    }

    public function getBasketItemIdentifier(): ?UuidInterface
    {
        return $this->basketItemIdentifier;
    }
}
