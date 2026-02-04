<?php

namespace App\Service\Tools;

use PDO;
use PDOException;
use Psr\Log\LoggerInterface;

class MssqlManager
{
    private ?PDO $connection = null;

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
                $this->dsn,
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
            var_dump($e->getMessage());
            $this->logger->error("Query failed: {$e->getMessage()}", ['query' => $query]);
            return [];
        }
    }

    public function executeQueryWithParams(string $query, array $params = []): array
    {
        try {
            $stmt = $this->connection?->prepare($query);
            if ($stmt && $stmt->execute($params)) {
                return $stmt->fetchAll(PDO::FETCH_OBJ);
            }

            return [];

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

    public function getConnection(): ?PDO
    {
        return $this->connection;
    }
}
