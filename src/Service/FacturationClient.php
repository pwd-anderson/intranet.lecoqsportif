<?php

namespace App\Service;

use App\Factory\MssqlManagerFactory;
use App\Service\Tools\GraphMailer;
use App\Service\Tools\MssqlManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Facturation client par plage glissante (0-12 / 12-18 / 18-24 mois).
 *
 * Alimente MASTER_TABLES.INTRANET_FACTURATION_CLIENT, qui remplace les trois colonnes
 * FACTURE_*_MOIS calculees dans la vue PRODXXX_CLIENT_INTRANE. Celle-ci lisait les tables
 * X3 brutes, qui ne contiennent des factures qu'a partir d'avril 2026 : les plages 12-18
 * et 18-24 mois y etaient systematiquement vides. Le cube porte l'historique complet.
 *
 * Perimetre identique a l'export CA / Marge (cf. CaMargeX3) : produits du perimetre BOH,
 * familles FTW/HDW/APL, societes LCSI, reseau principal renseigne. Le B2C n'est
 * volontairement PAS exclu ici, contrairement au CA / Marge.
 *
 * Les fenetres etant glissantes par rapport a la date du jour, la table doit etre
 * rafraichie quotidiennement : DDL dans Sei/create_table_facturation_client.sql.
 */
class FacturationClient
{
    private MssqlManager $mssql;
    private bool $isDev;

    public function __construct(
        MssqlManagerFactory $mssqlManagerFactory,
        private LoggerInterface $logger,
        private GraphMailer $graphMailer,
        #[Autowire('%db.lcs_sei%')]
        string $dbLcsSei,
        #[Autowire('%kernel.environment%')]
        string $environment,
    ) {
        $this->mssql = $mssqlManagerFactory->create($dbLcsSei);
        $this->isDev = ($environment === 'dev');
    }

    public function table(): string
    {
        return 'MASTER_TABLES.INTRANET_FACTURATION_CLIENT' . ($this->isDev ? '_DEV' : '');
    }

    /**
     * Vide la table et la recalcule entierement. Le volume est faible (~1 500 lignes)
     * et les fenetres sont glissantes : un recalcul complet est plus simple et plus sur
     * qu'un differentiel.
     *
     * @return int nombre de lignes inserees
     */
    public function refresh(): int
    {
        try {
            $table = $this->table();
            $this->mssql->executeDelete("DELETE FROM {$table}");

            $lignes = $this->mssql->insertData("
                INSERT INTO {$table}
                    (code_client, facture_0_12_mois, facture_12_18_mois, facture_18_24_mois, date_maj)
                SELECT
                    I.CUSTOMERNO,

                    CASE WHEN MAX(CASE WHEN I.DOCUMENTPOSTINGDATE >= DATEADD(MONTH, -12, CAST(GETDATE() AS DATE))
                                       THEN 1 ELSE 0 END) = 1
                         THEN 'Oui' ELSE 'Non' END,

                    CASE WHEN MAX(CASE WHEN I.DOCUMENTPOSTINGDATE >= DATEADD(MONTH, -18, CAST(GETDATE() AS DATE))
                                        AND I.DOCUMENTPOSTINGDATE <  DATEADD(MONTH, -12, CAST(GETDATE() AS DATE))
                                       THEN 1 ELSE 0 END) = 1
                         THEN 'Oui' ELSE 'Non' END,

                    CASE WHEN MAX(CASE WHEN I.DOCUMENTPOSTINGDATE >= DATEADD(MONTH, -24, CAST(GETDATE() AS DATE))
                                        AND I.DOCUMENTPOSTINGDATE <  DATEADD(MONTH, -18, CAST(GETDATE() AS DATE))
                                       THEN 1 ELSE 0 END) = 1
                         THEN 'Oui' ELSE 'Non' END,

                    GETDATE()

                FROM SEI_X3_LCS.CONSO_INVOICES I

                    LEFT JOIN SEI_X3_LCS.LCS_COLLECTION C
                        ON  I.ITEMNO   = C.ITEM_ID
                        AND I.SERIESNO = C.SERIESCODE

                    LEFT JOIN SEI_X3_LCS.LCS_CUSTOMER CUST
                        ON  I.COMPANYCODE = CUST.COMPANY_ID
                        AND I.CUSTOMERNO  = CUST.CUSTOMER_ID

                WHERE
                    I.ISBOHPERIMETERPRODUCT = 1
                    AND (
                        I.DOCUMENTTYPE IN ('INVOICE', 'CREDITMEMO')
                        OR (I.DOCUMENTTYPE = 'ORDER' AND I.ORDERSTATUS = 3 AND I.DLVQTY > 0)
                    )
                    AND C.ITEMFAMILYCODE IN ('FTW', 'HDW', 'APL')
                    AND I.COMPANYCODE IN ('LCSI BV', 'LCSI')
                    AND I.DOCUMENTPOSTINGDATE >= DATEADD(MONTH, -24, CAST(GETDATE() AS DATE))
                    AND CUST.MAINNETWORK IS NOT NULL

                GROUP BY I.CUSTOMERNO
            ");

            // insertData() avale les PDOException et renvoie 0 : sans ce garde-fou, une
            // table absente ou un cube injoignable passeraient pour un succes a vide,
            // et la stat afficherait "Non" partout sans que personne ne soit alerte.
            if ($lignes === 0) {
                throw new \RuntimeException(
                    "Aucune ligne inseree dans {$table}. La table existe-t-elle sur le SEI Cube "
                    . '(cf. Sei/create_table_facturation_client.sql) ? Voir le log pour l\'erreur SQL.'
                );
            }

            return $lignes;

        } catch (\Exception $e) {
            $this->graphMailer->notifyError('❌ LCS Erreur Facturation Client : rafraichissement', $e);
            $this->logger->error('LCS Erreur Facturation Client : rafraichissement', ['exception' => $e]);

            throw $e;
        }
    }
}
