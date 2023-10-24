<?php

declare(strict_types=1);

namespace Domain\Inventory\Model;

use Countable;
use IteratorAggregate;
use Traversable;
use Webmozart\Assert\Assert;

final readonly class ReadProductCollection implements IteratorAggregate, Countable
{
    /**
     * @param ReadProduct[] $items
     */
    public function __construct(
        public array $items,
    ) {
        Assert::allIsInstanceOf($items, ReadProduct::class);
    }

    public function getIterator(): Traversable
    {
        foreach ($this->items as $item) {
            yield $item;
        }
    }

    public function count(): int
    {
        return count($this->items);
    }
}
