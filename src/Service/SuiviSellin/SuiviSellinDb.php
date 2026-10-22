<?php

namespace App\Service\SuiviSellin;

use PDO;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Connexion PDO au SEI Cube pour le module Suivi Sellin global.
 *
 * Connexion propre (et non MssqlManager) pour deux raisons déjà rencontrées ailleurs : le pilote dblib travaille en
 * ISO-8859-1 par défaut (un « é » ou un « ° » ressortirait déformé : on force charset=UTF-8), et MssqlManager avale
 * les erreurs SQL (résultat vide au lieu d'une exception : un onglet vide passerait pour « aucune donnée »).
 * Ouverte à la première requête seulement (ouvrir une connexion coûte jusqu'à ~15 s).
 */
final class SuiviSellinDb
{
    private ?PDO $pdo = null;

    /** @var array{dsn:string,user:string,password:string} */
    private array $connection;

    /**
     * @param array<string,array{dsn:string,user:string,password:string}> $connections paramètre mssql.connections
     */
    public function __construct(
        #[Autowire('%mssql.connections%')]
        array $connections,
        #[Autowire('%db.lcs_sei%')]
        string $connectionName,
    ) {
        $this->connection = $connections[$connectionName];
    }

    public function pdo(): PDO
    {
        if ($this->pdo === null) {
            $dsn = $this->connection['dsn'];
            $options = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION];
            if (str_starts_with($dsn, 'dblib:')) {
                // FreeTDS : ISO-8859-1 par défaut, délai de connexion/requête en secondes.
                if (stripos($dsn, 'charset=') === false) {
                    $dsn = rtrim($dsn, ';') . ';charset=UTF-8';
                }
                $options[PDO::ATTR_TIMEOUT] = 300;
            } elseif (str_starts_with($dsn, 'sqlsrv:')) {
                // Pilote Microsoft (préprod / prod) : UTF-8 d'office ; PDO::ATTR_TIMEOUT n'existe pas (« unsupported attribute »),
                // le délai de requête a son propre attribut. MARS désactivé comme dans MssqlManager (facteur 10 sur les
                // requêtes volumineuses) : on ne lance donc jamais une requête tant que la précédente n'est pas lue en entier.
                if (stripos($dsn, 'MultipleActiveResultSets') === false) {
                    $dsn = rtrim($dsn, ';') . ';MultipleActiveResultSets=false';
                }
                if (defined('PDO::SQLSRV_ATTR_QUERY_TIMEOUT')) {
                    $options[PDO::SQLSRV_ATTR_QUERY_TIMEOUT] = 300;
                }
            }
            $this->pdo = new PDO($dsn, $this->connection['user'], $this->connection['password'], $options);
        }

        return $this->pdo;
    }

    /**
     * Parcourt le résultat ligne à ligne (sans tout charger en mémoire : le carnet fait ~490 000 lignes).
     *
     * @return \Generator<int, array<string, mixed>>
     */
    public function iterate(string $sql): \Generator
    {
        $stmt = $this->pdo()->query($sql);
        while (($row = $stmt->fetch(PDO::FETCH_ASSOC)) !== false) {
            yield $row;
        }
    }
}
