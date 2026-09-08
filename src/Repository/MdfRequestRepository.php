<?php

namespace App\Repository;

use App\Entity\MdfRequest;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class MdfRequestRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MdfRequest::class);
    }

    /** @return MdfRequest[] */
    public function findAllForList(): array
    {
        return $this->createQueryBuilder('r')
            ->addSelect('s')
            ->join('r.status', 's')
            ->orderBy('r.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /** @return MdfRequest[] */
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
        $prefix = 'MDF' . date('mY');
        $count  = (int) $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->where('r.numero LIKE :prefix')
            ->setParameter('prefix', $prefix . '%')
            ->getQuery()
            ->getSingleScalarResult();

        return $prefix . str_pad((string) ($count + 1), 10, '0', STR_PAD_LEFT);
    }

    /**
     * Somme des montants MDF archivés pour ce client sur l'année civile en cours,
     * en excluant éventuellement la demande en cours d'édition.
     */
    public function getMontantTotalArchiveParClient(string $clientCode, ?int $excludeId = null): float
    {
        // Doctrine DQL n'a pas de fonction YEAR() native — on borne par dates
        // plutôt que d'extraire l'année, ce qui reste du DQL portable standard.
        $yearStart = new \DateTimeImmutable('first day of january this year');
        $yearEnd   = new \DateTimeImmutable('first day of january next year');

        $qb = $this->createQueryBuilder('r')
            ->select('COALESCE(SUM(r.montantMdf), 0) AS total')
            ->join('r.status', 's')
            ->where('r.clientCode = :code')
            ->andWhere('s.code = :statut')
            ->andWhere('r.createdAt >= :yearStart')
            ->andWhere('r.createdAt < :yearEnd')
            ->setParameter('code', $clientCode)
            ->setParameter('statut', 'archive')
            ->setParameter('yearStart', $yearStart)
            ->setParameter('yearEnd', $yearEnd);

        if ($excludeId !== null) {
            $qb->andWhere('r.id != :excludeId')->setParameter('excludeId', $excludeId);
        }

        return (float) $qb->getQuery()->getSingleScalarResult();
    }
}
