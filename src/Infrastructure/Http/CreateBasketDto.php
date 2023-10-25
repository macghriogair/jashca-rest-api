<?php

declare(strict_types=1);

namespace Infrastructure\Http;

use Infrastructure\Http\CreateBasketItemDto;
use Symfony\Component\Validator\Constraints\Valid;
use Webmozart\Assert\Assert;

final readonly class CreateBasketDto
{
    /**
     * @param CreateBasketItemDto[] $items
     */
    public function __construct(
        #[Valid]
        public array $items = []
    ) {
        Assert::allIsInstanceOf($this->items, CreateBasketItemDto::class);
    }
}
