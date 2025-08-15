<?php

namespace App\Service;

use App\Service\Tools\GraphMailer;
use Psr\Log\LoggerInterface;
use App\Service\Tools\MssqlManager;

class Stock
{

    public function __construct(
        private MssqlManager $mssqlManager,
        private LoggerInterface $logger,
        private GraphMailer $graphMailer
    ) {}

    public function getSalesDataByYear($year): array
    {
        try {
            $query = "SELECT Famille, BarCode, Designation, Couleur, Taille, Vendeur, code_pays, FORMAT(Date, 'yyyy-MM-dd') AS Date,
                        DeviseML, TVA, rate, Quantite, PrixUnitaireHTDevise, PrixUnitaireTTCDevise, PrixUnitaireHtChf, TotalHTDevise,
                        TotalTTCDevise, TotalHtChf, PrixAchat
                        FROM OGIER.view_ventes
                        where YEAR(Date) = $year";

            $data = $this->mssqlManager->executeQuery($query);
            return $data;

        } catch (\Exception $e) {
            $this->graphMailer->notifyError('❌ OGIER Erreur Sales : Récupération de données Ventes', $e);
            $this->logger->error('OGIER Erreur Sales : Récupération de données Ventes', ['exception' => $e]);
        }
    }



}
