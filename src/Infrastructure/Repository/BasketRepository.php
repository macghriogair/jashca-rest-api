<?php

declare(strict_types=1);

namespace Infrastructure\Repository;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Domain\Basket\Service\BasketDataAccess;
use Domain\Entity\Basket;
use Domain\Entity\BasketStatus;

/**
 * @extends ServiceEntityRepository<Basket>
 *
 * @method Basket|null find($id, $lockMode = null, $lockVersion = null)
 * @method Basket|null findOneBy(array $criteria, array $orderBy = null)
 * @method Basket[]    findAll()
 * @method Basket[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class BasketRepository extends ServiceEntityRepository implements BasketDataAccess
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Basket::class);
    }

//    /**
//     * @return Basket[] Returns an array of Basket objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('b')
//            ->andWhere('b.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('b.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Basket
//    {
//        return $this->createQueryBuilder('b')
//            ->andWhere('b.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
    public function createBasket(Basket $basket): Basket
    {
        $em = $this->getEntityManager();
        $em->persist($basket);
        $em->flush();

        return $basket;
    }

    public function saveBasket(Basket $basket): Basket
    {
        $em = $this->getEntityManager();
        if (false === $em->contains($basket)) {
            throw new \RuntimeException(
                'Cannot save a Basket that is not tracked by entity manager!'
            );
        }
        $em->flush();
        $em->clear();

        return $basket;
    }

    /**
     * Delete pending Baskets and release reserved items.
     *
     * @return int the number of deleted rows
     */
    public function cleanupPendingBaskets(DateTimeImmutable $olderThan, int $limit = 100): int
    {
        // TODO: fixme UniqueConstraintViolation -> check Basket.basketItems cascade mapping
        $em = $this->getEntityManager();
        $query = $this->createQueryBuilder('b')
            ->select()
            ->where('b.updatedAt <= :olderThan')
            ->andWhere('b.status = :status')
            ->setParameter('olderThan', $olderThan)
            ->setParameter('status', BasketStatus::PENDING->value)
            ->setMaxResults($limit)
            ->getQuery();

        $toDelete = $query->execute();
        $count = 0;
        /** @var Basket $basket */
        foreach ($toDelete as $basket) {
            ++$count;
            foreach ($basket->getBasketItems() as $item) {
                $em->remove($item);
            }
            $em->remove($basket);
        }
        $em->flush();
        $em->clear();

        return $count;
    }
}
