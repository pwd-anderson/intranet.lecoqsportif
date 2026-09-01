<?php

namespace App\Repository;

use App\Entity\SoaRequest;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class SoaRequestRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SoaRequest::class);
    }

    /** @return SoaRequest[] */
    public function findAllForList(): array
    {
        return $this->createQueryBuilder('r')
            ->addSelect('s')
            ->join('r.status', 's')
            ->orderBy('r.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /** @return SoaRequest[] */
    public function findByRepresentant(string $representant): array
    {
        return $this->createQueryBuilder('r')
            ->addSelect('s')
            ->join('r.status', 's')
            ->where('r.representant = :rep')
            ->setParameter('rep', $representant)
            ->orderBy('r.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function generateNumero(): string
    {
        $prefix = 'SOA' . date('mY');
        $count  = (int) $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->where('r.numero LIKE :prefix')
            ->setParameter('prefix', $prefix . '%')
            ->getQuery()
            ->getSingleScalarResult();

        return $prefix . str_pad((string) ($count + 1), 10, '0', STR_PAD_LEFT);
    }
}
