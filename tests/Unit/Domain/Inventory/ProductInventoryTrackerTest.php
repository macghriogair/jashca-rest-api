<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Inventory;

use Domain\Entity\BasketItem;
use Domain\Entity\Product;
use Domain\Inventory\Service\ProductInventoryTracker;
use Generator;
use Monolog\Test\TestCase;

/**
 * @coversDefaultClass \Domain\Inventory\Service\ProductInventoryTracker
 */
class ProductInventoryTrackerTest extends TestCase
{
    /**
     * @dataProvider dataProviderIsStockSufficient
     *
     * @covers ::isStockSufficient
     * @covers ::getActualAvailableAmount
     */
    public function testIsStockSufficient(
        Product $product,
        int $targetAmount,
        int $expectedActualAvailableAmount,
        bool $expectedSufficient
    ): void {
        $tracker = new ProductInventoryTracker();

        self::assertSame(
            $tracker->getActualAvailableAmount($product),
            $expectedActualAvailableAmount
        );
        self::assertSame(
            $tracker->isStockSufficient($product, $targetAmount),
            $expectedSufficient
        );
    }

    public function dataProviderIsStockSufficient(): Generator
    {
        // without items that reserve quantity
        $product1 = new Product(
            identifier: '0b13e52d-b058-32fb-8507-10dec634a07c',
            name: 'product 1',
            stockQuantity: 10
        );

        // with items that reserve quantity
        $product2 = new Product(
            identifier: '94830791-a02a-3cdb-8789-06ede7653c53',
            name: 'product 1',
            stockQuantity: 10
        );
        $item1 = new BasketItem(product: $product2, quantity: 1);
        $product2->addBasketItem($item1);
        $item2 = new BasketItem(product: $product2, quantity: 4);
        $product2->addBasketItem($item2);

        yield 'w/o basket items - below stock - ok' => [$product1, 9, 10, true];
        yield 'w/o basket items - exact stock - ok' => [$product1, 10, 10, true];
        yield 'w/o basket items - above stock - nok' => [$product1, 11, 10, false];
        yield 'w/ basket items - below stock - ok' => [$product2, 4, 5, true];
        yield 'w/ basket items - exact stock - ok' => [$product2, 5, 5, true];
        yield 'w/ basket items - above stock - nok' => [$product2, 6, 5,  false];
    }
}
