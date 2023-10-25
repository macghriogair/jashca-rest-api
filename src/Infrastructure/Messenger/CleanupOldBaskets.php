<?php

declare(strict_types=1);

namespace Infrastructure\Messenger;

use Carbon\CarbonImmutable;
use DateTimeImmutable;

final class CleanupOldBaskets implements SyncMessageInterface
{
    public function __construct(
        private ?DateTimeImmutable $olderThan = null,
        private readonly int $limit = 1000,
    ) {
        $this->olderThan ??= CarbonImmutable::now()->subHour(); // older than 1 hour
    }

    public function getOlderThan(): DateTimeImmutable
    {
        return $this->olderThan;
    }

    public function getLimit(): int
    {
        return $this->limit;
    }
}
