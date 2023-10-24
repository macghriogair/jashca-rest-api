<?php

declare(strict_types=1);

namespace Domain\Basket\Service;

use Domain\Entity\Basket;

interface BasketDataAccess
{
    public function createBasket(Basket $basket): Basket;

    public function saveBasket(Basket $basket): Basket;
}
