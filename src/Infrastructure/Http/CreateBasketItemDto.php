<?php

declare(strict_types=1);

namespace Infrastructure\Http;

use Symfony\Component\Validator\Constraints\LessThanOrEqual;
use Symfony\Component\Validator\Constraints\Positive;
use Symfony\Component\Validator\Constraints\Uuid;

final readonly class CreateBasketItemDto
{
    public function __construct(
        #[Uuid]
        public string $productId,
        #[Positive]
        // TODO: from some configuration per product
        #[LessThanOrEqual(20, message: '20 items ought to be enough for anybody.')]
        public int $amount
    ) {
    }
}
