<?php

declare(strict_types=1);

namespace App\Controller\Basket;

use App\Security\BasketAccess;
use Domain\Basket\Exception\DomainException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Serializer\SerializerInterface;

abstract class AbstractBasketController implements LoggerAwareInterface
{
    use LoggerAwareTrait;
    use HandleTrait {
        handle as handleInternal;
    }

    public function __construct(
        protected readonly BasketAccess $basketAccess,
        protected readonly SerializerInterface $serializer,
        /** @phpstan-ignore-next-line required by HandleTrait */
        private MessageBusInterface $messageBus,
    ) {
    }

    /**
     * Override to provide access to child class.
     */
    protected function handle(object $message): mixed
    {
        try {
            return $this->handleInternal($message);
        } catch (HandlerFailedException $e) {
            $this->logger->error($e->getMessage(), [$e]);
            // rethrow the inner exception, let the global error handler take care
            throw $e->getPrevious() instanceof DomainException ? $e->getPrevious() : $e;
        }
    }
}
