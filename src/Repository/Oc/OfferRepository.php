<?php

namespace App\Repository\Oc;

use App\Entity\Oc\Offer;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<Offer> */
class OfferRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Offer::class);
    }

    public function numberExists(string $number): bool
    {
        return (bool) $this->createQueryBuilder('o')->select('1')->where('o.number = :n')->setParameter('n', $number)->setMaxResults(1)->getQuery()->getOneOrNullResult();
    }

    /**
     * Liste des OC avec le nombre de lignes. $onlyFor : restreint aux OC créées par cet utilisateur
     * (null = toutes, pour le valideur et les super-utilisateurs).
     *
     * @return list<array{0: Offer, lineCount: string}>
     */
    public function findListing(?User $onlyFor): array
    {
        $qb = $this->createQueryBuilder('o')
            ->select('o')
            ->addSelect('(SELECT COUNT(l.id) FROM App\Entity\Oc\OfferLine l WHERE l.offer = o) AS lineCount')
            ->orderBy('o.createdAt', 'DESC');

        if ($onlyFor !== null) {
            $qb->andWhere('o.createdBy = :u')->setParameter('u', $onlyFor);
        }

        return $qb->getQuery()->getResult();
    }
}
