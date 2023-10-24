<?php

declare(strict_types=1);

namespace Domain\Entity;

enum BasketStatus: string
{
    case PENDING = 'pending';
    case CHECKOUT = 'checkout';
    case FINISHED = 'finished';
}
