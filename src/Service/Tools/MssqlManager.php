<?php

namespace App\Service\Tools;

use PDO;
use PDOException;
use Psr\Log\LoggerInterface;

class MssqlManager
{
    private ?PDO $connection = null;

    /**
     * Désactive MARS (Multiple Active Result Sets) sur les connexions sqlsrv.
     *
     * Activé par défaut par le driver Microsoft, MARS impose un protocole de transfert
     * beaucoup plus bavard : mesuré sur le SEI Cube, 0,71 Mo/s avec, 7,48 Mo/s sans,
     * soit un facteur 10 sur toutes les requêtes qui ramènent beaucoup de lignes.
     * L'application n'ouvre jamais plusieurs jeux de résultats sur une même connexion.
     *
     * Sans effet sur dblib (FreeTDS), qui ignore ce paramètre et ne connaît pas MARS.
     * Une valeur déjà présente dans le DSN est respectée.
     */
    private function normalizeDsn(string $dsn): string
    {
        if (!str_starts_with($dsn, 'sqlsrv:')
            || stripos($dsn, 'MultipleActiveResultSets') !== false) {
            return $dsn;
        }

        return rtrim($dsn, ';') . ';MultipleActiveResultSets=false';
    }

    public function __construct(
        private string $dsn,
        private string $user,
        private string $password,
        private LoggerInterface $logger
    ) {
        try {
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,

            ];

            /**
             * Timeout spécifique SQL Server (REQUÊTE)
             * - existe uniquement avec le driver sqlsrv
             * - ignoré proprement avec dblib
             */
            if (defined('PDO::SQLSRV_ATTR_QUERY_TIMEOUT')) {
                $options[PDO::SQLSRV_ATTR_QUERY_TIMEOUT] = 300;
            }

            $this->connection = new PDO(
                $this->normalizeDsn($this->dsn),
                $this->user,
                $this->password,
                $options
            );

        } catch (PDOException $e) {
            $this->logger->error(
                'Connection to MSSQL failed',
                ['exception' => $e, 'dsn' => $this->dsn]
            );
        }
    }

    public function executeQuery(string $query): array
    {
        try {
            $stmt = $this->connection?->query($query);
            return $stmt ? $stmt->fetchAll(PDO::FETCH_OBJ) : [];
        } catch (PDOException $e) {
            $this->logger->error("Query failed: {$e->getMessage()}", ['query' => $query]);
            return [];
        }
    }

    /**
     * Lecture ligne par ligne, pour les gros volumes. Contrairement aux autres méthodes,
     * les erreurs remontent à l'appelant au lieu de renvoyer un résultat vide.
     *
     * @return \Generator<array<string, mixed>>
     */
    public function iterateQuery(string $query): \Generator
    {
        if ($this->connection === null) {
            throw new \RuntimeException('Connexion MSSQL indisponible.');
        }

        $stmt = $this->connection->query($query);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            yield $row;
        }
    }

    public function executeQueryWithParams(string $query, array $params = []): array
    {
        try {

            $stmt = $this->connection->prepare($query);

            foreach ($params as $key => $value) {

                if ($value === null) {
                    $stmt->bindValue(':' . $key, null, PDO::PARAM_NULL);
                } elseif (is_int($value)) {
                    $stmt->bindValue(':' . $key, $value, PDO::PARAM_INT);
                } else {
                    $stmt->bindValue(':' . $key, $value, PDO::PARAM_STR);
                }
            }

            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {

            $this->logger->error("Query with params failed: {$e->getMessage()}", [
                'query' => $query,
                'params' => $params,
            ]);

            return [];
        }
    }

    public function insertData(string $query): int
    {
        try {
            $stmt = $this->connection?->prepare($query);
            $stmt?->execute();
            return $stmt?->rowCount() ?? 0;
        } catch (PDOException $e) {
            $this->logger->error("Insert failed: {$e->getMessage()}", ['query' => $query]);
            return 0;
        }
    }

    public function executeQueryAsArray(string $query): array
    {
        try {
            $stmt = $this->connection?->query($query);
            return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        } catch (PDOException $e) {
            $this->logger->error("Query as array failed: {$e->getMessage()}", ['query' => $query]);
            return [];
        }
    }

    public function executeQueryAsRowArray(string $query): array
    {
        try {
            $stmt = $this->connection?->query($query);
            $results = [];
            while ($row = $stmt?->fetch(PDO::FETCH_OBJ)) {
                $results[] = array_values((array) $row);
            }
            return $results;
        } catch (PDOException $e) {
            $this->logger->error("Query as row array failed: {$e->getMessage()}", ['query' => $query]);
            return [];
        }
    }

    public function executeMultiStatement(string $sql): array
    {
        try {
            if (!$this->connection) {
                return [];
            }

            $stmt = $this->connection->prepare($sql);
            $stmt->execute();

            $result = [];

            /**
             * On consomme TOUS les resultsets intermédiaires
             * (SET, SELECT INTO, etc.)
             */
            do {
                if ($stmt->columnCount() > 0) {
                    $rows = $stmt->fetchAll(PDO::FETCH_OBJ);

                    if (!empty($rows)) {
                        $result = $rows;
                    }
                }
            } while ($stmt->nextRowset());

            return $result;

        } catch (PDOException $e) {
            $this->logger->error(
                'Multi-statement SQL failed',
                [
                    'error' => $e->getMessage(),
                    'sql_preview' => substr($sql, 0, 500),
                ]
            );

            return [];
        }
    }

    public function executeDelete(string $query, array $params = []): int
    {
        try {
            $stmt = $this->connection?->prepare($query);
            $stmt?->execute($params);
            return $stmt?->rowCount() ?? 0;
        } catch (PDOException $e) {
            $this->logger->error("Delete failed: {$e->getMessage()}", ['query' => $query, 'params' => $params]);
            return 0;
        }
    }

    public function getConnection(): ?PDO
    {
        return $this->connection;
    }
}
