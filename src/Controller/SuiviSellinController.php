<?php

namespace App\Controller;

use App\Service\SuiviSellin\SuiviSellinCatalog;
use App\Service\SuiviSellin\SuiviSellinData;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * Module Suivi Sellin global : une page normale de l'intranet (/suivi-sellin) qui embarque l'application reprise du
 * template d'un Sales Manager (CSS isolé, voir suivi.css). Les données sont servies par /suivi-sellin/data/{nom}.
 */
final class SuiviSellinController extends AbstractController
{
    #[Route('/suivi-sellin', name: 'app_suivi_sellin')]
    public function index(SuiviSellinCatalog $catalog, SuiviSellinData $data, CsrfTokenManagerInterface $csrf): Response
    {
        $this->denyUnlessAccess();

        $urls = [];
        foreach ($data->names() as $name) {
            $urls[$name] = $this->generateUrl('app_suivi_sellin_data', ['name' => $name]);
        }

        return $this->render('suivi_sellin/suivi_sellin.html.twig', [
            'queries'   => $catalog->queriesByTab(),
            'endpoints' => [
                'enabled' => $catalog->enabledTabs(),
                'data'    => $urls,
                // Identité et notes de suivi (adaptateur « intégration intranet » de suivi.js).
                'me'      => $this->generateUrl('app_suivi_sellin_me'),
                'notes'   => $this->generateUrl('app_suivi_sellin_notes'),
                'csrf'    => $csrf->getToken(SuiviSellinNotesController::CSRF_ID)->getValue(),
                // Photos produit : service d'images de l'intranet (voir LecoqsportifImageFetcher).
                'imageLookup' => $this->generateUrl('lecoqsportif_image_lookup', ['article' => 'ARTICLE_PLACEHOLDER']),
                'imageBase64' => $this->generateUrl('lecoqsportif_image_base64', ['article' => 'ARTICLE_PLACEHOLDER']),
            ],
        ]);
    }

    #[Route('/suivi-sellin/data/{name}', name: 'app_suivi_sellin_data', requirements: ['name' => '[a-z0-9_]+'])]
    public function data(string $name, SuiviSellinData $data): Response
    {
        $this->denyUnlessAccess();
        if (!in_array($name, $data->names(), true)) {
            throw $this->createNotFoundException();
        }

        $built = $data->get($name);

        return new Response($built['json'], 200, [
            'Content-Type'  => 'application/json; charset=utf-8',
            'Cache-Control' => 'private, no-cache',
            'X-Built-At'    => gmdate('c', $built['builtAt']),
        ]);
    }

    private function denyUnlessAccess(): void
    {
        // Remises, avoirs et chiffres par client : accès protégé côté serveur (le rôle ne gouverne pas que le menu),
        // comme la stat Contacts Clients.
        foreach (['ROLE_SALES', 'ROLE_ADV', 'ROLE_MANAGEMENT', 'ROLE_CONTROLLING', 'ROLE_ADMIN'] as $role) {
            if ($this->isGranted($role)) {
                return;
            }
        }

        throw $this->createAccessDeniedException();
    }
}
