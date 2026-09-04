<?php

namespace App\Repository;

use App\Entity\X3Collection;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<X3Collection>
 */
class X3CollectionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, X3Collection::class);
    }

    /**
     * @return string[]
     */
    public function findAllCodesDesc(): array
    {
        $rows = $this->createQueryBuilder('c')
            ->select('c.seriesCode')
            ->orderBy('c.seriesCode', 'DESC')
            ->getQuery()
            ->getScalarResult();

        return array_column($rows, 'seriesCode');
    }
}
