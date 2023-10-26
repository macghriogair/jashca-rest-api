<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Basket;

use Carbon\CarbonImmutable;
use Domain\Basket\Model\ReadBasketItem;
use Domain\Basket\Query\ShowBasketItemQuery;
use Domain\Basket\Query\ShowBasketQuery;
use Domain\Basket\Service\BasketQueryService;
use Domain\Entity\Basket;
use Domain\Entity\BasketItem;
use Domain\Entity\Product;
use Domain\Inventory\Service\ProductDataAccess;
use Domain\Inventory\Service\ProductInventoryTracker;
use Domain\Inventory\Service\ProductQueryService;
use Domain\Inventory\Transformer\ReadProductTransformer;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

/**
 * @coversDefaultClass \Domain\Basket\Service\BasketQueryService
 */
class BasketQueryServiceTest extends TestCase
{
    private BasketQueryService $serviceUnderTest;

    protected function setUp(): void
    {
        parent::setUp();
        $this->serviceUnderTest = new BasketQueryService(
            new ProductQueryService(
                $this->createMock(ProductDataAccess::class),
                new ProductInventoryTracker(),
                new ReadProductTransformer(),
            )
        );
    }

    /**
     * @covers ::showBasket
     */
    public function testShowBasket(): void
    {
        // prepare
        $basketStub = $this->buildBasketStub();
        $query = new ShowBasketQuery($basketStub);

        // do
        $readBasket = $this->serviceUnderTest->showBasket($query);

        // assert
        self::assertSame($basketStub->getIdentifier(), $readBasket->identifier);
        self::assertCount($basketStub->getBasketItems()->count(), $readBasket->items);
        self::assertReadBasketItemMatches(
            $basketStub->getBasketItems()[0],
            $readBasket->items[0]
        );
    }

    /**
     * @covers ::showBasketItem
     */
    public function testShowBasketItem(): void
    {
        // prepare
        $basketItemStub = $this->buildBaskedItemStub();
        $query = new ShowBasketItemQuery($basketItemStub);

        // do
        $readBasketItem = $this->serviceUnderTest->showBasketItem($query);

        // assert
        self::assertReadBasketItemMatches($basketItemStub, $readBasketItem);
    }

    private static function assertReadBasketItemMatches(
        BasketItem $basketItemStub,
        ReadBasketItem $actual
    ): void {
        self::assertSame($basketItemStub->getIdentifier(), $actual->identifier);
        self::assertSame(
            $basketItemStub->getProduct()->getIdentifier(),
            $actual->product->identifier,
        );
        self::assertSame(
            $basketItemStub->getQuantity(),
            $actual->amount
        );
    }

    private function buildBasketStub(): Basket
    {
        $aBasket = new Basket(id: -42);
        $aBasket->setIdentifier(Uuid::uuid4());
        $aBasket->addBasketItem($this->buildBaskedItemStub());
        $aBasket->setCreatedAt(CarbonImmutable::now());
        $aBasket->setUpdatedAt(CarbonImmutable::now());

        return $aBasket;
    }

    private function buildBaskedItemStub(): BasketItem
    {
        $aProduct = new Product(
            id: -43,
            name: 'Acme explosive tennis balls',
            stockQuantity: 50,
            priceValue: 10000
        );
        $aProduct->setIdentifier(Uuid::uuid4());

        return new BasketItem(identifier: Uuid::uuid4()->toString(), product: $aProduct, quantity: 3);
    }
}
