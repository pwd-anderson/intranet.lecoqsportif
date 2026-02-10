<?php

namespace App\Service\kpi;

use App\Factory\MssqlManagerFactory;
use App\Service\Tools\GraphMailer;
use App\Service\Tools\Helpers;
use App\Service\Tools\MssqlManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class RfcByBoutique
{
    private MssqlManager $mssqlLcs;
    public function __construct(
        private MssqlManagerFactory $mssqlManagerFactory,
        private LoggerInterface $logger,
        private GraphMailer $graphMailer,
        private Helpers $helpers,
        #[Autowire('%db.lcs%')]
        string $dbLcs,
    ) {
        $this->mssqlLcs = $this->mssqlManagerFactory->create($dbLcs);
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
        WHERE YEAR = {$year}
        AND RESEAU = 'CONCEPT STORE'
    ";

        if ($cumul) {
            $sql .= " AND SEMAINE <= {$week} ";
        } else {
            $sql .= " AND SEMAINE = {$week} ";
        }

        $sql .= "
        GROUP BY [CODE MAG]
    ";

        // 🔥 Exécution directe
        $rows = $this->mssqlLcs->executeQuery($sql);

        $out = [];

        foreach ($rows as $r) {
            if (empty($r->code)) {
                continue;
            }

            $out[(string) $r->code] = [
                'ca'    => (float) ($r->rfc_ca ?? 0),     // déjà en K€
                'marge' => (float) ($r->rfc_marge ?? 0),  // déjà en K€
            ];
        }

        return $out;
    }

    /**
     * RFC : retourne une valeur en K€
     */
    public function fetchRfc(
        int $year,
        int $week,
        bool $cumul,
        array $reseaux,
        string $columnName
    ): float {
        // Sécurité : colonne RFC non définie
        if (trim($columnName) === '') {
            return 0.0;
        }

        // Sécurité minimale : pas de réseau
        if (empty($reseaux)) {
            return 0.0;
        }

        // Construction du IN ('CS','FO',...)
        $reseauxSql = implode(
            ',',
            array_map(
                fn(string $r) => "'" . str_replace("'", "''", $r) . "'",
                $reseaux
            )
        );

        $sql = "
        SELECT
            SUM([$columnName]) AS rfc
        FROM UCD..RFC_AVEC_MARGE
        WHERE
            YEAR = {$year}
            AND RESEAU IN ({$reseauxSql})
    ";

        if ($cumul) {
            $sql .= " AND SEMAINE <= {$week} ";
        } else {
            $sql .= " AND SEMAINE = {$week} ";
        }

        try {
            $rows = $this->mssqlLcs->executeQuery($sql);

            return isset($rows[0]->rfc)
                ? (float) $rows[0]->rfc   // RFC déjà en K€
                : 0.0;

        } catch (\Throwable $e) {
            // Si la colonne RFC marge n'existe pas → on ne casse pas le slide
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
        // Sécurité : aucun réseau
        if (empty($reseaux)) {
            return ['ca' => 0.0, 'marge' => 0.0];
        }

        // Construction du IN ('CONCEPT STORE','FACTORY OUTLET',...)
        $reseauxSql = implode(
            ',',
            array_map(
                fn(string $r) => "'" . str_replace("'", "''", $r) . "'",
                $reseaux
            )
        );

        $sql = "
        SELECT
            SUM([RFC WEEK]) AS rfc_ca,
            SUM([RFC WEEK Marge Valeur]) AS rfc_marge
        FROM UCD..RFC_AVEC_MARGE
        WHERE
            YEAR = {$year}
            AND RESEAU IN ({$reseauxSql})
    ";

        if ($cumul) {
            $sql .= " AND SEMAINE <= {$week} ";
        } else {
            $sql .= " AND SEMAINE = {$week} ";
        }

        try {
            $rows = $this->mssqlLcs->executeQuery($sql);

            return [
                'ca'    => isset($rows[0]->rfc_ca) ? (float) $rows[0]->rfc_ca : 0.0,
                'marge' => isset($rows[0]->rfc_marge) ? (float) $rows[0]->rfc_marge : 0.0,
            ];
        } catch (\Throwable $e) {
            // On ne casse jamais le KPI
            $this->logger->warning('RFC aggregate fetch failed: ' . $e->getMessage());
            return ['ca' => 0.0, 'marge' => 0.0];
        }
    }
}
