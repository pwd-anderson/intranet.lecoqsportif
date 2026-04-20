<?php

namespace App\Service\kpi;

use App\Repository\KpiDeckPresentationRepository;

class SalesFoX3Kpi
{
    public function __construct(
        private OverviewBoutiquesX3Kpi $overviewBoutiquesKpi,
        private SalesByFamilyX3Kpi $salesByFamilyKpi,
        private KpiDeckPresentationRepository $kpiDeckPresentationRepository
    ) {}

    public function getData(int $year, int $week): array
    {
        $storeKeys = ['full_boutique', 'textile_footwear', 'top_produits'];

        $comments = $this->kpiDeckPresentationRepository->findByDeck(
            'sales_fo',
            $year,
            $week,
            $storeKeys
        );
        $commentsByStore = [];

        foreach ($comments as $comment) {
            $commentsByStore[$comment->getStoreKey()] = $comment->getCommentHtml();
        }
        // 1) Récupérer les 3 boutiques
        $boutiques = $this->overviewBoutiquesKpi->getBoutiquesDataFromKpi($year, $week, 'FACTORY OUTLET');

        // 2) Extraire uniquement FULL BOUTIQUE
        $fullBoutique = null;
        foreach ($boutiques as $b) {
            if (($b['store_key'] ?? null) === 'full_boutique') {
                $fullBoutique = $b;
                break;
            }
        }

        // 3) Données Sales by family
        $salesByFamily = $this->salesByFamilyKpi->getData($year, $week, 'FACTORY OUTLET');
        return [
            'full_boutique' => $fullBoutique,
            'sales_by_family' => $salesByFamily,
            'comments' => $commentsByStore,
        ];
    }
}
