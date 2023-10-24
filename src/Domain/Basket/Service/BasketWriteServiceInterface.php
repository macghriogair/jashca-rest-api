<?php

declare(strict_types=1);

namespace Domain\Basket\Service;

use Domain\Basket\Command\CreateBasketCommand;
use Domain\Entity\Basket;

interface BasketWriteServiceInterface
{
    /**
     * Creates a new Basket for a User or a Guest
     */
    public function handleCreate(CreateBasketCommand $command): Basket;
}
