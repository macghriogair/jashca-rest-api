<?php

declare(strict_types=1);

namespace App\Entity;

enum BasketStatus: string
{
    case PENDING = 'pending';
    case CHECKOUT = 'checkout';
    case FINISHED = 'finished';
}
