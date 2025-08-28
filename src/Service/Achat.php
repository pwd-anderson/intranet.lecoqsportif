<?php

namespace App\Service;

use App\Service\Tools\GraphMailer;
use Symfony\Component\Mime\Email;
use Psr\Log\LoggerInterface;
use App\Service\Tools\MssqlManager;

class Sales
{

    public function __construct(
        private MssqlManager $mssqlManager,
        private LoggerInterface $logger,
        private GraphMailer $graphMailer
    ) {}

    public function getLivraisonsNonFacturees(): array
    {
        try {
            $query = "select
                      s.CompanyCode
                      ,s.[Document No_]
                      ,s.No_
                      , s.[Variant Code]
                      ,SUM(S.[Qty_ Shipped Not Invoiced]) QtyShippedNotInvoiced,
                      sum(s.[Shipped Not Invoiced]) as montant_ttc, sum(s.[Shipped Not Invoiced HT]) as montant_ht
                    FROM [DB_Datalake].[nav].[Sales Line] s
                      where s.[Qty_ Shipped Not Invoiced] <> 0
                       and year(s.[Created Date Time]) > 2016
                       GROUP BY s.No_, s.[Variant Code],s.[Document No_],s.CompanyCode";

            $data = $this->mssqlManager->executeQuery($query);
            return $data;

        } catch (\Exception $e) {
            $this->graphMailer->notifyError('❌ LCS Erreur Sales : Récupération de données Livraison non facturées', $e);
            $this->logger->error('LCS Erreur Sales : Récupération de données Livraison non facturées', ['exception' => $e]);
        }
    }

}
