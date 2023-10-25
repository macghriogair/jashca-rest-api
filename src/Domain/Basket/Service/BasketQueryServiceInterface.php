<?php

declare(strict_types=1);

namespace Domain\Basket\Service;

use Domain\Basket\Model\ReadBasket;
use Domain\Basket\Model\ReadBasketItem;
use Domain\Basket\Query\ShowBasketItemQuery;
use Domain\Basket\Query\ShowBasketQuery;

interface BasketQueryServiceInterface
{
    /**
     * Get the read representation of a Basket
     */
    public function showBasket(ShowBasketQuery $query): ReadBasket;

    /**
     * Get the read representation of a single Basket Item
     */
    public function showBasketItem(ShowBasketItemQuery $query): ReadBasketItem;
}
