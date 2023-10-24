<?php

declare(strict_types=1);

namespace Domain\Inventory\Service;

use Domain\Entity\Product;
use Ramsey\Uuid\UuidInterface;

interface ProductDataAccess
{
    /**
     * @return Product[]
     */
    public function fetchProducts(): iterable;

    /**
     * @param UuidInterface[] $identifiers
     * @return Product[]
     */
    public function findProductsByIdentifiers(array $identifiers): array;
}
