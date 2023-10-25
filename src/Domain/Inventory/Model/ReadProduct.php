<?php

declare(strict_types=1);

namespace Domain\Inventory\Model;

use OpenApi\Attributes as OA;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Serializer\Annotation\SerializedName;

final readonly class ReadProduct
{
    /**
     * @param array<string,mixed> $extra
     */
    public function __construct(
        #[SerializedName('id')]
        #[OA\Property(type: 'string')]
        public UuidInterface $identifier,
        public string $name,
        public ReadPrice $price,
        public int $amountAvailable,
        #[OA\Property(type: 'array', items: new OA\Items(type: 'string'))]
        public array $extra = [],
    ) {
    }
}
