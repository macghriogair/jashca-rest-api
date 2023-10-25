<?php

declare(strict_types=1);

namespace App\Security;

use Domain\Entity\Basket;
use Domain\Entity\User;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\User\UserInterface;

final class BasketAccess
{
    public const GUEST_TOKEN_HEADER = 'X-GUEST-TOKEN';

    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly TokenStorageInterface $tokenStorage,
    ) {
    }

    public function getCurrentUser(): UserInterface | User | null
    {
        return $this->tokenStorage->getToken()?->getUser();
    }

    public function assertCanAccessBasket(Basket $basket): void
    {
        if ($user = $this->getCurrentUser()) {
            // tbd. an admin can access all baskets
            if (in_array('ROLE_ADMIN', $user->getRoles(), true)) {
                return;
            }

            // the current user is owner
            if ($basket->getOwner()?->getUserIdentifier() === $user->getUserIdentifier()) {
                return;
            }
        } else {
            $guestToken = $this->requestStack->getCurrentRequest()->headers->get(
                self::GUEST_TOKEN_HEADER
            );
            // the basket belongs to the guest and a non-empty guest token was sent
            if (null !== $basket->getGuestToken() && $basket->getGuestToken() === $guestToken) {
                return;
            }
        }

        throw new AccessDeniedHttpException('Access denied.');
    }
}
