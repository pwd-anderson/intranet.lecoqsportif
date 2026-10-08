<?php

namespace App\Service\Oc;

use App\Factory\MssqlManagerFactory;
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
final class OcX3Lookup
{
    public const MIN_CHARS = 2;

    private MssqlManager $mssqlSei;

    public function __construct(
        MssqlManagerFactory $mssqlManagerFactory,
        private readonly LoggerInterface $logger,
        private readonly GraphMailer $graphMailer,
        private readonly SqlFileLoader $sqlFileLoader,
        #[Autowire('%db.lcs_sei%')]
        string $dbLcsSei,
    ) {
        $this->mssqlSei = $mssqlManagerFactory->create($dbLcsSei);
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
            $rows = $this->mssqlSei->executeQueryWithParams($query, ['code' => $customerCode]);

            return $rows[0] ?? null;
        } catch (\Throwable $e) {
            $this->graphMailer->notifyError('❌ LCS Erreur OC : remise client', $e);
            $this->logger->error('LCS Erreur OC : remise client', ['exception' => $e]);

            return null;
        }
    }

    /**
     * Code tarif (groupe tarifaire du client), tarif et MSRP d'un article parent pour un client, sur la
     * collection la plus récente de l'article. Null si l'article ou le client est introuvable.
     *
     * @return array{ARTICLE:string,MODELE:?string,COLLECTION:?string,CODE_TARIF:?string,DEVISE:?string,TARIF:float|int|string|null,MSRP:float|int|string|null}|null
     */
    public function getArticlePrices(string $customerCode, string $articleCode): ?array
    {
        $customerCode = trim($customerCode);
        $articleCode = trim($articleCode);
        if ($customerCode === '' || $articleCode === '') {
            return null;
        }

        try {
            $query = $this->sqlFileLoader->load('Sei/oc_article_prices.sql');
            $rows = $this->mssqlSei->executeQueryWithParams($query, [
                'customer' => $customerCode,
                'article' => $articleCode,
                'article_cr' => $articleCode,
            ]);

            return $rows[0] ?? null;
        } catch (\Throwable $e) {
            $this->graphMailer->notifyError('❌ LCS Erreur OC : tarifs article', $e);
            $this->logger->error('LCS Erreur OC : tarifs article', ['exception' => $e]);

            return null;
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

            return $this->mssqlSei->executeQueryWithParams($query, array_fill_keys($paramNames, $like));
        } catch (\Throwable $e) {
            $this->graphMailer->notifyError('❌ LCS Erreur OC : ' . $label, $e);
            $this->logger->error('LCS Erreur OC : ' . $label, ['exception' => $e]);

            return [];
        }
    }
}
