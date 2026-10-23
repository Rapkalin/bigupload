<?php

namespace App\Repository;

use App\Entity\Item;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Item>
 */
class ItemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Item::class);
    }

    /**
     * Item that can still be downloaded (not expired yet)
     */
    public function findAvailableByShowId(string $showId): ?Item
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.show_id = :showId')
            ->andWhere('i.expiration_date > :now')
            ->setParameter('showId', $showId)
            ->setParameter('now', new \DateTimeImmutable(), Types::DATETIMETZ_IMMUTABLE)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return list<Item>
     */
    public function findExpired(): array
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.expiration_date <= :now')
            ->setParameter('now', new \DateTimeImmutable(), Types::DATETIMETZ_IMMUTABLE)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<string>
     */
    public function findAllZipNames(): array
    {
        return $this->createQueryBuilder('i')
            ->select('i.zip_name')
            ->getQuery()
            ->getSingleColumnResult();
    }
}
