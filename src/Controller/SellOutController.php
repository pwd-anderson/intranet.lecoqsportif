<?php

namespace App\Controller;

use App\Service\SellOut;
use App\Service\Tools\Helpers;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Stat "Sell Out par Client" : vue globale par client et vue détail par article,
 * en quantités hebdomadaires. Les colonnes semaine sont construites côté
 * JavaScript, comme dans Ventes par Client pour les mois.
 */
final class SellOutController extends AbstractController
{
    public function __construct(private SellOut $sellOut) {}

    private function resolveYear(Request $request): int
    {
        $year = (int) $request->query->get('year', (string) date('Y'));

        return ($year < 2000 || $year > 2100) ? (int) date('Y') : $year;
    }

    #[Route('/sales/sell_out_client', name: 'app_sales_sell_out_client')]
    public function index(Request $request): Response
    {
        return $this->render('sales/sell_out_client.html.twig', [
            'title'       => 'Sell Out par Client',
            'dataUrl'     => $this->generateUrl('sales_sell_out_client_json'),
            'detailUrl'   => $this->generateUrl('app_sales_sell_out_client_detail'),
            'currentYear' => $this->resolveYear($request),
            'annees'      => $this->sellOut->getAnneesDisponibles(),
        ]);
    }

    #[Route('/sales/sell_out_client_json', name: 'sales_sell_out_client_json')]
    public function indexJson(Request $request, Helpers $helpers): JsonResponse
    {
        $data = $this->sellOut->getVentesParClient($this->resolveYear($request));

        return new JsonResponse([
            'weeks' => $data['weeks'],
            'rows'  => $helpers->convertArrayToUtf8($data['rows']),
        ]);
    }

    #[Route('/sales/sell_out_client_detail', name: 'app_sales_sell_out_client_detail')]
    public function detail(Request $request): Response
    {
        $customerId = trim((string) $request->query->get('customer', ''));

        return $this->render('sales/sell_out_client_detail.html.twig', [
            'title'       => 'Sell Out — ' . $this->sellOut->getNomClient($customerId),
            'customerId'  => $customerId,
            'dataUrl'     => $this->generateUrl('sales_sell_out_client_detail_json'),
            'backUrl'     => $this->generateUrl('app_sales_sell_out_client'),
            'currentYear' => $this->resolveYear($request),
        ]);
    }

    #[Route('/sales/sell_out_client_detail_json', name: 'sales_sell_out_client_detail_json')]
    public function detailJson(Request $request, Helpers $helpers): JsonResponse
    {
        $customerId = trim((string) $request->query->get('customer', ''));
        $data       = $this->sellOut->getVentesParClientDetail($this->resolveYear($request), $customerId);

        return new JsonResponse([
            'weeks' => $data['weeks'],
            'rows'  => $helpers->convertArrayToUtf8($data['rows']),
        ]);
    }
}
