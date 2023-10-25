<?php

declare(strict_types=1);

namespace Domain\Basket\Model;

use DateTimeImmutable;
use Domain\Entity\BasketStatus;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Serializer\Annotation\SerializedName;
use Webmozart\Assert\Assert;
use OpenApi\Attributes as OA;

final readonly class ReadBasket
{
    /**
     * @param ReadBasketItem[] $items
     */
    public function __construct(
        #[SerializedName('id')]
        #[OA\Property(type: 'string')]
        public UuidInterface $identifier,
        public array $items,
        public BasketStatus $status,
        public DateTimeImmutable $lastChangedAt
    ) {
        Assert::allIsInstanceOf($this->items, ReadBasketItem::class);
    }
}
