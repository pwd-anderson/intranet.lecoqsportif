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
    public function getExchangeRateData(ExchangeRatesMoyenRepository $exchangeRatesMoyenRepository, string $currency = 'EUR'): JsonResponse
    {
        $data = $exchangeRatesMoyenRepository->getDataConversionRate($currency);
        $rateData = $exchangeRatesMoyenRepository->getCurrentRateAndEvolution($currency);

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

        $labels = [];
        $values = [];

        foreach ($clients as $row) {
            $labels[] = $row->CustomerName ?? $row['CustomerName'];
            $values[] = round($row->TotalCA_EUR ?? $row['TotalCA_EUR'], 2);
        }

        return new JsonResponse([
            'labels' => $labels,
            'data' => $values,
        ]);
    }

    #[Route('/api/dashboard/top-company-sales', name: 'api_dashboard_top_company_sales')]
    public function getTopCompanySales(): JsonResponse
    {
        $data = $this->mainDashboard->getTopCompanySales();
        $dataUtf8 = $this->helpers->convertArrayToUtf8($data);

        $dataArray = array_map(fn($item) => (array) $item, $dataUtf8);

        $labels = array_column($dataArray, 'CompanyCode');
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

        $dataArray = array_map(fn($item) => (array) $item, $dataUtf8);

        $labels = array_column($dataArray, 'ItemDescription');
        $values = array_map(fn($v) => round($v, 2), array_column($dataArray, 'TotalSales'));

        return new JsonResponse([
            'labels' => $labels,
            'values' => $values,
        ]);
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
}
