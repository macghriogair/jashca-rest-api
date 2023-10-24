<?php

declare(strict_types=1);

namespace Infrastructure\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Domain\Entity\Product;
use Domain\Inventory\Service\ProductDataAccess;
use Ramsey\Uuid\UuidInterface;

/**
 * @extends ServiceEntityRepository<Product>
 *
 * @method Product|null find($id, $lockMode = null, $lockVersion = null)
 * @method Product|null findOneBy(array $criteria, array $orderBy = null)
 * @method Product[]    findAll()
 * @method Product[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProductRepository extends ServiceEntityRepository implements ProductDataAccess
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Product::class);
    }

    /**
     * @return Product[]
     */
    public function fetchProducts(): iterable
    {
        return $this->findAll();
        // TODO: pagination sth. like
//        $maxPerPage = self::MAX_ITEMS_PER_PAGE;
//
//        $query = $this->createQueryBuilder('p')
//            ->leftJoin('p.basketItems', 'basketItems')
//            ->orderBy('p.id', 'ASC')
//            ->getQuery();
//
//        $paginator = new Paginator($query);
//
//        $paginator
//            ->getQuery()
//            ->setFirstResult($maxPerPage * ($page - 1))
//            ->setMaxResults($maxPerPage);
//
//        return $paginator;
    }

    /**
     * @inheritDoc
     */
    public function findProductsByIdentifiers(array $identifiers): array
    {
        return $this->findBy(['identifier' => $identifiers]);
    }
}
