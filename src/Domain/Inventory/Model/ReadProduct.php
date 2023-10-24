<?php

declare(strict_types=1);

namespace Domain\Inventory\Model;

use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Serializer\Annotation\SerializedName;

final readonly class ReadProduct
{
    /**
     * @param array<string,mixed> $extra
     */
    public function __construct(
        #[SerializedName('id')]
        public UuidInterface $identifier,
        public string $name,
        public ReadPrice $price,
        public int $amountAvailable,
        public array $extra = [],
    ) {
    }
}
