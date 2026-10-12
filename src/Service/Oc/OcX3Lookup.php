<?php

namespace App\Service\Oc;

use App\Infrastructure\Sql\SqlFileLoader;
use App\Service\Tools\GraphMailer;
use App\Service\Tools\MssqlManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Lectures X3 (X3_LCS via le cube SEI) du module OC : clients, puis articles et tarifs existants.
 * Les SQL vivent dans src/Infrastructure/Sql/Sei/oc_*.sql ; les valeurs saisies sont toujours liées
 * en paramètres.
 */
class OcX3Lookup
{
    public const MIN_CHARS = 2;

    private ?MssqlManager $mssql = null;

    /** @var array{dsn:string,user:string,password:string} */
    private array $connection;

    /**
     * @param array<string,array{dsn:string,user:string,password:string}> $connections paramètre mssql.connections
     */
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly GraphMailer $graphMailer,
        private readonly SqlFileLoader $sqlFileLoader,
        #[Autowire('%mssql.connections%')]
        array $connections,
        #[Autowire('%db.lcs_sei%')]
        string $dbLcsSei,
    ) {
        $this->connection = $connections[$dbLcsSei] ?? throw new \InvalidArgumentException("Connexion MSSQL inconnue : '$dbLcsSei'");
    }

    /**
     * Connexion au SEI Cube en UTF-8, ouverte à la PREMIÈRE requête seulement (ouvrir une connexion coûte
     * jusqu'à ~15 s : les pages qui n'interrogent pas X3 ne doivent pas la payer). Le pilote dblib travaille par
     * défaut en ISO-8859-1 : sans charset=UTF-8, un « ° » dans une désignation arrivait en octet invalide pour
     * MySQL (enregistrement refusé) et tout caractère hors Latin-1 était remplacé par « ? ».
     */
    private function db(): MssqlManager
    {
        if ($this->mssql === null) {
            $dsn = $this->connection['dsn'];
            if (str_starts_with($dsn, 'dblib:') && stripos($dsn, 'charset=') === false) {
                $dsn = rtrim($dsn, ';') . ';charset=UTF-8';
            }
            $this->mssql = new MssqlManager($dsn, $this->connection['user'], $this->connection['password'], $this->logger);
        }

        return $this->mssql;
    }

    /**
     * Clients actifs dont le code ou le nom contient la saisie (20 résultats maximum).
     *
     * @return list<array{CODE_CLIENT:string,NOM_CLIENT:string,DEVISE:string,VILLE:?string,PAYS:?string}>
     */
    public function searchCustomers(string $input): array
    {
        return $this->search('Sei/oc_customer_search.sql', $input, ['q_code', 'q_name'], 'recherche client');
    }

    /**
     * Articles parents (sans code variant) dont le code ou la désignation contient la saisie
     * (20 résultats maximum).
     *
     * @return list<array{ARTICLE:string,DESIGNATION:?string}>
     */
    public function searchArticles(string $input): array
    {
        return $this->search('Sei/oc_article_search.sql', $input, ['q_ref', 'q_des'], 'recherche article');
    }

    /**
     * Remise client (%) du type REMCLI, ou null si le client n'en a pas.
     *
     * @return array{REMISE:float|string,DATE_DEBUT:?string,DATE_FIN:?string}|null
     */
    public function getCustomerDiscount(string $customerCode): ?array
    {
        $customerCode = trim($customerCode);
        if ($customerCode === '') {
            return null;
        }

        try {
            $query = $this->sqlFileLoader->load('Sei/oc_customer_discount.sql');
            $rows = $this->db()->executeQueryWithParams($query, ['code' => $customerCode]);

            return $rows[0] ?? null;
        } catch (\Throwable $e) {
            $this->graphMailer->notifyError('❌ LCS Erreur OC : remise client', $e);
            $this->logger->error('LCS Erreur OC : remise client', ['exception' => $e]);

            return null;
        }
    }

    /**
     * Un client actif par son code exact (relecture serveur à l'enregistrement), ou null s'il est introuvable.
     * Un résultat null peut aussi signifier « X3 indisponible » (MssqlManager avale les erreurs SQL).
     *
     * @return array{CODE_CLIENT:string,NOM_CLIENT:string,DEVISE:?string,CODE_TARIF:?string}|null
     */
    public function getCustomer(string $customerCode): ?array
    {
        $customerCode = trim($customerCode);
        if ($customerCode === '') {
            return null;
        }

        try {
            $rows = $this->db()->executeQueryWithParams($this->sqlFileLoader->load('Sei/oc_customer_get.sql'), ['code' => $customerCode]);

            return isset($rows[0]) ? (array) $rows[0] : null;
        } catch (\Throwable $e) {
            $this->graphMailer->notifyError('❌ LCS Erreur OC : lecture client', $e);
            $this->logger->error('LCS Erreur OC : lecture client', ['exception' => $e]);

            return null;
        }
    }

    /**
     * Adresse par défaut du client (PDF de l'offre), ou null si introuvable / X3 indisponible : le PDF s'en passe.
     *
     * @return array{LIGNE_1:?string,LIGNE_2:?string,LIGNE_3:?string,CODE_POSTAL:?string,VILLE:?string,PAYS:?string}|null
     */
    public function getCustomerAddress(string $customerCode): ?array
    {
        $customerCode = trim($customerCode);
        if ($customerCode === '') {
            return null;
        }

        try {
            $rows = $this->db()->executeQueryWithParams($this->sqlFileLoader->load('Sei/oc_customer_address.sql'), ['code' => $customerCode]);

            return isset($rows[0]) ? (array) $rows[0] : null;
        } catch (\Throwable $e) {
            $this->logger->warning('LCS OC : adresse client indisponible pour le PDF', ['customer' => $customerCode, 'exception' => $e]);

            return null;
        }
    }

    /**
     * Code tarif (groupe tarifaire du client), tarif et MSRP d'un article parent pour un client, sur la
     * collection la plus récente de l'article. Null si l'article ou le client est introuvable.
     *
     * @return array{ARTICLE:string,DESIGNATION:?string,MODELE:?string,COLLECTION:?string,CODE_TARIF:?string,DEVISE:?string,TARIF:float|int|string|null,MSRP:float|int|string|null}|null
     */
    public function getArticlePrices(string $customerCode, string $articleCode): ?array
    {
        return $this->getArticlesPrices($customerCode, [$articleCode])[trim($articleCode)] ?? null;
    }

    /**
     * Même lecture pour plusieurs articles en UNE requête (relecture serveur à l'enregistrement d'une OC).
     * Les articles introuvables sont absents du résultat.
     *
     * @param list<string> $articleCodes
     *
     * @return array<string, array<string,mixed>> lignes indexées par code article
     */
    public function getArticlesPrices(string $customerCode, array $articleCodes): array
    {
        $customerCode = trim($customerCode);
        $articleCodes = array_values(array_unique(array_filter(array_map('trim', $articleCodes), static fn (string $a): bool => $a !== '')));
        if ($customerCode === '' || $articleCodes === []) {
            return [];
        }

        // Deux jeux de paramètres nommés distincts (CTE et requête principale) pour la même liste d'articles.
        $params = ['customer' => $customerCode];
        $inCr = [];
        $inMain = [];
        foreach ($articleCodes as $i => $code) {
            $params['a' . $i] = $code;
            $params['b' . $i] = $code;
            $inCr[] = ':a' . $i;
            $inMain[] = ':b' . $i;
        }

        try {
            $query = str_replace(
                ['{{ARTICLES_CR}}', '{{ARTICLES}}'],
                [implode(', ', $inCr), implode(', ', $inMain)],
                $this->sqlFileLoader->load('Sei/oc_article_prices.sql')
            );

            $result = [];
            foreach ($this->db()->executeQueryWithParams($query, $params) as $row) {
                $row = (array) $row;
                $result[trim((string) $row['ARTICLE'])] = $row;
            }

            return $result;
        } catch (\Throwable $e) {
            $this->graphMailer->notifyError('❌ LCS Erreur OC : tarifs articles', $e);
            $this->logger->error('LCS Erreur OC : tarifs articles', ['exception' => $e]);

            return [];
        }
    }

    /**
     * Recherche "contient" sur deux colonnes : même saisie liée aux deux paramètres LIKE.
     *
     * @param list<string> $paramNames
     */
    private function search(string $sqlFile, string $input, array $paramNames, string $label): array
    {
        $input = trim($input);
        if (mb_strlen($input) < self::MIN_CHARS) {
            return [];
        }

        // Les caractères joker de LIKE saisis par l'utilisateur sont neutralisés.
        $like = '%' . strtr(mb_strtoupper($input), ['[' => '[[]', '%' => '[%]', '_' => '[_]']) . '%';

        try {
            $query = $this->sqlFileLoader->load($sqlFile);

            return $this->db()->executeQueryWithParams($query, array_fill_keys($paramNames, $like));
        } catch (\Throwable $e) {
            $this->graphMailer->notifyError('❌ LCS Erreur OC : ' . $label, $e);
            $this->logger->error('LCS Erreur OC : ' . $label, ['exception' => $e]);

            return [];
        }
    }
}
