<?php

namespace Domain\Basket\Exception;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\WithHttpStatus;

#[WithHttpStatus(Response::HTTP_CONFLICT)]
final class ProductAlreadyInBasketException extends DomainException
{
}
