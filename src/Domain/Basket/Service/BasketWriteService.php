<?php

declare(strict_types=1);

namespace Domain\Basket\Service;

use Domain\Basket\Command\CreateBasketCommand;
use Domain\Basket\Exception\MissingUserOrGuestException;
use Domain\Basket\Exception\PendingUserBasketConflictException;
use Domain\Basket\Exception\ProductNotFoundException;
use Domain\Basket\Exception\ProductOutOfStockException;
use Domain\Entity\Basket;
use Domain\Entity\BasketItem;
use Domain\Entity\BasketStatus;
use Domain\Entity\Product;
use Domain\Entity\User;
use Domain\Inventory\Service\ProductDataAccess;
use Domain\Inventory\Service\ProductInventoryTrackerInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Webmozart\Assert\Assert;

final class BasketWriteService implements BasketWriteServiceInterface
{
    public function __construct(
        private readonly BasketDataAccess $basketDataAccess,
        private readonly ProductDataAccess $productDataAccess,
        private readonly ProductInventoryTrackerInterface $inventoryTracker
    ) {
    }

    #[AsMessageHandler]
    public function handleCreate(CreateBasketCommand $command): Basket
    {
        $this->assertValidUserOrGuest($command);

        // check product items are unique and exists
        $productIds = array_map(fn ($item) => $item->getProductIdentifier(), $command->getItems());
        Assert::uniqueValues($productIds, 'All product ids must be unique.');

        $products = $this->productDataAccess->findProductsByIdentifiers($productIds);
        if (count($products) !== count($productIds)) {
            throw new ProductNotFoundException('One or more products could not be found.');
        }

        $productsById = [];
        foreach ($products as $product) {
            $productsById[(string)$product->getIdentifier()] = $product;
        }

        $basket = $this->buildBasketEntity($command, $productsById);

        return $this->basketDataAccess->createBasket($basket);
    }

    private function assertValidUserOrGuest(CreateBasketCommand $command): void
    {
        // check validity user <> guest
        /** @var User|null $user */
        $user = $command->getUser();
        $guestToken = $command->getGuestToken();
        if (null === $user && null === $guestToken) {
            throw new MissingUserOrGuestException('Either the User or a Guest Token must be set.');
        }

        // check user has no current basked pending
        $this->assertUserHasNoPendingBasket($user);
    }

    private function assertUserHasNoPendingBasket(User | null $user): void
    {
        if (
            $user
            && null !== $user->getBasket()
            && $user->getBasket()->getStatus() !== BasketStatus::FINISHED
        ) {
            throw new PendingUserBasketConflictException(
                sprintf(
                    'A pending Basked already exists for current user: %s',
                    $user->getBasket()->getIdentifier()
                )
            );
        }
    }

    private function assertProductStockNotExceeded(
        Product $currentProduct,
        int $targetAmount
    ): void {
        if (false === $this->inventoryTracker->isStockSufficient($currentProduct, $targetAmount)) {
            throw new ProductOutOfStockException(
                sprintf(
                    'Not enough product items in stock. Product %s',
                    $currentProduct->getIdentifier(),
                )
            );
        }
    }

    /**
     * @param array<string, Product> $productsById
     */
    private function buildBasketEntity(CreateBasketCommand $command, array $productsById): Basket
    {
        $basket = new Basket();
        $basket->setIdentifier(Uuid::uuid4());
        if ($command->getUser()) {
            $basket->setOwner($command->getUser());
        } else {
            $basket->setGuestToken($command->getGuestToken());
        }

        foreach ($command->getItems() as $writeBasketItem) {
            $currentProduct = $productsById[(string)$writeBasketItem->getProductIdentifier()];
            $this->assertProductStockNotExceeded($currentProduct, $writeBasketItem->getAmount());

            $item = new BasketItem();
            $item->setIdentifier(Uuid::uuid4());
            $item->setBasket($basket);
            $item->setQuantity($writeBasketItem->getAmount());
            $item->setProduct($productsById[(string)$writeBasketItem->getProductIdentifier()]);
            $basket->addBasketItem($item);
        }

        return $basket;
    }
}
