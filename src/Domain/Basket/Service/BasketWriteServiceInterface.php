<?php

declare(strict_types=1);

namespace Domain\Basket\Service;

use Domain\Basket\Command\AddItemToBasketCommand;
use Domain\Basket\Command\CreateBasketCommand;
use Domain\Basket\Command\DeleteBasketItemCommand;
use Domain\Basket\Command\UpdateBasketItemCommand;
use Domain\Basket\Exception\ProductAlreadyInBasketException;
use Domain\Basket\Exception\ProductNotFoundException;
use Domain\Entity\Basket;
use Domain\Entity\BasketItem;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

interface BasketWriteServiceInterface
{
    /**
     * Creates a new Basket for a User or a Guest
     */
    public function handleCreate(CreateBasketCommand $command): Basket;

    /**
     * Adds an Item to an existing Basket
     */
    public function handleAddItem(AddItemToBasketCommand $command): BasketItem;

    /**
     * Updates an Item contained in an existing Basket
     */
    public function handleUpdateItem(UpdateBasketItemCommand $command): BasketItem;

    /**
     * Removes an Item from an existing Basket
     */
    public function handleDeleteItem(DeleteBasketItemCommand $command): void;
}
