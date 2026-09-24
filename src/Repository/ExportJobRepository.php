<?php

namespace App\Repository;

use App\Entity\ExportJob;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ExportJob>
 */
class ExportJobRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ExportJob::class);
    }

    /**
     * Export deja lance par cet utilisateur et pas encore termine.
     *
     * Sert de garde-fou contre les clics repetes : tant qu'un export tourne, un
     * nouveau clic renvoie celui-ci au lieu d'en lancer un second.
     */
    public function trouverEnCoursPourUtilisateur(?User $user, string $statKey): ?ExportJob
    {
        if ($user === null) {
            return null;
        }

        return $this->createQueryBuilder('e')
            ->andWhere('e.user = :user')
            ->andWhere('e.statKey = :statKey')
            ->andWhere('e.statut IN (:statuts)')
            ->setParameter('user', $user)
            ->setParameter('statKey', $statKey)
            ->setParameter('statuts', [ExportJob::STATUT_EN_ATTENTE, ExportJob::STATUT_EN_COURS])
            ->orderBy('e.creeLe', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Demandes anterieures a une date, pour la purge quotidienne.
     *
     * @return array<int, ExportJob>
     */
    public function trouverAnterieuresA(\DateTimeImmutable $date): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.creeLe < :date')
            ->setParameter('date', $date)
            ->getQuery()
            ->getResult();
    }
}
