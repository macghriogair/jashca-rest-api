<?php

declare(strict_types=1);

namespace Domain\Entity;

use Infrastructure\Repository\ProductRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Webmozart\Assert\Assert;

#[ORM\Entity(repositoryClass: ProductRepository::class)]
#[ORM\Table(name: 'product', schema: 'public')]
class Product
{
    #[ORM\Column(type: 'uuid', unique: true, nullable: false)]
    private ?UuidInterface $identifier = null;

    #[ORM\Embedded(columnPrefix: 'price_')]
    private ?ProductPrice $price = null;

    /**
     * @var Collection<BasketItem>
     */
    #[ORM\OneToMany(mappedBy: 'product', targetEntity: BasketItem::class, orphanRemoval: true)]
    private Collection $basketItems;

    public function __construct(
        #[ORM\Id]
        #[ORM\GeneratedValue]
        #[ORM\Column]
        private ?int $id = null,
        ?string $identifier = null,
        #[ORM\Column(length: 255, nullable: false)]
        private ?string $name = null,
        #[ORM\Column(nullable: false)]
        private ?int $stockQuantity = 0,
        #[ORM\Column]
        private ?\DateTimeImmutable $createdAt = null,
        #[ORM\Column]
        private ?\DateTimeImmutable $updatedAt = null,
        ?int $priceValue = null,
        ?string $priceCurrency = null,
        ?int $priceVat = null,
    ) {
        Assert::nullOrUuid($identifier, 'Argument $identifier is not a valid UUID: %s');
        if (null !== $identifier) {
            $this->identifier = Uuid::fromString($identifier);
        }

        Assert::nullOrPositiveInteger($priceValue, 'Argument $priceValue must be null or positive');
        $this->price = new ProductPrice($priceValue, $priceCurrency, $priceVat);
        $this->basketItems = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIdentifier(): ?UuidInterface
    {
        return $this->identifier;
    }

    public function setIdentifier(UuidInterface $identifier): static
    {
        $this->identifier = $identifier;

        return $this;
    }

    public function getStockQuantity(): ?int
    {
        return $this->stockQuantity;
    }

    public function setStockQuantity(int $stockQuantity): static
    {
        $this->stockQuantity = $stockQuantity;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getPrice(): ?ProductPrice
    {
        return $this->price;
    }

    public function setPrice(?ProductPrice $price): void
    {
        $this->price = $price;
    }

    /**
     * @return Collection<int, BasketItem>
     */
    public function getBasketItems(): Collection
    {
        return $this->basketItems;
    }

    public function addBasketItem(BasketItem $basketItem): static
    {
        if (!$this->basketItems->contains($basketItem)) {
            $this->basketItems->add($basketItem);
            $basketItem->setProduct($this);
        }

        return $this;
    }

    public function removeBasketItem(BasketItem $basketItem): static
    {
        if ($this->basketItems->removeElement($basketItem)) {
            // set the owning side to null (unless already changed)
            if ($basketItem->getProduct() === $this) {
                $basketItem->setProduct(null);
            }
        }

        return $this;
    }
}
