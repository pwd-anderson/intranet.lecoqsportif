<?php

namespace App\Controller;

use App\Entity\KpiDeckPresentation;
use App\Repository\KpiDeckPresentationRepository;
use App\Service\kpi\CategoriesByBoutiquesKpi;
use App\Service\kpi\OverviewBoutiquesKpi;
use App\Service\kpi\SalesByBoutiquesKpi;
use App\Service\kpi\SalesByFamilyKpi;
use App\Service\kpi\SalesFoKpi;
use App\Service\kpi\SalesShopByGroupKpi;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class KpiController extends AbstractController
{
    #[Route('/kpi/retail', name: 'app_kpi_retail')]
    public function retail(Request $request): Response
    {
        $currentYear = (int) date('Y');

        $year = (int) $request->query->get('year', $currentYear);
        $week = (int) $request->query->get('week', (int) date('W'));
        $weeks = array_merge(
            range((int) date('W'), 53),
            range(1, (int) date('W') - 1)
        );
        $view = $request->query->get('view', 'overview_boutiques');

        $views = [
            'overview_boutiques' => 'Overview boutiques',
            'sales_by_boutiques' => 'Sales by boutiques',
            'sales_by_family' => 'Sales by Family',
            'sales_shop_by_group' => 'Sales Shop by Group',
            'sales_fo' => 'Sales FO',
            // futur :
            // 'sales_by_country' => 'Sales by country',
        ];

        return $this->render('kpi/retail.html.twig', [
            'year' => $year,
            'week' => $week,
            'years' => range($currentYear, $currentYear - 3),
            'view' => $view,
            'views' => $views,
            'weeks' => $weeks,
        ]);
    }

    #[Route('/kpi/retail_ajax', name: 'kpi_retail_ajax', methods: ['GET'])]
    public function retailAjax(
        Request $request,
        OverviewBoutiquesKpi $overviewBoutiques,
        SalesByBoutiquesKpi $salesByBoutiques,
        SalesByFamilyKpi $salesByFamily,
        SalesShopByGroupKpi $salesShopByGroup,
        SalesFoKpi $salesFoKpi,
        KpiDeckPresentationRepository $repository,
    ): Response {
        $year = (int) $request->query->get('year', (int) date('Y'));
        $week = (int) $request->query->get('week', (int) date('W'));
        $view = $request->query->get('view', 'overview_boutiques');

        switch ($view) {

            case 'sales_by_boutiques':
                return $this->render('kpi/_sales_by_boutiques_blocks.html.twig', [
                    'sales_data' => $salesByBoutiques->getSalesByBoutique($year, $week),
                    'view' => $view,
                    'year' => $year,
                    'week' => $week,
                ]);
            case 'sales_by_family':
                return $this->render('kpi/_sales_by_family_blocks.html.twig', [
                    'data' => $salesByFamily->getData($year, $week),
                    'view' => $view,
                    'year' => $year,
                    'week' => $week,
                ]);
            case 'sales_shop_by_group':
                $commentGlobal = $repository->findOneBy([
                    'viewKey' => $view,
                    'year' => $year,
                    'week' => $week,
                    'storeKey' => 'GLOBAL',
                ]);
                return $this->render('kpi/_sales_shop_by_group.html.twig', [
                    'shops' => $salesShopByGroup->getData($year, $week)['shops'],
                    'comment_global' => $commentGlobal?->getCommentHtml(),
                    'view' => $view,
                    'year' => $year,
                    'week' => $week,
                ]);
            case 'sales_fo':
                return $this->render('kpi/_sales_fo_blocks.html.twig', [
                    'data' => $salesFoKpi->getData($year, $week),
                    'view' => $view,
                    'year' => $year,
                    'week' => $week,
                ]);
            case 'overview_boutiques':
            default:
                return $this->render('kpi/_retail_blocks.html.twig', [
                    'boutiques' => $overviewBoutiques->getBoutiquesDataFromKpi($year, $week),
                    'view' => $view,
                    'year' => $year,
                    'week' => $week,
                ]);
        }
    }

    #[Route('/kpi/comment/save', name: 'kpi_comment_save', methods: ['POST'])]
    public function saveKpiComment(
        Request $request,
        EntityManagerInterface $em,
        KpiDeckPresentationRepository $repository
    ): JsonResponse {
        $view = $request->request->get('view');
        $year = (int) $request->request->get('year');
        $week = (int) $request->request->get('week');

        // ✅ store_key devient optionnel (GLOBAL si absent)
        $storeKey = $request->request->get('store_key', 'GLOBAL');

        $html = $request->request->get('comment_html');

        if (!$view || !$year || !$week) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Paramètres manquants',
            ], 400);
        }

        $comment = $repository->findOneBy([
            'viewKey' => $view,
            'year' => $year,
            'week' => $week,
            'storeKey' => $storeKey,
        ]);

        if (!$comment) {
            $comment = new KpiDeckPresentation();
            $comment->setViewKey($view);
            $comment->setYear($year);
            $comment->setWeek($week);
            $comment->setStoreKey($storeKey);
            $comment->setCreateDate(new \DateTimeImmutable());

            $em->persist($comment);
        }

        $comment->setCommentHtml($html);
        $comment->setUpdateDate(new \DateTimeImmutable());

        $em->flush();

        return new JsonResponse([
            'success' => true,
            'updatedAt' => $comment->getUpdateDate()->format('Y-m-d H:i'),
        ]);
    }

    #[Route('/kpi/retail_test', name: 'app_kpi_retail_test')]
    public function retailTest(Request $request, CategoriesByBoutiquesKpi $categoriesByBoutiques): Response
    {
        $data = $categoriesByBoutiques->getDashboardData();
        return $this->render('kpi/retail_test.html.twig', ['shops' => $data]);
    }
}
