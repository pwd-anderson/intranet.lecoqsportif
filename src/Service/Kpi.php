<?php

namespace App\Service;

use App\Factory\MssqlManagerFactory;
use App\Service\Tools\GraphMailer;
use Symfony\Component\Mime\Email;
use Psr\Log\LoggerInterface;
use App\Service\Tools\MssqlManager;

class Kpi
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

    public function getEshopData(): array
    {
        try {
            return [
                'ca_ht' => 63544130,
                'variation_ca' => -56,
                'panier_moyen' => 88.6,
                'panier_variation' => -0.1,
                'conversion' => 1.68,
                'conversion_variation' => -0.83,
                'trafic' => 25200,
                'trafic_variation' => -25.2,
                'roas' => 4.5,
                'roas_variation' => -1.1,
                'cac' => 13.1,
                'email_percent' => 16,
                'email_variation' => 4.4,
                'marketplace_ca' => 21590,
                'marketplace_variation' => -79
            ];

        } catch (\Exception $e) {
            $this->graphMailer->notifyError('❌ LCS Erreur Sales : Récupération de données Livraison non facturées', $e);
            $this->logger->error('LCS Erreur Sales : Récupération de données Livraison non facturées', ['exception' => $e]);
        }
    }

    public function getTopProducts(): array
    {
        try {
            return [
                'textile' => [
                    ['id' => '2345678', 'label' => 'Pharetra, Nulla , Nec, Aliquet', 'price' => '4085 €', 'image' => 'tex_1.png'],
                    ['id' => '2876453', 'label' => 'Pharetra, Nulla , Nec, Aliquet', 'price' => '1385 €', 'image' => 'tex_2.png'],
                    ['id' => '2567854', 'label' => 'Pharetra, Nulla , Nec, Aliquet', 'price' => '1285 €', 'image' => 'tex_3.png']
                ],
                'footwear' => [
                    ['id' => '2456789', 'label' => 'Pharetra, Nulla , Nec, Aliquet', 'price' => '1800 €', 'image' => 'foo_1.png'],
                    ['id' => '2567834', 'label' => 'Pharetra, Nulla , Nec, Aliquet', 'price' => '1777 €', 'image' => 'foo_2.png'],
                    ['id' => '2534789', 'label' => 'Pharetra, Nulla , Nec, Aliquet', 'price' => '930 €', 'image' => 'foo_3.png']
                ]
            ];

        } catch (\Exception $e) {
            $this->graphMailer->notifyError('❌ LCS Erreur Sales : Récupération de données Livraison non facturées', $e);
            $this->logger->error('LCS Erreur Sales : Récupération de données Livraison non facturées', ['exception' => $e]);
        }
    }

    public function getCommentaires(): array
    {
        try {
            return [
                'winner' => [
                    "CA en progression vs LW (+8%) avec 2J de French days vs 5J LW => dernier jour + fort que premier jour",
                    "Jackets passe en 3e caté qui performe en CA (à la place des t-shirts)",
                    "TRI SP FZ Hybride N°1 M sky captain TOP PERF et hors sélection French days"
                ],
                'looser' => [
                    "Footwear dont la PDM tombe à 42%",
                    "(-5 points vs trend précédente semaine hors French days) => sélection french majoritairement sur textile"
                ]
            ];

        } catch (\Exception $e) {
            $this->graphMailer->notifyError('❌ LCS Erreur Sales : Récupération de données Livraison non facturées', $e);
            $this->logger->error('LCS Erreur Sales : Récupération de données Livraison non facturées', ['exception' => $e]);
        }
    }

}
