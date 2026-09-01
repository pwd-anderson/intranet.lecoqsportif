<?php

namespace App\Repository;

use App\Entity\SoaStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class SoaStatusRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SoaStatus::class);
    }

    public function findByCode(string $code): ?SoaStatus
    {
        return $this->findOneBy(['code' => $code]);
    }

    /** @return SoaStatus[] */
    public function findAllOrdered(): array
    {
        return $this->createQueryBuilder('s')
            ->orderBy('s.orderIndex', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
