<?php

declare(strict_types=1);

namespace Domain\Basket\Command;

use Domain\Basket\Model\WriteBasketItem;
use Symfony\Component\Security\Core\User\UserInterface;
use Webmozart\Assert\Assert;

final readonly class CreateBasketCommand
{
    /**
     * @param WriteBasketItem[] $items
     * @param UserInterface|null $user
     * @param string|null $guestToken
     */
    public function __construct(
        private array $items = [],
        private ?UserInterface $user = null,
        private ?string $guestToken = null,
    ) {
        Assert::allIsInstanceOf($this->items, WriteBasketItem::class);
    }

    /**
     * @return WriteBasketItem[]
     */
    public function getItems(): array
    {
        return $this->items;
    }

    public function getUser(): ?UserInterface
    {
        return $this->user;
    }

    public function getGuestToken(): ?string
    {
        return $this->guestToken;
    }
}
