<?php

namespace App\Service;

use App\Factory\MssqlManagerFactory;
use App\Infrastructure\Sql\SqlFileLoader;
use App\Service\Tools\GraphMailer;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mime\Email;
use Psr\Log\LoggerInterface;
use App\Service\Tools\MssqlManager;

class Sales
{
    private MssqlManager $mssqlLcs;

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
    }

    public function getLivraisonsNonFacturees(): array
    {
        try {
            $query = "select
                        s.CompanyCode
                        ,c.No_
                        ,c.Name
                        ,s.[Document No_]
                        ,s.[Shipment Date]
                        ,s.No_
                        , s.[Variant Code]
                        ,SUM(S.[Qty_ Shipped Not Invoiced]) QtyShippedNotInvoiced,
                        sum(s.[Shipped Not Invoiced]) as montant_ttc, sum(s.[Shipped Not Invoiced HT]) as montant_ht
                        FROM [DB_Datalake].[nav].[Sales Line] s
                        left join [DB_Datalake].[nav].[Customer] as c on s.CompanyCode = c.CompanyCode and s.[Bill-to Customer No_] = c.No_
                        where s.[Qty_ Shipped Not Invoiced] <> 0
                        and year(s.[Created Date Time]) > 2016
                        GROUP BY s.No_, s.[Variant Code],s.[Document No_],s.CompanyCode,c.No_,c.Name,s.[Shipment Date]";

            $data = $this->mssqlLcs->executeQuery($query);
            return $data;

        } catch (\Exception $e) {
            $this->graphMailer->notifyError('❌ LCS Erreur Sales : Récupération de données Livraison non facturées', $e);
            $this->logger->error('LCS Erreur Sales : Récupération de données Livraison non facturées', ['exception' => $e]);
        }
    }

    public function getBacklogClients(): array
    {
        try {

            $query = "SELECT
                    s.CompanyCode,
                    c.No_ as code_client,
                    c.Name as nom_client,
                    s.No_ as article,
                    s.[Variant Code] as variant_code,
                    i.[Item Family Code] as famille,
                    i.[Last Series No_] as collection,
                    s.[Document No_] as no_commande,
                    SH.[Order Date] as date_commande,
                    s.[Requested Delivery Date] as date_livraison,
                    s.[Outstanding Quantity] as quantite,
                    s.[Outstanding Amount (LCY) HT] as montant_ht_eur,
                    0 as stock_reel

                FROM
                [DB_Datalake].[nav].[Sales Line] s
                LEFT JOIN DB_Datalake.[nav].[Sales Header] SH
                    ON SH.CompanyCode = S.CompanyCode
                    AND SH.No_ = S.[Document No_]
                    AND SH.[Document Type] = S.[Document Type]
                LEFT JOIN [DB_Datalake].[nav].[Customer] c
                    ON s.CompanyCode = c.CompanyCode
                    AND s.[Bill-to Customer No_] = c.No_
                LEFT JOIN [DB_Datalake].[nav lcsi bv].[Item] i
                    ON s.No_ = i.No_
                WHERE
                s.No_ <> ''
                AND s.[Type] = 2
                AND s.[Document Type] = 1
                AND (SH.[Sales order typ] <> 'IR' OR SH.[Order Date] <= '20200101')
                AND c.[Business Model] in ('1_WHOLESALE', '2_DISTRIBUTORS')
                AND s.[Outstanding Quantity] <> 0
                ORDER BY s.[Requested Delivery Date] desc;";

            $backlog = $this->mssqlLcs->executeQuery($query);

            /* ======================
               Récupération stock réel
               ====================== */

            $stocks = $this->getStockReel();

            /* ======================
               Indexation du stock
               ====================== */

            $stockIndex = [];

            foreach ($stocks as $stock) {

                $key = $stock->LastSeriesNo . '|' .
                    $stock->ItemFamilyCode . '|' .
                    $stock->ItemNo . '|' .
                    $stock->VariantCode;

                $stockIndex[$key] = $stock->AvailableInventory_Deducted_NA_Quantity ?? 0;
            }

            /* ======================
               Injection stock backlog
               ====================== */

            foreach ($backlog as $row) {

                $key = $row->collection . '|' .
                    $row->famille . '|' .
                    $row->article . '|' .
                    $row->variant_code;

                $row->stock_reel = $stockIndex[$key] ?? 0;
            }
            return $backlog;

        } catch (\Exception $e) {

            $this->graphMailer->notifyError(
                '❌ LCS Erreur Sales : Récupération de données Backlog clients',
                $e
            );

            $this->logger->error(
                'LCS Erreur Sales : Récupération de données Backlog clients',
                ['exception' => $e]
            );
        }
    }

    public function getCommandesAFacturer(): array
    {
        try {
            $query = $this->sqlFileLoader->load('Navision/commandes_a_facturer_tmp.sql');
            $data = $this->mssqlLcs->executeQuery($query);
            return $data;

        } catch (\Exception $e) {
            $this->graphMailer->notifyError('❌ LCS Erreur Commandes à Facturer : Récupération de données Ventes LCS', $e);
            $this->logger->error('LCS Erreur Commandes à Facturer : Récupération de données Ventes LCS', ['exception' => $e]);
        }
    }

    public function getStockReel(): array
    {
        try {
            $query = $this->sqlFileLoader->load('Navision/stock_reel_tmp.sql');
            $data = $this->mssqlLcs->executeQuery($query);
            return $data;
        }catch (\Exception $e) {
            $this->graphMailer->notifyError('❌ LCS Erreur Stock : Récupération de données Stock Reel LCS', $e);
            $this->logger->error('LCS Erreur Stock : Récupération de données Stock Reel LCS', ['exception' => $e]);
        }
    }

}
