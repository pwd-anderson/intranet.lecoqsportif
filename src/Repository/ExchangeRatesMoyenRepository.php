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

    public function getDataConversionRate(string $sourceCurrency, string $targetCurrency = 'USD'): array
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = '
        SELECT
            DATE_FORMAT(mois_taux, "%Y-%m") AS mois,
            AVG(rate) AS avg_rate
        FROM exchange_rates_moyen
        WHERE source_currency = :source
          AND target_currency = :target
          AND mois_taux BETWEEN :start AND :end
        GROUP BY mois
        ORDER BY mois ASC
    ';

        $end = new \DateTimeImmutable('first day of this month'); // ex : 2025-10-01
        $start = (clone $end)->modify('-12 months');

        $stmt = $conn->prepare($sql);
        $result = $stmt->executeQuery([
            'source' => $sourceCurrency,
            'target' => $targetCurrency,
            'start' => $start->format('Y-m-d'),
            'end' => $end->format('Y-m-d'),
        ]);

        $rows = $result->fetchAllAssociative();

        // Calcul des variations
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

        return array_slice($final, 1); // on ignore le premier mois sans comparaison
    }

    public function getCurrentRateAndEvolution(string $sourceCurrency, string $targetCurrency = 'USD'): array
    {
        $conn = $this->getEntityManager()->getConnection();

        // On va chercher les taux pour mois N et N-1 => dates de fin mois N-1 et N-2
        $end = new \DateTimeImmutable('last day of last month'); // 30/09
        $start = (clone $end)->modify('-1 month')->modify('first day');

        $sql = '
        SELECT
            DATE_FORMAT(DATE_ADD(date_cours, INTERVAL 1 MONTH), "%Y-%m") AS mois,
            AVG(rate) AS avg_rate
        FROM exchange_rates_moyen
        WHERE source_currency = :source
          AND target_currency = :target
          AND date_cours BETWEEN :start AND :end
        GROUP BY mois
    ';

        $stmt = $conn->prepare($sql);
        $result = $stmt->executeQuery([
            'source' => $sourceCurrency,
            'target' => $targetCurrency,
            'start' => $start->format('Y-m-d'),
            'end' => $end->format('Y-m-d'),
        ]);

        $rows = $result->fetchAllAssociative();

        $rates = [];
        foreach ($rows as $row) {
            $rates[$row['mois']] = (float) $row['avg_rate'];
        }

        $moisPrecedent = $end->format('Y-m'); // exemple : 2025-09
        $moisActuel = $end->modify('+1 month')->format('Y-m'); // exemple : 2025-10

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
