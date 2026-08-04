<?php

namespace App\Controller;

use App\Repository\ExchangeRatesMoyenRepository;
use App\Service\Dashboards\MainDashboard;
use App\Service\Tools\Helpers;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
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

    private function getNetworkFromRequest(Request $request): string
    {
        $network = $request->query->get('network', 'global');

        return in_array($network, ['global', 'boutique', 'ecom', 'wholesale_fr', 'wholesale_eu', 'wholesale_int'], true)
            ? $network
            : 'global';
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
    public function getCaParMois(Request $request): JsonResponse
    {
        $network = $this->getNetworkFromRequest($request);

        $dataAnnual = $this->mainDashboard->getSalesComparaisonYears($network);
        $dataByMonth = $this->mainDashboard->getSalesComparaisonByMonths($network);

        $labels = ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin', 'Juil', 'Août', 'Sept', 'Oct', 'Nov', 'Déc'];
        $caN = array_fill(0, 12, 0);
        $caN1 = array_fill(0, 12, 0);

        foreach ($dataByMonth as $row) {
            $index = (int) $row->mois - 1;
            if ($index >= 0 && $index < 12) {
                $caN[$index] = round((float) $row->ca_n, 2);
                $caN1[$index] = round((float) $row->ca_n_1, 2);
            }
        }

        $year = (int) date('Y');

        return new JsonResponse([
            'ca_n'      => round((float) ($dataAnnual[0]->ca_n    ?? 0), 2),
            'ca_n_1'    => round((float) ($dataAnnual[0]->ca_n_1  ?? 0), 2),
            'ca_ytd_n1' => round((float) ($dataAnnual[0]->ca_ytd_n1 ?? 0), 2),
            'variation' => $dataAnnual[0]->variation_pourcent ?? null,
            'year'      => $year,
            'labels'    => $labels,
            'series'    => [
                ['name' => 'CA '    . $year,       'data' => $caN],
                ['name' => 'CA '    . ($year - 1), 'data' => $caN1],
            ],
        ]);
    }

    #[Route('/api/dashboard/sales-current-month', name: 'api_dashboard_sales_current_month')]
    public function getSalesCurrentMonthData(Request $request): JsonResponse
    {
        $network = $this->getNetworkFromRequest($request);

        $dataByDay = $this->mainDashboard->getSalesComparaisonCurrentMonthByDay($network);
        $summary   = $this->mainDashboard->getSalesComparaisonCurrentMonth($network);

        $labels = [];
        $caN    = [];
        $caN1   = [];

        // Mois de J-1, pas mois courant
        $month = (new \DateTime('yesterday'))->format('m');

        foreach ($dataByDay as $row) {
            $day = str_pad((string) $row->jour, 2, '0', STR_PAD_LEFT);
            $labels[] = $day . '/' . $month;
            $caN[]    = round((float) $row->ca_n, 2);
            $caN1[]   = round((float) $row->ca_n_1, 2);
        }

        return new JsonResponse([
            'labels' => $labels,
            'series' => [
                ['name' => 'CA Mois N',   'data' => $caN],
                ['name' => 'CA Mois N-1', 'data' => $caN1],
            ],
            'ca_n'      => round((float) ($summary[0]->ca_n ?? 0), 2),
            'variation' => $summary[0]->variation_pourcent ?? null,
        ]);
    }

    #[Route('/api/dashboard/top-clients', name: 'api_dashboard_top_clients')]
    public function getTopClients(Request $request): JsonResponse
    {
        $network = $this->getNetworkFromRequest($request);

        $clients = $this->mainDashboard->getTopClients($network);
        $dataUtf8 = $this->helpers->convertArrayToUtf8($clients);

        $labels = [];
        $values = [];

        foreach ($dataUtf8 as $row) {
            $customerName = is_array($row)
                ? ($row['CustomerName'] ?? '')
                : ($row->CustomerName ?? '');

            $totalCa = is_array($row)
                ? ($row['TotalCA_EUR'] ?? 0)
                : ($row->TotalCA_EUR ?? 0);

            $labels[] = mb_strimwidth((string) $customerName, 0, 25, '…');
            $values[] = round((float) $totalCa, 2);
        }

        return new JsonResponse([
            'labels' => $labels,
            'data' => $values,
        ]);
    }

    #[Route('/api/dashboard/top-family-sales', name: 'api_dashboard_top_family_sales')]
    public function getTopFamilySales(Request $request): JsonResponse
    {
        $network = $this->getNetworkFromRequest($request);

        $data = $this->mainDashboard->getTopFamilySales($network);
        $dataUtf8 = $this->helpers->convertArrayToUtf8($data);
        $dataArray = array_map(fn($item) => (array) $item, $dataUtf8);

        $labels = array_column($dataArray, 'ItemFamilyCode');
        $values = array_map(fn($value) => round((float) $value, 2), array_column($dataArray, 'TotalSales'));

        return new JsonResponse([
            'labels' => $labels,
            'values' => $values,
        ]);
    }

    #[Route('/api/dashboard/top-product-sales', name: 'api_dashboard_top_product_sales')]
    public function getTopProductSales(Request $request): JsonResponse
    {
        $network = $this->getNetworkFromRequest($request);
        $family  = $request->query->get('family');

        $allowed = ['FTW', 'APL', 'HDW'];
        $family  = in_array($family, $allowed, true) ? $family : null;

        $data     = $this->mainDashboard->getTopProductsBySales($network, $family);
        $dataUtf8 = $this->helpers->convertArrayToUtf8($data);

        return new JsonResponse($dataUtf8);
    }

    #[Route('/api/dashboard/sales-evolution-5y', name: 'api_dashboard_sales_evolution_5y')]
    public function getSalesEvolution5Years(Request $request): JsonResponse
    {
        $network = $this->getNetworkFromRequest($request);

        $rawData = $this->mainDashboard->getMonthlySalesEvolutionLast5Years($network);
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

            if ($mois >= 1 && $mois <= 12) {
                $series[$annee][$mois] = $ca;
            }
        }

        $seriesFormatted = [];
        ksort($series);

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
    public function getSalesOfToday(Request $request): JsonResponse
    {
        $network = $this->getNetworkFromRequest($request);

        $ca = $this->mainDashboard->getSalesOfToday($network);

        return new JsonResponse([
            'ca_n_j_1' => round((float) $ca, 2),
        ]);
    }

    #[Route('/api/dashboard/backlog-client', name: 'api_dashboard_backlog_client')]
    public function backlogClientDashboard(Request $request): JsonResponse
    {
        $network = $this->getNetworkFromRequest($request);

        return $this->json(
            $this->mainDashboard->getBacklogClientDonut($network)
        );
    }
}
