<?php

namespace App\Controller;

use App\Repository\AggridOptionRepository;
use App\Service\Achat;
use App\Service\AgGrid\AgGridColumnBuilder;
use App\Service\Tools\Helpers;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class AchatController extends AbstractController
{
    public function __construct(
        private AggridOptionRepository $aggridOptionRepository,
        private AgGridColumnBuilder $columnBuilder,
    ) {}

    // ################## ROUTES ALIAS #####################
    #[Route('/achat/backlog_fournisseur', name: 'app_backlog_fournisseur')]
    public function backlogFournisseurAlias(): Response
    {
        return $this->achatGeneric('backlog_fournisseur');
    }

    #[Route('/achat/backlog_fournisseur_x3', name: 'app_backlog_fournisseur_x3')]
    public function backlogFournisseurX3Alias(): Response
    {
        return $this->achatGeneric('backlog_fournisseur_x3');
    }

    #[Route('/achat/reception_fournisseur', name: 'app_reception_fournisseur')]
    public function receptionFournisseurAlias(): Response
    {
        return $this->achatGeneric('reception_fournisseur');
    }

    // ################## ROUTES JSON (inchangées) #####################

    // Routes JSON
    #[Route('/achat/backlog_fournisseur_json', name: 'backlog_fournisseur_json')]
    public function backlogFournisseurJson(Achat $achat, Helpers $helpers): JsonResponse
    {
        $data = $achat->getBAcklogFournisseurNavision();
        $dataUtf8 = $helpers->convertArrayToUtf8($data);

        return new JsonResponse($dataUtf8);
    }

    #[Route('/achat/backlog_fournisseur_x3_json', name: 'backlog_fournisseur_x3_json')]
    public function backlogFournisseurX3Json(Achat $achat, Helpers $helpers): JsonResponse
    {
        $data = $achat->getBacklogFournisseur();
        $dataUtf8 = $helpers->convertArrayToUtf8($data);

        return new JsonResponse($dataUtf8);
    }

    #[Route('/achat/backlog_fournisseur_intersites_json', name: 'backlog_fournisseur_intersites_json')]
    public function backlogFournisseurIntersitesJson(Achat $achat, Helpers $helpers): JsonResponse
    {
        $data = $achat->getBacklogFournisseurIntersites();
        $dataUtf8 = $helpers->convertArrayToUtf8($data);

        return new JsonResponse($dataUtf8);
    }

    #[Route('/achat/reception_fournisseur_json', name: 'reception_fournisseur_json')]
    public function receptionFournisseurJson(Achat $achat, Helpers $helpers): JsonResponse
    {
        $data = $achat->getReceptionFournisseur();
        $dataUtf8 = $helpers->convertArrayToUtf8($data);

        return new JsonResponse($dataUtf8);
    }


    #[Route(
        '/achat/{type}',
        name: 'app_achat_generic',
        requirements: ['type' => 'backlog_fournisseur|backlog_fournisseur_x3|reception_fournisseur']
    )]
    public function achatGeneric(string $type): Response
    {
        $config = [
            'backlog_fournisseur' => [
                'gridName' => 'backlog_fournisseur_grid',
                'title' => 'Backlog Fournisseur (Nav.)',
                'jsonRoute' => 'backlog_fournisseur_json',
            ],
            'backlog_fournisseur_x3' => [
                'gridName' => 'backlog_fournisseur_x3_grid',
                'title' => 'Backlog Fournisseur',
                'jsonRoute' => 'backlog_fournisseur_x3_json',
            ],
            'reception_fournisseur' => [
                'gridName' => 'reception_fournisseur_grid',
                'title' => 'Réception Fournisseur',
                'jsonRoute' => 'reception_fournisseur_json',
            ],
        ];

        if (!isset($config[$type])) {
            throw $this->createNotFoundException(sprintf('Unknown achat type "%s"', $type));
        }

        $gridConfig = $config[$type];

        $agridOptions = $this->aggridOptionRepository->findBy(
            ['gridName' => $gridConfig['gridName']],
            ['orderIndex' => 'ASC']
        );

        $grid = $this->columnBuilder->build($agridOptions);

        return $this->render('achat/achat_generic.html.twig', [
            'title' => $gridConfig['title'],
            'columns' => $grid['columns'],
            'numericColumns' => $grid['numericColumns'],
            'integerColumns' => $grid['integerColumns'],
            'totalColumns' => $grid['totalColumns'],
            'dataUrl' => $this->generateUrl($gridConfig['jsonRoute']),
            'intersitesUrl' => $type === 'backlog_fournisseur_x3'   // 🆕
                ? $this->generateUrl('backlog_fournisseur_intersites_json')
                : null,
            'type' => $type,
        ]);
    }
}
