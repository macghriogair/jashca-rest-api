<?php

declare(strict_types=1);

namespace Tests\Functional\Infrastructure\Messenger;

use Carbon\CarbonImmutable;
use Doctrine\DBAL\Connection;
use Domain\Entity\BasketStatus;
use Infrastructure\Messenger\CleanupOldBaskets;
use Ramsey\Uuid\Uuid;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * @coversDefaultClass \Infrastructure\Messenger\CleanupOldBasketsHandler
 */
class CleanupOldBasketsHandlerTest extends KernelTestCase
{
    private MessageBusInterface $messageBus;
    private Connection $connection;

    protected function setUp(): void
    {
        parent::setUp();
        self::bootKernel();
        /** @phpstan-ignore-next-line */
        $this->messageBus = $this->getContainer()->get(MessageBusInterface::class);
        /** @phpstan-ignore-next-line */
        $this->connection = $this->getContainer()->get(Connection::class);
        $this->truncateBasketsTable();
    }

    /**
     * @covers ::__invoke
     */
    public function testInvoke(): void
    {
        // prepare
        $olderThan = CarbonImmutable::now()->subHours(6);

        $basketId1 = $this->withOldPendingBasket($olderThan);
        $basketId2 = $this->withOldNonPendingBasket($olderThan);
        $basketId3 = $this->withNewerPendingBasket($olderThan);

        self::assertTrue($this->existsInDatabase($basketId1));
        self::assertTrue($this->existsInDatabase($basketId2));
        self::assertTrue($this->existsInDatabase($basketId3));

        // do
        // by triggering the handler through the bus, we also ensure the handler is correctly configured
        $this->messageBus->dispatch(
            new CleanupOldBaskets(olderThan: $olderThan)
        );

        // assert
        self::assertFalse($this->existsInDatabase($basketId1), 'Pending old basket must be removed');
        self::assertTrue($this->existsInDatabase($basketId2), 'Non-Pending old basket must be kept');
        self::assertTrue($this->existsInDatabase($basketId3), 'Newer basket must be kept');
    }

    private function truncateBasketsTable(): void
    {
        $this->connection->executeQuery('TRUNCATE TABLE public.basket CASCADE');
    }

    public function existsInDatabase(int $id): bool
    {
        return $this->connection
            ->executeQuery(
                <<<'SQL'
                    SELECT EXISTS (SELECT FROM public.basket WHERE id = :id)
                SQL,
                [
                    'id' => $id,
                ]
            )
            ->fetchOne();
    }

    private function withOldPendingBasket(CarbonImmutable $olderThan): int
    {
        return $this->doInsertWithStatus($olderThan, BasketStatus::PENDING);
    }

    private function withNewerPendingBasket(CarbonImmutable $olderThan): int
    {
        $olderThan = $olderThan->clone()->addSecond();
        return $this->doInsertWithStatus($olderThan, BasketStatus::PENDING);
    }

    private function withOldNonPendingBasket(CarbonImmutable $olderThan): int
    {
        return $this->doInsertWithStatus($olderThan, BasketStatus::FINISHED);
    }

    private function doInsertWithStatus(CarbonImmutable $olderThan, BasketStatus $status): int
    {
        static $id = -9999;
        $id++;

        $insertBasketSql = <<<'SQL'
                INSERT INTO public.basket
                        (id, identifier, status, created_at, updated_at, guest_token)
                    VALUES (
                            :id,
                            :identifier,
                            :status, 
                            :createdAt,
                            :updatedAt,
                            :guestToken
                    ) RETURNING *
            SQL;

        $result = $this->connection->prepare($insertBasketSql)->executeQuery([
            'id' => $id,
            'identifier' => Uuid::uuid4()->toString(),
            'status' => $status->value,
            'createdAt' => $olderThan->toString(),
            'updatedAt' => $olderThan->toString(),
            'guestToken' => uniqid('functional-test-'),
        ])->fetchAssociative();

        return $result['id'] ?? 0;
    }
}
