<?php

namespace App\Repository;

use App\Entity\ExchangeRatesMoyen;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ExchangeRatesMoyen>
 */
class ExchangeRatesMoyenRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ExchangeRatesMoyen::class);
    }

    public function getDataConversionRate(string $sourceCurrency): array
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = '
        SELECT
            DATE_FORMAT(date_cours, "%Y-%m") AS mois,
            AVG(rate) AS avg_rate
        FROM exchange_rates_moyen
        WHERE source_currency = :source
          AND target_currency = "CHF"
          AND date_cours BETWEEN :start AND :end
        GROUP BY mois
        ORDER BY mois ASC
    ';

        $end = new \DateTimeImmutable('last day of this month');
        $start = (clone $end)->modify('-12 months')->modify('first day of this month');

        $stmt = $conn->prepare($sql);
        $result = $stmt->executeQuery([
            'source' => $sourceCurrency,
            'start' => $start->format('Y-m-d'),
            'end' => $end->format('Y-m-d'),
        ]);

        $rows = $result->fetchAllAssociative();

        // Calcul variation mensuelle
        $final = [];
        $prev = null;
        foreach ($rows as $row) {
            $mois = $row['mois'];
            $avg = (float) $row['avg_rate'];
            $variationPos = 0;
            $variationNeg = 0;

            if ($prev !== null) {
                $variation = $avg - $prev;
                if ($variation > 0) {
                    $variationPos = $variation;
                } elseif ($variation < 0) {
                    $variationNeg = abs($variation);
                }
            }

            $final[] = [
                'mois' => $mois,
                'avg_rate' => $avg,
                'previous_rate' => $prev,
                'positive_variation' => $variationPos,
                'negative_variation' => $variationNeg,
            ];

            $prev = $avg;
        }

        return array_slice($final, 1); // ignorer premier mois sans comparaison
    }

    public function getCurrentRateAndEvolution(string $sourceCurrency): array
    {
        $conn = $this->getEntityManager()->getConnection();

        $currentMonth = new \DateTimeImmutable('first day of this month');
        $previousMonth = $currentMonth->modify('-1 month');
        $nextMonth = $currentMonth->modify('+1 month');

        $sql = '
        SELECT
            DATE_FORMAT(date_cours, "%Y-%m") AS mois,
            AVG(rate) AS avg_rate
        FROM exchange_rates_moyen
        WHERE source_currency = :source
          AND target_currency = "CHF"
          AND date_cours BETWEEN :start AND :end
        GROUP BY mois
    ';

        $stmt = $conn->prepare($sql);
        $result = $stmt->executeQuery([
            'source' => $sourceCurrency,
            'start' => $previousMonth->format('Y-m-d'),
            'end' => $nextMonth->format('Y-m-d'),
        ]);

        $rows = $result->fetchAllAssociative();

        $rates = [];
        foreach ($rows as $row) {
            $rates[$row['mois']] = (float) $row['avg_rate'];
        }

        $moisActuel = $currentMonth->format('Y-m');
        $moisPrecedent = $previousMonth->format('Y-m');

        $tauxActuel = $rates[$moisActuel] ?? null;
        $tauxPrecedent = $rates[$moisPrecedent] ?? null;

        $evolution = null;
        if ($tauxActuel !== null && $tauxPrecedent !== null && $tauxPrecedent != 0) {
            $evolution = round((($tauxActuel - $tauxPrecedent) / $tauxPrecedent) * 100, 2);
        }

        return [
            'taux_courant' => $tauxActuel,
            'taux_prec' => $tauxPrecedent,
            'evolution_pourcent' => $evolution,
        ];
    }

    //    /**
    //     * @return ExchangeRatesMoyen[] Returns an array of ExchangeRatesMoyen objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('e')
    //            ->andWhere('e.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('e.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?ExchangeRatesMoyen
    //    {
    //        return $this->createQueryBuilder('e')
    //            ->andWhere('e.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
