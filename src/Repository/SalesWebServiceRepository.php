<?php

namespace App\Repository;

use App\Entity\SalesWebService;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class SalesWebServiceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SalesWebService::class);
    }

    /** @return SalesWebService[] Flux SOA générés mais pas encore envoyés à X3 */
    public function findPendingSoa(): array
    {
        return $this->createQueryBuilder('w')
            ->where('w.name = :name')
            ->andWhere('w.executed = false')
            ->andWhere('w.soaRequestId IS NOT NULL')
            ->setParameter('name', 'WSCRESIH')
            ->orderBy('w.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
