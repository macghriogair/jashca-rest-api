<?php

declare(strict_types=1);

namespace Domain\Entity;

enum ProductVat: int
{
    case PERCENT_19 = 19;
    case PERCENT_7 = 7;
}
