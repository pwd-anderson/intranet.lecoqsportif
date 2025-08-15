<?php

namespace App\Service;

use App\Service\Tools\GraphMailer;
use Symfony\Component\Mime\Email;
use Psr\Log\LoggerInterface;
use App\Service\Tools\MssqlManager;

class Management
{

    public function __construct(
        private MssqlManager $mssqlManager,
        private LoggerInterface $logger,
        private GraphMailer $graphMailer
    ) {}

    public function getCAMargesByYear($year): array
    {
        try {
            $query = "SELECT
                    YEAR(I.ExpectedInvoicingDate) AS InvoicingYear,
                    MONTH(I.ExpectedInvoicingDate) AS InvoicingMonth,
                    I.DocumentNo,
                    I.CustomerNo,
                    C.Name AS CustomerName,
                    I.CurrencyCode,
                    I.ItemNo,
                    IT.Description AS ItemDescription,
                    SUM(I.Quantity) AS Quantity,
                    SUM(I.AmountEurTM) AS AmountEur,
                    SUM(I.AmountCurrency) AS AmountCurrency,
                    SUM(I.Margin_Cost_AmountEurTM) AS CostAmountEur,      -- coût achat
                    SUM(I.Margin_Cost_AmountCurrency) AS CostAmountCurrency,
                    SUM(I.Margin_AmountEurTM) AS Margin_AmountEur,        -- marge
                    FORMAT(INV.LastInboundDate, 'yyyy-MM-dd') as LastInboundDate
                FROM BI.DWH.F_Invoices I
                LEFT JOIN BI.DWH.D_Customer C ON C.Code = I.CustomerNo AND C.CompanyCode = I.CompanyCode
                LEFT JOIN BI.DWH.D_Item IT ON IT.ItemNo = I.ItemNo
                LEFT JOIN (SELECT ItemNo,MAX(LastInboundDate) LastInboundDate FROM BI.DWH.F_Inventory WHERE LocationCode like 'LOGTXM-1' GROUP BY ItemNo) INV ON INV.ItemNo = I.ItemNo
                WHERE
                    IsBohPerimeter_Product = 1
                    AND IsBohPerimeter_IR = 1
                    AND DocumentType = 'INVOICE'
                    AND YEAR(ExpectedInvoicingDate) = 2025
                    -- AND MONTH(ExpectedInvoicingDate) = 1
                    -- AND I.ItemNo = '2510466'
                    -- AND I.DocumentNo = 'FVB25000003'
                GROUP BY
                    YEAR(I.ExpectedInvoicingDate),
                    MONTH(I.ExpectedInvoicingDate),
                    I.DocumentNo,
                    I.CustomerNo,
                    C.Name,
                    I.CurrencyCode,
                    I.ItemNo,
                    IT.Description,
                    INV.LastInboundDate;";

            $data = $this->mssqlManager->executeQuery($query);
            //dump($data);exit;
            return $data;

        } catch (\Exception $e) {
            $this->graphMailer->notifyError('❌ OGIER Erreur Sales : Récupération de données Ventes', $e);
            $this->logger->error('OGIER Erreur Sales : Récupération de données Ventes', ['exception' => $e]);
        }
    }

}
