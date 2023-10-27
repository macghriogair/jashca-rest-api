<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Basket;

use Domain\Basket\Command\AddItemToBasketCommand;
use Domain\Basket\Command\CreateBasketCommand;
use Domain\Basket\Command\DeleteBasketItemCommand;
use Domain\Basket\Command\UpdateBasketItemCommand;
use Domain\Basket\Exception\InvalidBasketStatusException;
use Domain\Basket\Exception\PendingUserBasketConflictException;
use Domain\Basket\Exception\ProductAlreadyInBasketException;
use Domain\Basket\Exception\ProductOutOfStockException;
use Domain\Basket\Model\WriteBasketItem;
use Domain\Basket\Service\BasketDataAccess;
use Domain\Basket\Service\BasketWriteService;
use Domain\Entity\Basket;
use Domain\Entity\BasketItem;
use Domain\Entity\BasketStatus;
use Domain\Entity\Product;
use Domain\Entity\User;
use Domain\Inventory\Service\ProductDataAccess;
use Domain\Inventory\Service\ProductInventoryTracker;
use Monolog\Test\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Ramsey\Uuid\Uuid;
use Webmozart\Assert\InvalidArgumentException;

/**
 * @coversDefaultClass \Domain\Basket\Service\BasketWriteService
 */
class BasketWriteServiceTest extends TestCase
{
    private BasketDataAccess & MockObject $mockBasketDataAccess;
    private ProductDataAccess & MockObject $mockProductDataAccess;
    private BasketWriteService $serviceUnderTest;

    /**
     * @var string[]
     */
    private static array $productIdentifiers = [
        '32e4f1b4-c102-41d3-bb5a-b692cf498e01',
        '1d97185e-5f3d-4a5e-aa03-f14980452476',
        '0b13e52d-b058-32fb-8507-10dec634a07c',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockBasketDataAccess = $this->createMock(BasketDataAccess::class);
        $this->mockProductDataAccess = $this->createMock(ProductDataAccess::class);
        $this->serviceUnderTest = new BasketWriteService(
            $this->mockBasketDataAccess,
            $this->mockProductDataAccess,
            new ProductInventoryTracker(),
        );
    }

    /**
     * @covers ::handleCreate
     */
    public function testHandleCreateWithGuest(): void
    {
        // prepare
        $cmd = new CreateBasketCommand([], null, $guestToken = uniqid('guest-'));

        $expectedObjHash = null;
        $this->mockBasketDataAccess
            ->expects($this->once())
            ->method('createBasket')
            ->willReturnCallback(
                static function (Basket $basket) use (&$expectedObjHash, $guestToken): Basket {
                    self::assertNull($basket->getId());
                    self::assertEmpty($basket->getBasketItems());
                    self::assertSame($basket->getGuestToken(), $guestToken);
                    self::assertNull($basket->getOwner(), $guestToken);
                    $expectedObjHash = spl_object_hash($basket);

                    return $basket;
                }
            );

        // do
        $result = $this->serviceUnderTest->handleCreate($cmd);
        // assert
        self::assertSame($expectedObjHash, spl_object_hash($result));
    }

    /**
     * @covers ::handleCreate
     */
    public function testHandleCreateWithUser(): void
    {
        // prepare
        $user = new User();
        $cmd = new CreateBasketCommand([], $user);

        $expectedObjHash = null;
        $this->mockBasketDataAccess
            ->expects($this->once())
            ->method('createBasket')
            ->willReturnCallback(
                static function (Basket $basket) use (&$expectedObjHash): Basket {
                    self::assertNull($basket->getId());
                    $expectedObjHash = spl_object_hash($basket);

                    return $basket;
                }
            );

        // do
        $actualBasket = $this->serviceUnderTest->handleCreate($cmd);

        // assert
        self::assertSame($expectedObjHash, spl_object_hash($actualBasket));
        self::assertEmpty($actualBasket->getBasketItems());
        self::assertSame($user, $actualBasket->getOwner());
        self::assertNull($actualBasket->getGuestToken());
    }

    /**
     * @covers ::handleCreate
     */
    public function testHandleCreateWithItems(): void
    {
        // prepare
        $user = new User();
        $cmd = new CreateBasketCommand(
            $this->buildWriteBasketItems(1, 3, 5),
            $user
        );

        $this->mockProductDataAccess->expects($this->once())
            ->method('findProductsByIdentifiers')
            ->with(self::$productIdentifiers)
            ->willReturn(
                $this->buildProducts(10, 20, 5)
            );
        $this->mockBasketDataAccess->expects($this->once())->method('createBasket')
            ->willReturnCallback(
                static function (Basket $basket) use (&$expectedObjHash): Basket {
                    $expectedObjHash = spl_object_hash($basket);

                    return $basket;
                }
            );

        // do
        $actualBasket = $this->serviceUnderTest->handleCreate($cmd);

        // assert
        self::assertCount(count(self::$productIdentifiers), $actualBasket->getBasketItems());
        foreach ($actualBasket->getBasketItems() as $i => $item) {
            self::assertSame(self::$productIdentifiers[$i], $item->getProduct()->getIdentifier()->toString());
        }
        self::assertSame($user, $actualBasket->getOwner());
        self::assertNull($actualBasket->getGuestToken());
    }

    /**
     * @covers ::handleCreate
     */
    public function testHandleCreateWithUserWithOtherPendingCartThrows(): void
    {
        // prepare
        $user = new User();
        $user->setBasket(
            new Basket(status: BasketStatus::PENDING)
        );
        $cmd = new CreateBasketCommand([], $user);

        $this->mockBasketDataAccess->expects($this->never())->method('createBasket');

        // do
        self::expectException(PendingUserBasketConflictException::class);
        $this->serviceUnderTest->handleCreate($cmd);
    }

    /**
     * @covers ::handleCreate
     */
    public function testHandleCreateWithDuplicateProductsThrows(): void
    {
        // prepare
        $writeItems = $this->buildWriteBasketItems();
        $writeItems[] = $writeItems[count($writeItems) - 1];
        $user = new User();
        $cmd = new CreateBasketCommand(
            $writeItems,
            $user
        );

        $this->mockBasketDataAccess->expects($this->never())->method('createBasket');

        // do
        self::expectException(InvalidArgumentException::class);
        self::expectExceptionMessage('All product ids must be unique.');
        $this->serviceUnderTest->handleCreate($cmd);
    }

    /**
     * @covers ::handleCreate
     */
    public function testHandleCreateForOutOfStockProductThrows(): void
    {
        // prepare
        $user = new User();
        $cmd = new CreateBasketCommand(
            $this->buildWriteBasketItems(1, 5, 1),
            $user
        );

        $this->mockProductDataAccess->expects($this->once())
            ->method('findProductsByIdentifiers')
            ->with(self::$productIdentifiers)
            ->willReturn($this->buildProducts(10, 4, 10));

        $this->mockBasketDataAccess->expects($this->never())->method('createBasket');

        // do
        self::expectException(ProductOutOfStockException::class);
        self::expectExceptionMessage(
            'Not enough product items in stock. Product 1d97185e-5f3d-4a5e-aa03-f14980452476'
        );

        $this->serviceUnderTest->handleCreate($cmd);
    }

    /**
     * @covers ::handleAddItem
     */
    public function testHandleAddItem(): void
    {
        // prepare
        $basket = new Basket();
        $writeItem = new WriteBasketItem(
            Uuid::fromString(self::$productIdentifiers[0]),
            amount: 1
        );
        $cmd = new AddItemToBasketCommand($basket, $writeItem);

        $this->mockProductDataAccess->expects($this->once())
            ->method('findProductsByIdentifiers')
            ->with([self::$productIdentifiers[0]])
            ->willReturn([
                new Product(
                    identifier: self::$productIdentifiers[0],
                    name: 'product 1',
                    stockQuantity: 10
                ),
            ]);
        $this->mockBasketDataAccess->expects($this->once())
            ->method('saveBasket')
            ->with($basket)
            ->willReturn($basket);

        // do
        $actualItem = $this->serviceUnderTest->handleAddItem($cmd);

        // assert
        self::assertSame($basket, $actualItem->getBasket());
        self::assertCount(1, $actualItem->getBasket()->getBasketItems());
        self::assertSame($actualItem, $actualItem->getBasket()->getBasketItems()[0]);
    }

    /**
     * @covers ::handleAddItem
     */
    public function testHandleAddItemWhenAlreadyContainedThrows(): void
    {
        // prepare
        $basket = new Basket();
        $aProduct = new Product(
            identifier: self::$productIdentifiers[0],
            name: 'product 1',
            stockQuantity: 10
        );
        $basket->addBasketItem(
            new BasketItem(product: $aProduct)
        );

        $writeItem = new WriteBasketItem(
            Uuid::fromString(self::$productIdentifiers[0]),
            amount: 1
        );
        $cmd = new AddItemToBasketCommand($basket, $writeItem);

        $this->mockProductDataAccess->expects($this->once())
            ->method('findProductsByIdentifiers')
            ->with([self::$productIdentifiers[0]])
            ->willReturn([$aProduct]);
        $this->mockBasketDataAccess->expects($this->never())
            ->method('saveBasket');

        // do
        self::expectException(ProductAlreadyInBasketException::class);
        $this->serviceUnderTest->handleAddItem($cmd);
    }

    /**
     * @covers ::handleAddItem
     */
    public function testHandleAddItemWhenProductStockExceededThrows(): void
    {
        // prepare
        $basket = new Basket();
        $writeItem = new WriteBasketItem(
            Uuid::fromString(self::$productIdentifiers[0]),
            amount: 6
        );
        $cmd = new AddItemToBasketCommand($basket, $writeItem);

        $this->mockProductDataAccess->expects($this->once())
            ->method('findProductsByIdentifiers')
            ->with([self::$productIdentifiers[0]])
            ->willReturn([
                new Product(
                    identifier: self::$productIdentifiers[0],
                    name: 'product 1',
                    stockQuantity: 5
                ),
            ]);
        $this->mockBasketDataAccess->expects($this->never())
            ->method('saveBasket');

        // do
        self::expectException(ProductOutOfStockException::class);
        $this->serviceUnderTest->handleAddItem($cmd);
    }

    /**
     * @covers ::handleUpdateItem
     */
    public function testHandleUpdateItem(): void
    {
        $expectedNewAmount = 20;
        // prepare
        $basket = new Basket();
        $aProduct = new Product(
            identifier: self::$productIdentifiers[0],
            name: 'product 1',
            stockQuantity: 20
        );
        $basket->addBasketItem(new BasketItem(product: $aProduct, quantity: 10));
        $writeItem = new WriteBasketItem(
            Uuid::fromString(self::$productIdentifiers[0]),
            amount: $expectedNewAmount
        );
        $this->mockBasketDataAccess->expects($this->once())
            ->method('saveBasket')
            ->willReturn($basket);

        $cmd = new UpdateBasketItemCommand($basket, $writeItem);

        // do
        $updatedItem = $this->serviceUnderTest->handleUpdateItem($cmd);
        self::assertSame($expectedNewAmount, $updatedItem->getQuantity());
    }

    /**
     * @covers ::handleUpdateItem
     */
    public function testHandleUpdateItemWhenProductStockExceededThrows(): void
    {
        $newAmount = 4;
        // prepare
        $basket = new Basket();
        $aProduct = new Product(
            identifier: self::$productIdentifiers[0],
            name: 'product 1',
            stockQuantity: 3
        );
        $aBasketItem = new BasketItem(basket: $basket, product: $aProduct, quantity: 1);
        $aProduct->addBasketItem($aBasketItem);
        $basket->addBasketItem($aBasketItem);

        $writeItem = new WriteBasketItem(
            Uuid::fromString(self::$productIdentifiers[0]),
            amount: $newAmount
        );
        $this->mockBasketDataAccess->expects($this->never())->method('saveBasket');

        $cmd = new UpdateBasketItemCommand($basket, $writeItem);

        // do
        self::expectException(ProductOutOfStockException::class);
        $this->serviceUnderTest->handleUpdateItem($cmd);
    }

    /**
     * @covers ::handleDeleteItem
     */
    public function testHandleDeleteItem(): void
    {
        // prepare
        $basket = new Basket();
        $aProduct = new Product(
            identifier: self::$productIdentifiers[0],
            name: 'product 1',
            stockQuantity: 3
        );
        $aBasketItem = new BasketItem(basket: $basket, product: $aProduct, quantity: 1);
        $aBasketItem->setIdentifier(Uuid::uuid4());
        $basket->addBasketItem($aBasketItem);

        $cmd = new DeleteBasketItemCommand($basket, $aBasketItem->getIdentifier());

        $this->mockBasketDataAccess
            ->expects($this->once())
            ->method('saveBasket');

        // do
        self::assertSame($basket, $aBasketItem->getBasket());
        $this->serviceUnderTest->handleDeleteItem($cmd);

        // assert
        self::assertNull($aBasketItem->getBasket());
        self::assertEmpty($basket->getBasketItems());
    }

    /**
     * @covers ::handleDeleteItem
     */
    public function testHandleDeleteItemWhenBasketNotPendingThrows(): void
    {
        // prepare
        $basket = new Basket(status: BasketStatus::CHECKOUT);
        $aProduct = new Product(
            identifier: self::$productIdentifiers[0],
            name: 'product 1',
            stockQuantity: 3
        );
        $aBasketItem = new BasketItem(basket: $basket, product: $aProduct, quantity: 1);
        $aBasketItem->setIdentifier(Uuid::uuid4());
        $basket->addBasketItem($aBasketItem);

        $cmd = new DeleteBasketItemCommand($basket, $aBasketItem->getIdentifier());

        $this->mockBasketDataAccess
            ->expects($this->never())
            ->method('saveBasket');

        // do
        self::assertSame($basket, $aBasketItem->getBasket());
        self::expectException(InvalidBasketStatusException::class);
        $this->serviceUnderTest->handleDeleteItem($cmd);
    }

    /**
     * @param int ...$amounts
     *
     * @return WriteBasketItem[]
     */
    private function buildWriteBasketItems(...$amounts): array
    {
        $items = [];
        foreach (self::$productIdentifiers as $index => $identifier) {
            $items[] = new WriteBasketItem(
                Uuid::fromString(self::$productIdentifiers[$index]),
                amount: $amounts[$index] ?? 1
            );
        }

        return $items;
    }

    /**
     * @param int ...$stockQuantities
     *
     * @return Product[]
     */
    private function buildProducts(...$stockQuantities): array
    {
        $products = [];
        foreach (self::$productIdentifiers as $index => $identifier) {
            $products[] = new Product(
                identifier: self::$productIdentifiers[$index],
                name: 'product ' . $index,
                stockQuantity: $stockQuantities[$index] ?? 0
            );
        }

        return $products;
    }
}
