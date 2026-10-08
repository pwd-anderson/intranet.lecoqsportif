<?php

namespace App\Controller\Module;

use App\Service\Oc\OcDemoData;
use App\Service\Oc\OcX3Lookup;
use App\Service\Tools\Helpers;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Module Offres Commerciales (OC) — version visuelle (v0) : aucune donnée réelle, aucune écriture.
 * Accès : ROLE_SALES, ROLE_MANAGEMENT, ROLE_CONTROLLING (super-users) et ROLE_ADMIN.
 */
#[Route('/oc')]
#[IsGranted(new \Symfony\Component\ExpressionLanguage\Expression(
    'is_granted("ROLE_SALES") or is_granted("ROLE_MANAGEMENT") or is_granted("ROLE_CONTROLLING") or is_granted("ROLE_ADMIN")'
))]
final class OcController extends AbstractController
{
    public function __construct(private readonly OcDemoData $demo)
    {
    }

    #[Route('', name: 'app_oc_index', methods: ['GET'])]
    public function index(): Response
    {
        $rows = array_map(static fn (array $o): array => [
            'id' => $o['id'],
            'number' => $o['number'],
            'type' => $o['type'],
            'customers' => implode(', ', $o['customers']),
            'lines' => $o['lines'],
            'status' => $o['status']->value,
            'statusLabel' => $o['status']->label(),
            'createdBy' => $o['createdBy'],
            'createdAt' => $o['createdAt'],
            'startDate' => $o['startDate'],
            'endDate' => $o['endDate'],
        ], $this->demo->offers());

        return $this->render('oc/index.html.twig', ['rows' => $rows]);
    }

    #[Route('/new', name: 'app_oc_new', methods: ['GET'])]
    public function new(): Response
    {
        return $this->render('oc/new.html.twig', [
        ]);
    }

    /** Autocomplétion client (X3_LCS) : GET /oc/api/customers?q=... */
    #[Route('/api/customers', name: 'app_oc_api_customers', methods: ['GET'])]
    public function customers(Request $request, OcX3Lookup $lookup, Helpers $helpers): JsonResponse
    {
        $rows = $lookup->searchCustomers((string) $request->query->get('q', ''));

        return new JsonResponse($helpers->convertArrayToUtf8($rows));
    }

    /** Remise client (%) : GET /oc/api/customers/{code}/discount -> {"discount": 5.0, "startDate": "...", "endDate": "..."} ou {"discount": null} */
    #[Route('/api/customers/{code}/discount', name: 'app_oc_api_customer_discount', requirements: ['code' => '[A-Za-z0-9_-]+'], methods: ['GET'])]
    public function customerDiscount(string $code, OcX3Lookup $lookup): JsonResponse
    {
        $row = $lookup->getCustomerDiscount($code);

        return new JsonResponse($row === null
            ? ['discount' => null, 'startDate' => null, 'endDate' => null]
            : ['discount' => (float) $row['REMISE'], 'startDate' => $row['DATE_DEBUT'], 'endDate' => $row['DATE_FIN']]);
    }

    /** Code tarif / tarif / MSRP d'un article pour un client : GET /oc/api/prices?customer=...&article=... */
    #[Route('/api/prices', name: 'app_oc_api_prices', methods: ['GET'])]
    public function prices(Request $request, OcX3Lookup $lookup): JsonResponse
    {
        $row = $lookup->getArticlePrices(
            (string) $request->query->get('customer', ''),
            (string) $request->query->get('article', ''),
        );

        $num = static fn (mixed $v): ?float => $v === null || $v === '' ? null : (float) $v;

        return new JsonResponse([
            'found' => $row !== null,
            'tariffCode' => $row['CODE_TARIF'] ?? null,
            'currency' => $row['DEVISE'] ?? null,
            'collection' => $row['COLLECTION'] ?? null,
            'tariff' => $num($row['TARIF'] ?? null),
            'msrp' => $num($row['MSRP'] ?? null),
        ]);
    }

    /** Autocomplétion article parent (X3_LCS) : GET /oc/api/articles?q=... */
    #[Route('/api/articles', name: 'app_oc_api_articles', methods: ['GET'])]
    public function articles(Request $request, OcX3Lookup $lookup, Helpers $helpers): JsonResponse
    {
        $rows = $lookup->searchArticles((string) $request->query->get('q', ''));

        return new JsonResponse($helpers->convertArrayToUtf8($rows));
    }

    #[Route('/{id}', name: 'app_oc_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(int $id): Response
    {
        $offer = $this->demo->offer($id) ?? throw $this->createNotFoundException();

        return $this->render('oc/show.html.twig', ['offer' => $offer]);
    }
}
