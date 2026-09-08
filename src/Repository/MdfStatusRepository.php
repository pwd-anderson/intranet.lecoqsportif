<?php

namespace App\Repository;

use App\Entity\MdfStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class MdfStatusRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MdfStatus::class);
    }

    public function findByCode(string $code): ?MdfStatus
    {
        return $this->findOneBy(['code' => $code]);
    }

    /** @return MdfStatus[] */
    public function findAllOrdered(): array
    {
        return $this->createQueryBuilder('s')
            ->orderBy('s.orderIndex', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
