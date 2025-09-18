<?php

namespace App\Factory;

use App\Service\Tools\MssqlManager;
use Psr\Log\LoggerInterface;

class MssqlManagerFactory
{
    public function __construct(
        private LoggerInterface $logger,
        private array $connections // injecté depuis services.yaml
    ) {}

    public function create(string $connectionName = 'made2deseign'): MssqlManager
    {
        if (!isset($this->connections[$connectionName])) {
            throw new \InvalidArgumentException("Connexion MSSQL inconnue : '$connectionName'");
        }

        $conf = $this->connections[$connectionName];

        return new MssqlManager(
            dsn: $conf['dsn'],
            user: $conf['user'],
            password: $conf['password'],
            logger: $this->logger
        );
    }
}
