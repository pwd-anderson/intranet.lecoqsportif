<?php

namespace App\Repository;

use App\Entity\SuiviSellinNote;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<SuiviSellinNote> */
class SuiviSellinNoteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SuiviSellinNote::class);
    }

    /** @return list<SuiviSellinNote> plus récentes d'abord */
    public function findAllRecentFirst(): array
    {
        return $this->findBy([], ['createdAt' => 'DESC', 'id' => 'DESC']);
    }
}
