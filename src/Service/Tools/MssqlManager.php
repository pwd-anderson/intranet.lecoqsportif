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
            $this->connection = new PDO($this->dsn, $this->user, $this->password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]);
        } catch (PDOException $e) {
            $this->logger->error('Connection to MSSQL failed: ' . $e->getMessage(), ['exception' => $e]);
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

    public function getConnection(): ?PDO
    {
        return $this->connection;
    }
}
