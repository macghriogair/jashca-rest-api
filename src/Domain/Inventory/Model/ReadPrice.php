<?php

declare(strict_types=1);

namespace Domain\Inventory\Model;

final readonly class ReadPrice
{
    public function __construct(
        public int $value,
        public string $currency,
        public int $vat
    ) {
    }
}
