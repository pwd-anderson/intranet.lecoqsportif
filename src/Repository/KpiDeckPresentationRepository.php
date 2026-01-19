<?php

namespace App\Repository;

use App\Entity\KpiDeckPresentation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<KpiDeckPresentation>
 */
class KpiDeckPresentationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, KpiDeckPresentation::class);
    }

    public function findByDeck(
        string $viewKey,
        int $year,
        int $week,
        array $storeKeys
    ): array {
        return $this->createQueryBuilder('k')
            ->where('k.viewKey = :view')
            ->andWhere('k.year = :year')
            ->andWhere('k.week = :week')
            ->andWhere('k.storeKey IN (:stores)')
            ->setParameter('view', $viewKey)
            ->setParameter('year', $year)
            ->setParameter('week', $week)
            ->setParameter('stores', $storeKeys)
            ->getQuery()
            ->getResult();
    }

    //    /**
    //     * @return KpiDeckPresentation[] Returns an array of KpiDeckPresentation objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('k')
    //            ->andWhere('k.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('k.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?KpiDeckPresentation
    //    {
    //        return $this->createQueryBuilder('k')
    //            ->andWhere('k.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
