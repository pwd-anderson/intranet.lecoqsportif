<?php

namespace App\Controller;

use App\Repository\ExchangeRatesMoyenRepository;
use App\Service\Dashboards\MainDashboard;
use App\Service\Tools\Helpers;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    public function __construct(
        private MainDashboard $mainDashboard, private Helpers $helpers
    ) {}
    #[Route('/', name: 'app_home')]
    public function index(): Response
    {
        return $this->render('home/dashboard.html.twig');
    }

    #[Route('/api/dashboard/exchange-rate/{currency}', name: 'api_dashboard_exchange_rate')]
    public function getExchangeRateData(ExchangeRatesMoyenRepository $repo, string $currency = 'EUR'): JsonResponse
    {
        $target = 'USD'; // fixe USD

        $data = $repo->getDataConversionRate($currency, $target);
        $rateData = $repo->getCurrentRateAndEvolution($currency, $target);

        $series = [
            'positive' => [],
            'negative' => [],
            'labels' => [],
            'taux_courant' => round($rateData['taux_courant'], 4),
            'evolution_pourcent' => $rateData['evolution_pourcent'],
        ];

        foreach ($data as $row) {
            $series['labels'][] = substr($row['mois'], 2);
            $series['positive'][] = round($row['positive_variation'] * 1000, 2);
            $series['negative'][] = round($row['negative_variation'] * -1000, 2);
        }

        return new JsonResponse($series);
    }

    #[Route('/api/dashboard/ca-par-mois', name: 'api_dashboard_ca_par_mois')]
    public function getCaParMois(): JsonResponse
    {
        $dataAnnual = $this->mainDashboard->getSalesComparaisonYears();
        $dataByMonth = $this->mainDashboard->getSalesComparaisonByMonths();

        $labels = ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin', 'Juil', 'Août', 'Sept', 'Oct', 'Nov', 'Déc'];
        $caN = array_fill(0, 12, 0);
        $caN1 = array_fill(0, 12, 0);

        foreach ($dataByMonth as $row) {
            $index = $row->mois - 1;
            $caN[$index] = round($row->ca_n, 2);
            $caN1[$index] = round($row->ca_n_1, 2);
        }

        return new JsonResponse([
            'ca_n' => round($dataAnnual[0]->ca_n, 2),
            'variation' => $dataAnnual[0]->variation_pourcent,
            'labels' => $labels,
            'series' => [
                ['name' => 'CA Année N', 'data' => $caN],
                ['name' => 'CA Année N-1', 'data' => $caN1]
            ]
        ]);
    }

    #[Route('/api/dashboard/sales-current-month', name: 'api_dashboard_sales_current_month')]
    public function getSalesCurrentMonthData(): JsonResponse
    {
        $dataByDay = $this->mainDashboard->getSalesComparaisonCurrentMonthByDay();
        $summary = $this->mainDashboard->getSalesComparaisonCurrentMonth();

        $labels = [];
        $caN = [];
        $caN1 = [];

        $month = (new \DateTime())->format('m'); // Mois courant, ex: "05"

        foreach ($dataByDay as $row) {
            $day = str_pad($row->jour, 2, '0', STR_PAD_LEFT); // "01", "02", ...
            $labels[] = "$day/$month"; // ex: "01/05", "02/05"
            $caN[] = round($row->ca_n, 2);
            $caN1[] = round($row->ca_n_1, 2);
        }

        return new JsonResponse([
            'labels' => $labels,
            'series' => [
                ['name' => 'CA Mois N', 'data' => $caN],
                ['name' => 'CA Mois N-1', 'data' => $caN1],
            ],
            'ca_n' => round($summary[0]->ca_n, 2),
            'variation' => $summary[0]->variation_pourcent,
        ]);
    }

    #[Route('/api/dashboard/top-clients', name: 'api_dashboard_top_clients')]
    public function getTopClients(): JsonResponse
    {
        $clients = $this->mainDashboard->getTopClients(); // appelle ta méthode SQL directe
        $dataUtf8 = $this->helpers->convertArrayToUtf8($clients);

        $labels = [];
        $values = [];

        foreach ($dataUtf8 as $row) {
            $labels[] = $row->CustomerName ?? mb_strimwidth($row['CustomerName'], 0, 25, '…');
            $values[] = round($row->TotalCA_EUR ?? $row['TotalCA_EUR'], 2);
        }

        return new JsonResponse([
            'labels' => $labels,
            'data' => $values,
        ]);
    }

    #[Route('/api/dashboard/top-family-sales', name: 'api_dashboard_top_family_sales')]
    public function getTopFamilySales(): JsonResponse
    {
        $data = $this->mainDashboard->getTopFamilySales();
        $dataUtf8 = $this->helpers->convertArrayToUtf8($data);

        $dataArray = array_map(fn($item) => (array) $item, $dataUtf8);

        $labels = array_column($dataArray, 'ItemFamilyCode');
        $values = array_column($dataArray, 'TotalSales');

        return new JsonResponse([
            'labels' => $labels,
            'values' => $values,
        ]);
    }

    #[Route('/api/dashboard/top-product-sales', name: 'api_dashboard_top_product_sales')]
    public function getTopProductSales(): JsonResponse
    {
        $data = $this->mainDashboard->getTopProductsBySales();
        $dataUtf8 = $this->helpers->convertArrayToUtf8($data);

        return new JsonResponse($dataUtf8);
    }

    #[Route('/api/dashboard/sales-evolution-5y', name: 'api_dashboard_sales_evolution_5y')]
    public function getSalesEvolution5Years(): JsonResponse
    {
        $rawData = $this->mainDashboard->getMonthlySalesEvolutionLast5Years();
        $dataUtf8 = $this->helpers->convertArrayToUtf8($rawData);

        $dataArray = array_map(fn($item) => (array) $item, $dataUtf8);

        $series = [];
        $labels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

        foreach ($dataArray as $row) {
            $annee = $row['annee'];
            $mois = (int) $row['mois'];
            $ca = round((float) $row['ca_mensuel'], 2);

            if (!isset($series[$annee])) {
                $series[$annee] = array_fill(1, 12, 0.0);
            }

            $series[$annee][$mois] = $ca;
        }

        // Construction du format ApexCharts
        $seriesFormatted = [];
        ksort($series); // pour avoir les années dans l'ordre
        foreach ($series as $annee => $moisData) {
            $seriesFormatted[] = [
                'name' => (string) $annee,
                'data' => array_values($moisData),
            ];
        }

        return new JsonResponse([
            'series' => $seriesFormatted,
            'categories' => $labels,
        ]);
    }

    #[Route('/api/dashboard/ca-today', name: 'api_dashboard_ca_today')]
    public function getSalesOfToday(): JsonResponse
    {
        $ca = $this->mainDashboard->getSalesOfToday();

        return new JsonResponse([
            'ca_n_j_1' => round($ca, 2),
        ]);
    }
}
