<?php

declare(strict_types=1);

namespace Domain\Basket\Query;

use Domain\Entity\Basket;

final readonly class ShowBasketQuery
{
    public function __construct(private Basket $basketEntity)
    {
    }

    public function getBasketEntity(): Basket
    {
        return $this->basketEntity;
    }
}
