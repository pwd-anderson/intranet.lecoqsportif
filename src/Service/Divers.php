<?php

namespace App\Service;

use App\Factory\MssqlManagerFactory;
use App\Infrastructure\Sql\SqlFileLoader;
use App\Service\Tools\GraphMailer;
use App\Service\Tools\MssqlManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class Divers
{
    private MssqlManager $mssqlLcs;
    private MssqlManager $mssqlSei;

    public function __construct(
        private MssqlManagerFactory $mssqlManagerFactory,
        private LoggerInterface $logger,
        private GraphMailer $graphMailer,
        private SqlFileLoader $sqlFileLoader,
        #[Autowire('%db.lcs%')]
        string $dbLcs,
        #[Autowire('%db.lcs_sei%')]
        string $dbLcsSei,
    )
    {
        $this->mssqlLcs = $this->mssqlManagerFactory->create($dbLcs);
        $this->mssqlSei = $this->mssqlManagerFactory->create($dbLcsSei);
    }

    public function getFamilles(): array
    {
        $sql = "
            SELECT DISTINCT ITM.TCLCOD_0
            FROM X3_LCS.ITMMASTER ITM
            WHERE ITM.TCLCOD_0 IS NOT NULL
            ORDER BY ITM.TCLCOD_0
        ";

        $result = $this->mssqlSei->executeQueryWithParams($sql);

        // On transforme en tableau simple
        return array_map(
            fn($row) => $row['TCLCOD_0'],
            $result
        );
    }
}
