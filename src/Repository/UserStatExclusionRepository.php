<?php

namespace App\Repository;

use App\Entity\User;
use App\Entity\UserStatExclusion;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class UserStatExclusionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserStatExclusion::class);
    }

    /** @return string[] */
    public function findExcludedStatKeysForUser(User $user): array
    {
        return array_column(
            $this->createQueryBuilder('e')
                ->select('e.statKey')
                ->where('e.user = :user')
                ->setParameter('user', $user)
                ->getQuery()
                ->getArrayResult(),
            'statKey'
        );
    }

    public function setExclusions(User $user, array $statKeys): void
    {
        $em = $this->getEntityManager();

        // Supprimer toutes les exclusions existantes pour cet user
        $em->createQuery('DELETE FROM App\Entity\UserStatExclusion e WHERE e.user = :user')
            ->setParameter('user', $user)
            ->execute();

        foreach ($statKeys as $key) {
            $exclusion = new UserStatExclusion();
            $exclusion->setUser($user);
            $exclusion->setStatKey($key);
            $em->persist($exclusion);
        }

        $em->flush();
    }
}
