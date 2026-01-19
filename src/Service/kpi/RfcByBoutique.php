<?php

namespace App\Service\kpi;

use App\Factory\MssqlManagerFactory;
use App\Service\Tools\GraphMailer;
use App\Service\Tools\Helpers;
use App\Service\Tools\MssqlManager;
use Psr\Log\LoggerInterface;

class RfcByBoutique
{
    private MssqlManager $mssqlLcs;
    public function __construct(
        private MssqlManagerFactory $mssqlManagerFactory,
        private LoggerInterface $logger,
        private GraphMailer $graphMailer,
        private Helpers $helpers
    ) {
        $this->mssqlLcs = $this->mssqlManagerFactory->create('lcs');
    }
    /**
     * RFC par boutique (clé = CODE MAG)
     * Retour en K€
     */
    public function getRfcByStore(int $year, int $week, bool $cumul): array
    {
        $sql = "
            SELECT
                [CODE MAG] AS code,
                SUM([RFC WEEK]) AS rfc_ca,
                SUM([RFC WEEK Marge Valeur]) AS rfc_marge
            FROM UCD..RFC_AVEC_MARGE
            WHERE YEAR = :year
            AND RESEAU = 'CONCEPT STORE'
        ";

        $params = [
            'year' => $year,
            'week' => $week,
        ];

        if ($cumul) {
            $sql .= " AND SEMAINE <= :week ";
        } else {
            $sql .= " AND SEMAINE = :week ";
        }

        $sql .= "
            GROUP BY [CODE MAG]
        ";

        $rows = $this->mssqlLcs->executeQueryWithParams($sql, $params);
        $out = [];

        foreach ($rows as $r) {
            if (empty($r->code)) {
                continue;
            }

            $out[(string)$r->code] = [
                'ca'    => (float) $r->rfc_ca,     // déjà en K€
                'marge' => (float) $r->rfc_marge,  // déjà en K€
            ];
        }

        return $out;
    }

    /**
     * RFC : retourne une valeur en K€
     */
    public function fetchRfc(int $year, int $week, bool $cumul, array $reseaux, string $columnName): float
    {
        // Si tu n'as pas encore la colonne marge RFC, on renvoie 0 sans casser le code
        if (trim($columnName) === '') {
            return 0.0;
        }

        // IN (...)
        $in = [];
        $params = [
            'year' => $year,
            'week' => $week,
        ];

        foreach ($reseaux as $idx => $r) {
            $key = 'r' . $idx;
            $in[] = ':' . $key;
            $params[$key] = $r;
        }

        $sql = "
            SELECT SUM([$columnName]) AS rfc
            FROM UCD..RFC_AVEC_MARGE
            WHERE YEAR = :year
              AND RESEAU IN (" . implode(',', $in) . ")
        ";

        if ($cumul) {
            $sql .= " AND SEMAINE <= :week ";
        } else {
            $sql .= " AND SEMAINE = :week ";
        }

        try {
            $rows = $this->mssqlLcs->executeQueryWithParams($sql, $params);
            $val = isset($rows[0]->rfc) ? (float) $rows[0]->rfc : 0.0;
            // RFC est déjà en K€ chez toi (comme slide 1)
            return $val;
        } catch (\Throwable $e) {
            // Si la colonne RFC marge n'existe pas, on ne casse pas le slide
            $this->logger->warning('RFC fetch failed: ' . $e->getMessage());
            return 0.0;
        }
    }

    /**
     * RFC agrégé (Concept Store / Total Retail)
     * Retour en K€
     */
    public function getRfcAggregate(
        int $year,
        int $week,
        bool $cumul,
        array $reseaux
    ): array {
        $in = [];
        $params = [
            'year' => $year,
            'week' => $week,
        ];

        foreach ($reseaux as $idx => $r) {
            $key = 'r' . $idx;
            $in[] = ':' . $key;
            $params[$key] = $r;
        }

        $sql = "
        SELECT
            SUM([RFC WEEK]) AS rfc_ca,
            SUM([RFC WEEK Marge Valeur]) AS rfc_marge
        FROM UCD..RFC_AVEC_MARGE
        WHERE YEAR = :year
          AND RESEAU IN (" . implode(',', $in) . ")
    ";

        if ($cumul) {
            $sql .= " AND SEMAINE <= :week ";
        } else {
            $sql .= " AND SEMAINE = :week ";
        }

        $rows = $this->mssqlLcs->executeQueryWithParams($sql, $params);

        return [
            'ca'    => (float) ($rows[0]->rfc_ca ?? 0),
            'marge' => (float) ($rows[0]->rfc_marge ?? 0),
        ];
    }
}
