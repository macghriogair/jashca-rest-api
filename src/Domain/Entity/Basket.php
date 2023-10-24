<?php

declare(strict_types=1);

namespace Domain\Entity;

use Infrastructure\Repository\BasketRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Webmozart\Assert\Assert;

#[ORM\Entity(repositoryClass: BasketRepository::class)]
#[ORM\Table(name: 'basket', schema: 'public')]
class Basket
{
    #[ORM\Column(type: 'uuid', unique: true, nullable: false)]
    private ?UuidInterface $identifier = null;

    #[ORM\Column(length: 20)]
    private BasketStatus $status;

    /**
     * @var Collection<BasketItem>
     */
    #[ORM\OneToMany(mappedBy: 'basket', targetEntity: BasketItem::class, orphanRemoval: true)]
    private Collection $basketItems;

    public function __construct(
        #[ORM\Id]
        #[ORM\GeneratedValue]
        #[ORM\Column]
        private ?int $id = null,
        ?string $identifier = null,
        #[ORM\OneToOne(inversedBy: 'basket')]
        private ?User $owner = null,
        #[ORM\Column]
        private ?\DateTimeImmutable $createdAt = null,
        #[ORM\Column]
        private ?\DateTimeImmutable $updatedAt = null,
        #[ORM\Column(type: 'text', nullable: true)]
        private ?string $guestToken = null,
        BasketStatus | string | null $status = null
    ) {
        Assert::nullOrUuid($identifier, 'Argument $identifier is not a valid UUID: %s');
        if (null !== $identifier) {
            $this->identifier = Uuid::fromString($identifier);
        }

        if (null === $status) {
            $this->status = BasketStatus::PENDING;
        } elseif (is_string($status)) {
            $this->status = BasketStatus::from($status);
        } else {
            $this->status = $status;
        }
        $this->basketItems = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStatus(): BasketStatus
    {
        return $this->status;
    }

    public function setStatus(BasketStatus | string $status): static
    {
        if (is_string($status)) {
            $status = BasketStatus::from($status);
        }

        $this->status = $status;

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

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function getGuestToken(): ?string
    {
        return $this->guestToken;
    }

    public function setCreatedAt(?\DateTimeImmutable $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }

    public function setGuestToken(?string $guestToken): void
    {
        $this->guestToken = $guestToken;
    }

    public function getOwner(): ?User
    {
        return $this->owner;
    }

    public function setOwner(?User $owner): void
    {
        $this->owner = $owner;
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
            $basketItem->setBasket($this);
        }

        return $this;
    }

    public function removeBasketItem(BasketItem $basketItem): static
    {
        if ($this->basketItems->removeElement($basketItem)) {
            // set the owning side to null (unless already changed)
            if ($basketItem->getBasket() === $this) {
                $basketItem->setBasket(null);
            }
        }

        return $this;
    }
}
