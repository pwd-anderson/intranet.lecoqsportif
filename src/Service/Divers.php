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
    private ?MssqlManager $mssqlLcsInstance = null;
    private ?MssqlManager $mssqlSeiInstance = null;

    public function __construct(
        private MssqlManagerFactory $mssqlManagerFactory,
        private LoggerInterface $logger,
        private GraphMailer $graphMailer,
        private SqlFileLoader $sqlFileLoader,
        private \App\Repository\X3CollectionRepository $x3CollectionRepository,
        #[Autowire('%db.lcs%')]
        private string $dbLcs,
        #[Autowire('%db.lcs_sei%')]
        private string $dbLcsSei,
    )
    {
    }

    /**
     * Connexion MSSQL établie à la demande seulement (pas dans le constructeur) :
     * ouvrir une connexion PDO vers un serveur distant est coûteux (plusieurs
     * secondes), donc on évite de payer ce coût pour les méthodes qui n'ont pas
     * besoin de MSSQL (ex: getCollections() qui lit désormais le cache MySQL local).
     */
    private function mssqlLcs(): MssqlManager
    {
        return $this->mssqlLcsInstance ??= $this->mssqlManagerFactory->create($this->dbLcs);
    }

    private function mssqlSei(): MssqlManager
    {
        return $this->mssqlSeiInstance ??= $this->mssqlManagerFactory->create($this->dbLcsSei);
    }

    public function getFamilles(): array
    {
        $sql = "
            SELECT DISTINCT ITM.TCLCOD_0
            FROM X3_LCS.ITMMASTER ITM
            WHERE ITM.TCLCOD_0 IS NOT NULL
            ORDER BY ITM.TCLCOD_0
        ";

        $result = $this->mssqlSei()->executeQueryWithParams($sql);

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

        $exchangeRates = $this->mssqlSei()->executeQuery($query);

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

        $results = $this->mssqlSei()->executeQuery($query);

        if (empty($results)) {
            return [];
        }

        $processedResults = [];

        foreach ($results as $result) {
            $processedResults[$result->MARQUE][$result->ARTICLE] = $result->REF_FOURNISSEUR;
        }

        return $processedResults;
    }

    /**
     * Liste des collections X3 — lue depuis le cache local MySQL (table x3_collection,
     * alimentée par la commande `app:x3-collection:refresh`) pour éviter une requête
     * lente sur le SEI Cube à chaque chargement de page.
     * Fallback direct sur le SEI Cube si le cache local est vide (première utilisation
     * avant tout refresh, ou table pas encore synchronisée).
     */
    public function getCollections(): array
    {
        try {
            $cached = $this->x3CollectionRepository->findAllCodesDesc();

            if ($cached !== []) {
                return $cached;
            }

            $query = "
            SELECT DISTINCT
                C.SERIESCODE AS COLLECTION
            FROM SEI_X3_LCS.LCS_COLLECTION C
            WHERE C.SERIESCODE >= '2023-01-SS'
            ORDER BY C.SERIESCODE DESC
        ";

            $data = $this->mssqlSei()->executeQuery($query);

            return array_map(static function ($row) {
                return $row->COLLECTION;
            }, $data);

        } catch (\Exception $e) {
            $this->graphMailer->notifyError('❌ LCS Erreur Divers : Liste collections', $e);
            $this->logger->error('LCS Erreur Divers : Liste collections', ['exception' => $e]);

            return [];
        }
    }

    public function getTypes(): array
    {
        try {
            $query = "
            SELECT DISTINCT
                CASE
                    WHEN C.AGEGROUP = 'ADULT' THEN C.GENUSCODE
                    ELSE 'KIDS'
                END AS TYPE
            FROM SEI_X3_LCS.LCS_COLLECTION C
            ORDER BY TYPE ASC
        ";

            $data = $this->mssqlSei()->executeQuery($query);

            return array_map(static function ($row) {
                return $row->TYPE;
            }, $data);

        } catch (\Exception $e) {
            $this->graphMailer->notifyError('❌ LCS Erreur Divers : Liste types', $e);
            $this->logger->error('LCS Erreur Divers : Liste types', ['exception' => $e]);

            return [];
        }
    }

    public function getGenres(): array
    {
        try {
            $query = "
            SELECT DISTINCT ITM.TSICOD_0 AS GENRE
            FROM X3_LCS.ITMMASTER ITM
            WHERE ITM.TSICOD_0 IS NOT NULL
              AND ITM.TSICOD_0 <> ''
            ORDER BY ITM.TSICOD_0
        ";

            $data = $this->mssqlSei()->executeQuery($query);

            return array_map(static function ($row) {
                return $row->GENRE;
            }, $data);

        } catch (\Exception $e) {
            $this->graphMailer->notifyError('❌ LCS Erreur Stock : Liste genres', $e);
            $this->logger->error('LCS Erreur Stock : Liste genres', ['exception' => $e]);

            return [];
        }
    }

    public function getItemGroups(): array
    {
        try {
            $query = "
            SELECT DISTINCT
                C.ITEMGROUPCODE
            FROM SEI_X3_LCS.LCS_COLLECTION C
            WHERE C.ITEMGROUPCODE IS NOT NULL
              AND C.ITEMGROUPCODE <> ''
            ORDER BY C.ITEMGROUPCODE ASC
        ";

            $data = $this->mssqlSei()->executeQuery($query);

            return array_map(static function ($row) {
                return $row->ITEMGROUPCODE;
            }, $data);

        } catch (\Exception $e) {
            $this->graphMailer->notifyError('❌ LCS Erreur Divers : Liste item groups', $e);
            $this->logger->error('LCS Erreur Divers : Liste item groups', ['exception' => $e]);

            return [];
        }
    }

    public function getSites(): array
    {
        try {
            $data = $this->mssqlSei()->executeQuery("SELECT DISTINCT FCY.FCY_0 FROM X3_LCS.FACILITY FCY ORDER BY FCY.FCY_0");
            return array_map(fn($row) => $row->FCY_0, $data);
        } catch (\Exception $e) {
            $this->graphMailer->notifyError('❌ LCS Erreur Divers : Liste sites', $e);
            $this->logger->error('LCS Erreur Divers : Liste sites', ['exception' => $e]);
            return [];
        }
    }

    public function getSocietes(): array
    {
        try {
            $data = $this->mssqlSei()->executeQuery("SELECT DISTINCT CPY_0 FROM X3_LCS.COMPANY ORDER BY CPY_0");
            return array_map(fn($row) => $row->CPY_0, $data);
        } catch (\Exception $e) {
            $this->graphMailer->notifyError('❌ LCS Erreur Divers : Liste sociétés', $e);
            $this->logger->error('LCS Erreur Divers : Liste sociétés', ['exception' => $e]);
            return [];
        }
    }

    public function getTypesAccent(): array
    {
        try {
            $data = $this->mssqlSei()->executeQuery("SELECT DISTINCT TYP_0 FROM X3_LCS.GTYPACCENT ORDER BY TYP_0");
            return array_map(fn($row) => $row->TYP_0, $data);
        } catch (\Exception $e) {
            $this->graphMailer->notifyError('❌ LCS Erreur Divers : Liste types accent', $e);
            $this->logger->error('LCS Erreur Divers : Liste types accent', ['exception' => $e]);
            return [];
        }
    }

    public function getJournaux(): array
    {
        try {
            $data = $this->mssqlSei()->executeQuery("SELECT DISTINCT JOU_0 FROM X3_LCS.GJOURNAL ORDER BY JOU_0");
            return array_map(fn($row) => $row->JOU_0, $data);
        } catch (\Exception $e) {
            $this->graphMailer->notifyError('❌ LCS Erreur Divers : Liste journaux', $e);
            $this->logger->error('LCS Erreur Divers : Liste journaux', ['exception' => $e]);
            return [];
        }
    }

    public function getDevises(): array
    {
        try {
            $data = $this->mssqlSei()->executeQuery("SELECT DISTINCT CUR_0 FROM X3_LCS.TABCUR ORDER BY CUR_0");
            return array_map(fn($row) => $row->CUR_0, $data);
        } catch (\Exception $e) {
            $this->graphMailer->notifyError('❌ LCS Erreur Divers : Liste devises', $e);
            $this->logger->error('LCS Erreur Divers : Liste devises', ['exception' => $e]);
            return [];
        }
    }
}
