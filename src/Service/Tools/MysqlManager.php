<?php

namespace App\Service\Tools;

use PDO;
use PDOException;
use Psr\Log\LoggerInterface;

class MysqlManager
{
    /** @var \PDO */
    private $connection;

    /** @var LoggerInterface */
    private $logger;

    public function __construct($dsn, $user, $pass, LoggerInterface $logger)
    {
        $this->logger = $logger;

        try {

            $this->connection = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
            ]);

        } catch (PDOException $e) {
            $this->logger->error('Erreur connexion MySQL : ' . $e->getMessage(), [$e]);
            $this->connection = null;
        }
    }

    /**
     * Exécute une requête SQL et retourne un tableau d’objets
     */
    public function executeQuery($sql, array $params = [])
    {
        if (!$this->connection) {
            return [];
        }

        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            $this->logger->error('Erreur requête MySQL : ' . $e->getMessage(), [$e]);
            return [];
        }
    }

    /**
     * Exécute un INSERT / UPDATE et retourne nb de lignes affectées
     */
    public function executeUpdate($sql, array $params = [])
    {
        if (!$this->connection) {
            return 0;
        }

        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt->rowCount();
        } catch (PDOException $e) {
            $this->logger->error('Erreur update MySQL : ' . $e->getMessage(), [$e]);
            return 0;
        }
    }

    public function getConnection()
    {
        return $this->connection;
    }
}
