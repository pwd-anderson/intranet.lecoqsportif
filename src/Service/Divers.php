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

    /**
     * @param bool $arrayKeysCurrencies
     * @param string $currency
     * @param bool $sortedByReferenceCurrency
     * @return array
     */
    public function getExchangeRates(bool $arrayKeysCurrencies = true, string $currency = 'EUR', bool $sortedByReferenceCurrency = false): array
    {
        $currency = trim($currency);

        if ($currency === '') {
            return [];
        }

        // Sécurisation minimale pour éviter de casser la requête SQL
        $currency = str_replace("'", "''", $currency);

        $query = "
        SELECT
            CHANGE.CUR_0 AS DEVISE_REFERENCE,
            CHANGE.CURDEN_0 AS DEVISE,
            CHANGE.CHGRAT_0 AS COURS,
            CHANGE.CHGSTRDAT_0 AS DATE_COURS
        FROM X3_LCS.TABCHANGE AS CHANGE
        WHERE CHANGE.CUR_0 = '{$currency}'
          AND CHANGE.CHGSTRDAT_0 = (
              SELECT MAX(SUB_CHANGE.CHGSTRDAT_0)
              FROM X3_LCS.TABCHANGE AS SUB_CHANGE
              WHERE SUB_CHANGE.CUR_0 = '{$currency}'
                AND SUB_CHANGE.CHGTYP_0 = 1
          )
          AND CHANGE.CHGTYP_0 = 1
        ORDER BY CHANGE.CHGSTRDAT_0 DESC
    ";

        $exchangeRates = $this->mssqlSei->executeQuery($query);

        if (empty($exchangeRates)) {
            return [];
        }

        if (!$arrayKeysCurrencies) {
            return $exchangeRates;
        }

        $processedResults = [];

        foreach ($exchangeRates as $exchangeRate) {
            if ($sortedByReferenceCurrency) {
                $processedResults[$exchangeRate->DEVISE_REFERENCE][$exchangeRate->DEVISE] = $exchangeRate;
            } else {
                $processedResults[$exchangeRate->DEVISE] = $exchangeRate;
            }
        }

        return $processedResults;
    }

    public function getExchangeRatesValues(): array
    {
        $exchangeRates = $this->getExchangeRates(false, 'EUR');

        $rates = [
            'EUR' => 1.0,
        ];

        foreach ($exchangeRates as $exchangeRate) {
            $rates[$exchangeRate->DEVISE] = (float) $exchangeRate->COURS;
        }

        return $rates;
    }

    /**
     * @return array
     */
    public function getSupplierReferences(): array
    {
        $query = "
        SELECT
            ITP.ITMREFBPS_0 AS REF_FOURNISSEUR,
            ITM.ITMREF_0 AS ARTICLE,
            ITM.TSICOD_0 AS MARQUE
        FROM X3_LCS.ITMBPS AS ITP
        INNER JOIN X3_LCS.ITMMASTER AS ITM
            ON ITP.ITMREF_0 = ITM.ITMREF_0
        WHERE ITM.ITMSTA_0 IN (1, 3, 4)
    ";

        $results = $this->mssqlSei->executeQuery($query);

        if (empty($results)) {
            return [];
        }

        $processedResults = [];

        foreach ($results as $result) {
            $processedResults[$result->MARQUE][$result->ARTICLE] = $result->REF_FOURNISSEUR;
        }

        return $processedResults;
    }
}
