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
        $this->mssqlSei = $this->mssqlManagerFactory->create($dbLcsSei);
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

            $query = "select s.CompanyCode AS CODE_COMPANY,
                        s.OrderSeriesNo as COLLECTION,
                        i.ItemFamilyCode as FAMILLE,
                        s.ItemNo as ARTICLE,
                        s.VariantCode AS CODE_VARIANT,
                        c.BillToNo AS CODE_CLIENT,
                        c.BillToName AS NOM_CLIENT,
                        s.OrderDocumentNo AS NO_COMMANDE,
                        s.OrderCreationDate AS DATE_COMMANDE,
                        o.RequestedDeliveryDate_L AS DATE_LIVRAISON_DEMANDEE,
                        s.OUT_Quantity AS QUANTITE,
                        s.OUT_AmountEur AS MONTANT_HT_EUR,
                        0 as STOCK_REEL
                        from BI.DWH.F_Sales s
                        left join BI.DWH.F_Sales_Orders o on s.OrderDocumentNo = o.OrderDocumentNo and s.CompanyCode = o.CompanyCode and s.OrderDocumentLineNo = o.OrderDocumentLineNo and s.VariantCode = o.VariantCode
                        left join BI.DWH.D_Item i on s.ItemNo = i.ItemNo
                        left join BI.DWH.D_Customer c on s.CustomerNo = c.Code and s.CompanyCode = c.CompanyCode
                        where 1=1
                        and s.CompanyCode = 'LCSI BV'
                        and s.OUT_Quantity <> 0
                        and s.IsBohPerimeter = 1
                        and s.LocationCode in ('DIRECT', 'DT-WHS-TH', 'LOGTXM-1', 'SF-WHS-CN1')
                        and s.SalesOrderType in ('CO', 'OP', 'PS', 'RE')";

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

                $key = $row->COLLECTION . '|' .
                    $row->FAMILLE . '|' .
                    $row->ARTICLE . '|' .
                    $row->CODE_VARIANT;

                $row->STOCK_REEL = $stockIndex[$key] ?? 0;
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

    public function getReassort(): array
    {
        try {
            $query = $this->sqlFileLoader->load('Navision/reassort_tmp.sql');
            $data = $this->mssqlLcs->executeQuery($query);

            return $data;

        } catch (\Exception $e) {
            $this->graphMailer->notifyError(
                '❌ LCS Erreur Sales : Récupération de données Reassort clients',
                $e
            );

            $this->logger->error(
                'LCS Erreur Sales : Récupération de données Reassort clients',
                ['exception' => $e]
            );

            return [];
        }
    }

    public function getCommandesAFacturerX3(): array
    {
        try {
            $query = $this->sqlFileLoader->load('Sei/commandes_a_facturer_x3.sql');
            $data = $this->mssqlSei->executeQuery($query);
            return $data;

        } catch (\Exception $e) {
            $this->graphMailer->notifyError('❌ LCS Erreur Commandes à Facturer X3 : Récupération de données Ventes LCS', $e);
            $this->logger->error('LCS Erreur Commandes à Facturer X3 : Récupération de données Ventes LCS', ['exception' => $e]);
        }
    }

}
