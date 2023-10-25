<?php

declare(strict_types=1);

namespace Infrastructure\Messenger;

use Infrastructure\Repository\BasketRepository;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class CleanupOldBasketsHandler implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function __construct(private readonly BasketRepository $repo)
    {
    }

    public function __invoke(CleanupOldBaskets $message): void
    {
        $affectedRows = $this->repo->cleanupPendingBaskets($message->getOlderThan());

        $this->logger->info('{0} pending baskets were purged 🍻', [$affectedRows]);
    }
}
