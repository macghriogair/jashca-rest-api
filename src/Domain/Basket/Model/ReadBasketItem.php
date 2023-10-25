<?php

declare(strict_types=1);

namespace Domain\Basket\Model;

use Domain\Inventory\Model\ReadProduct;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Serializer\Annotation\SerializedName;
use OpenApi\Attributes as OA;

final readonly class ReadBasketItem
{
    public function __construct(
        #[SerializedName('id')]
        #[OA\Property(type: 'string')]
        public UuidInterface $identifier,
        public ReadProduct $product,
        public int $amount,
    ) {
    }
}
