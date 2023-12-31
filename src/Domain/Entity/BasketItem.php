<?php

declare(strict_types=1);

namespace Domain\Entity;

use Infrastructure\Repository\BasketItemRepository;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Webmozart\Assert\Assert;

#[ORM\Entity(repositoryClass: BasketItemRepository::class)]
#[ORM\Table(name: 'basket_item', schema: 'public')]
#[ORM\HasLifecycleCallbacks]
class BasketItem
{
    use HasTimestampsTrait;

    #[ORM\Column(type: 'uuid', unique: true, nullable: false)]
    private ?UuidInterface $identifier = null;

    public function __construct(
        ?string $identifier = null,
        #[ORM\Id]
        #[ORM\ManyToOne(inversedBy: 'basketItems')]
        #[ORM\JoinColumn(nullable: false)]
        private ?Basket $basket = null,
        #[ORM\ManyToOne(inversedBy: 'basketItems')]
        #[ORM\Id]
        #[ORM\JoinColumn(nullable: false)]
        private ?Product $product = null,
        #[ORM\Column]
        private ?int $quantity = null,
    ) {
        Assert::nullOrUuid($identifier, 'Argument $identifier is not a valid UUID: %s');
        if (null !== $identifier) {
            $this->identifier = Uuid::fromString($identifier);
        }
    }

    public function getBasket(): ?Basket
    {
        return $this->basket;
    }

    public function setBasket(?Basket $basket): static
    {
        $this->basket = $basket;

        return $this;
    }

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function setProduct(?Product $product): static
    {
        $this->product = $product;

        return $this;
    }

    public function getQuantity(): ?int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): static
    {
        $this->quantity = $quantity;

        return $this;
    }

    public function getIdentifier(): ?UuidInterface
    {
        return $this->identifier;
    }

    public function setIdentifier(?UuidInterface $identifier): void
    {
        $this->identifier = $identifier;
    }
}
