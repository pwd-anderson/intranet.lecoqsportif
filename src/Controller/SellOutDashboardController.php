<?php

namespace App\Controller;

use App\Repository\ExchangeRatesMoyenRepository;
use App\Service\Dashboards\SellOutDashboard;
use App\Service\Tools\Helpers;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class SellOutDashboardController extends AbstractController
{
    public function __construct(
        private SellOutDashboard $sellOutDashboard,
        private Helpers $helpers
    ) {}

    #[Route('/dashboard/sell-out', name: 'app_dashboard_sellout')]
    public function index(): Response
    {
        return $this->render('home/dashboard_sellout.html.twig');
    }

    #[Route('/api/sell-out/ventes-annuelles', name: 'api_sellout_ventes_annuelles')]
    public function getVentesAnnuelles(): JsonResponse
    {
        $rows        = $this->sellOutDashboard->getVentesAnnuelles();
        $dataArray   = array_map(fn($r) => (array) $r, $this->helpers->convertArrayToUtf8($rows));
        $currentYear = (int) date('Y');
        $prevYear    = $currentYear - 1;

        $seriesN  = [];
        $seriesN1 = [];
        $allWeeks = [];

        foreach ($dataArray as $row) {
            $allWeeks[(int) $row['semaine']] = true;
        }
        ksort($allWeeks);

        foreach ($dataArray as $row) {
            $semaine = (int) $row['semaine'];
            $ca      = round((float) $row['salesamt'], 2);
            if ((int) $row['annee'] === $currentYear) {
                $seriesN[$semaine]  = $ca;
            } else {
                $seriesN1[$semaine] = $ca;
            }
        }

        $labels = [];
        $dataN  = [];
        $dataN1 = [];

        foreach (array_keys($allWeeks) as $week) {
            $labels[] = 'S' . str_pad((string) $week, 2, '0', STR_PAD_LEFT);
            $dataN[]  = $seriesN[$week]  ?? 0;
            $dataN1[] = $seriesN1[$week] ?? 0;
        }

        $totalN  = array_sum($dataN);
        $totalN1 = array_sum($dataN1);
        $variation = $totalN1 > 0 ? round(($totalN - $totalN1) / $totalN1 * 100, 2) : null;

        return new JsonResponse([
            'ca_n'      => round($totalN, 2),
            'ca_n1'     => round($totalN1, 2),
            'variation' => $variation,
            'labels'    => $labels,
            'series'    => [
                ['name' => (string) $currentYear, 'data' => $dataN],
                ['name' => (string) $prevYear,    'data' => $dataN1],
            ],
        ]);
    }

    #[Route('/api/sell-out/ventes-semaine', name: 'api_sellout_ventes_semaine')]
    public function getVentesSemaine(): JsonResponse
    {
        $semaine     = $this->sellOutDashboard->getLastWeekNumber();
        $rows        = $this->sellOutDashboard->getVentesSemaine();
        $dataArray   = array_map(fn($r) => (array) $r, $this->helpers->convertArrayToUtf8($rows));
        $currentYear = (int) date('Y');
        $prevYear    = $currentYear - 1;

        $bySourceYear = [];
        $totals       = [$currentYear => 0.0, $prevYear => 0.0];

        foreach ($dataArray as $row) {
            $annee  = (int) $row['annee'];
            $source = $row['sourcename'];
            $ca     = round((float) $row['salesamt'], 2);

            $bySourceYear[$source][$annee] = $ca;
            if (array_key_exists($annee, $totals)) {
                $totals[$annee] += $ca;
            }
        }

        $variation = $totals[$prevYear] > 0
            ? round(($totals[$currentYear] - $totals[$prevYear]) / $totals[$prevYear] * 100, 2)
            : null;

        $sources = ['INTERSPORT', 'SPORT2000'];
        $dataN   = array_map(fn($s) => $bySourceYear[$s][$currentYear] ?? 0, $sources);
        $dataN1  = array_map(fn($s) => $bySourceYear[$s][$prevYear]    ?? 0, $sources);

        return new JsonResponse([
            'semaine'   => $semaine,
            'ca_n'      => round($totals[$currentYear], 2),
            'ca_n1'     => round($totals[$prevYear], 2),
            'variation' => $variation,
            'labels'    => $sources,
            'series'    => [
                ['name' => (string) $prevYear,    'data' => $dataN1],
                ['name' => (string) $currentYear, 'data' => $dataN],
            ],
        ]);
    }

    #[Route('/api/sell-out/top-clients', name: 'api_sellout_top_clients')]
    public function getTopClients(): JsonResponse
    {
        $rows    = $this->sellOutDashboard->getTopClients();
        $dataUtf8 = $this->helpers->convertArrayToUtf8($rows);

        $labels = [];
        $values = [];

        foreach ($dataUtf8 as $row) {
            $row      = (array) $row;
            $labels[] = $row['sourcename'];
            $values[] = round((float) $row['salesamt'], 2);
        }

        return new JsonResponse(['labels' => $labels, 'data' => $values]);
    }

    #[Route('/api/sell-out/famille-ca', name: 'api_sellout_famille_ca')]
    public function getFamilyCa(): JsonResponse
    {
        $rows     = $this->sellOutDashboard->getFamilyCa();
        $dataUtf8 = $this->helpers->convertArrayToUtf8($rows);

        $labels = [];
        $values = [];

        foreach ($dataUtf8 as $row) {
            $row      = (array) $row;
            $labels[] = (string) $row['famille'];
            $values[] = round((float) $row['salesamt'], 2);
        }

        return new JsonResponse(['labels' => $labels, 'values' => $values]);
    }

    #[Route('/api/sell-out/top-produits', name: 'api_sellout_top_produits')]
    public function getTopProduits(): JsonResponse
    {
        $rows     = $this->sellOutDashboard->getTopProduits();
        $dataUtf8 = $this->helpers->convertArrayToUtf8($rows);

        $out = [];
        foreach ($dataUtf8 as $row) {
            $row   = (array) $row;
            $itemn = trim((string) ($row['itemn'] ?? ''));
            if ($itemn === '') {
                continue;
            }
            $base  = explode('_', $itemn)[0] ?? $itemn;
            $out[] = [
                'image' => 'https://www.lecoqsportif.com/cdn/shop/files/' . rawurlencode($base) . '_2.jpg',
                'code'  => $itemn,
                'label' => $itemn,
                'value' => (float) ($row['salesamt'] ?? 0),
            ];
        }

        return new JsonResponse($out);
    }

    #[Route('/api/sell-out/backlog', name: 'api_sellout_backlog')]
    public function getBacklog(): JsonResponse
    {
        return $this->json($this->sellOutDashboard->getBacklogSellOut());
    }

    #[Route('/api/sell-out/evolution-semaines', name: 'api_sellout_evolution_semaines')]
    public function getEvolution12Semaines(): JsonResponse
    {
        $rows        = $this->sellOutDashboard->getEvolution12Semaines();
        $dataArray   = array_map(fn($r) => (array) $r, $this->helpers->convertArrayToUtf8($rows));
        $currentYear = (int) date('Y');
        $prevYear    = $currentYear - 1;

        $seriesN  = [];
        $seriesN1 = [];
        $allWeeks = [];

        foreach ($dataArray as $row) {
            $allWeeks[(int) $row['semaine']] = true;
        }
        ksort($allWeeks);

        foreach ($dataArray as $row) {
            $semaine = (int) $row['semaine'];
            $ca      = round((float) $row['salesamt'], 2);
            if ((int) $row['annee'] === $currentYear) {
                $seriesN[$semaine]  = $ca;
            } else {
                $seriesN1[$semaine] = $ca;
            }
        }

        $labels = [];
        $dataN  = [];
        $dataN1 = [];

        foreach (array_keys($allWeeks) as $week) {
            $labels[] = 'S' . str_pad((string) $week, 2, '0', STR_PAD_LEFT);
            $dataN[]  = $seriesN[$week]  ?? 0;
            $dataN1[] = $seriesN1[$week] ?? 0;
        }

        return new JsonResponse([
            'labels' => $labels,
            'series' => [
                ['name' => (string) $prevYear,    'data' => $dataN1],
                ['name' => (string) $currentYear, 'data' => $dataN],
            ],
        ]);
    }
}
