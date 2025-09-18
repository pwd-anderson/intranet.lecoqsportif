<?php

namespace App\Service;

use App\Factory\MssqlManagerFactory;
use App\Service\Tools\GraphMailer;
use Symfony\Component\Mime\Email;
use Psr\Log\LoggerInterface;
use App\Service\Tools\MssqlManager;

class Sales
{
    private MssqlManager $mssqlLcs;

    public function __construct(
        private MssqlManagerFactory $mssqlManagerFactory,
        private LoggerInterface $logger,
        private GraphMailer $graphMailer
    )
    {
        $this->mssqlLcs = $this->mssqlManagerFactory->create('lcs');
    }

    public function getLivraisonsNonFacturees(): array
    {
        try {
            $query = "select
                        s.CompanyCode
                        ,c.No_
                        ,c.Name
                        ,s.[Document No_]
                        ,s.No_
                        , s.[Variant Code]
                        ,SUM(S.[Qty_ Shipped Not Invoiced]) QtyShippedNotInvoiced,
                        sum(s.[Shipped Not Invoiced]) as montant_ttc, sum(s.[Shipped Not Invoiced HT]) as montant_ht
                        FROM [DB_Datalake].[nav].[Sales Line] s
                        left join [DB_Datalake].[nav].[Customer] as c on s.CompanyCode = c.CompanyCode and s.[Bill-to Customer No_] = c.No_
                        where s.[Qty_ Shipped Not Invoiced] <> 0
                        and year(s.[Created Date Time]) > 2016
                        GROUP BY s.No_, s.[Variant Code],s.[Document No_],s.CompanyCode,c.No_,c.Name";

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
                        s.[Variant Code],
                        i.[Last Series No_] as collection,
                        s.[Document No_] as no_commande,
                        s.[Requested Delivery Date] as date_livraison,
                        s.[Outstanding Quantity] as quantite

                        FROM
                        [DB_Datalake].[nav].[Sales Line] s
                        LEFT JOIN DB_Datalake.[nav].[Sales Header] SH
                            ON SH.CompanyCode = S.CompanyCode
                            AND SH.No_ = S.[Document No_]
                            AND SH.[Document Type] = S.[Document Type]
                        left join [DB_Datalake].[nav].[Customer] as c on s.CompanyCode = c.CompanyCode and s.[Bill-to Customer No_] = c.No_
                        left join [DB_Datalake].[nav lcsi bv].[Item] i on s.No_ = i.No_
                        WHERE
                        s.No_ <> ''
                        AND s.[Type] = 2
                        AND s.[Document Type] = 1
                        AND (SH.[Sales order typ] <> 'IR' OR SH.[Order Date] <= '20200101') -- exclusion des forecast JO 2024
                        and c.[Business Model] in ('1_WHOLESALE', '2_DISTRIBUTORS')
                        and s.[Outstanding Quantity] <> 0
                        order by s.[Requested Delivery Date] desc;";

            $data = $this->mssqlLcs->executeQuery($query);
            return $data;

        } catch (\Exception $e) {
            $this->graphMailer->notifyError('❌ LCS Erreur Sales : Récupération de données Backlog clients', $e);
            $this->logger->error('LCS Erreur Sales : Récupération de données Backlog clients', ['exception' => $e]);
        }
    }

}
