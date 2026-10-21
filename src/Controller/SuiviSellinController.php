<?php

namespace App\Controller;

use App\Service\ProductImages\ProductImageLibrary;
use App\Service\SuiviSellin\SuiviSellinCatalog;
use App\Service\SuiviSellin\SuiviSellinData;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
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
                // Photo de l'article sur SharePoint (à défaut son sketch) ; 404 si aucune : le navigateur passe alors à l'e-shop.
                'image'       => $this->generateUrl('app_suivi_sellin_image', ['code' => '0000000']),
                'imageSpBase64' => $this->generateUrl('app_suivi_sellin_image_base64', ['code' => '0000000']),
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

    /**
     * Image d'un article : sa photo (SharePoint, MARKETING/ASSETS/GHOST) ou, à défaut, son sketch (PRODUCTS/APPAREL), sans
     * distinction côté écran. 404 si l'article n'a ni l'une ni l'autre. Voir ProductImageLibrary.
     */
    #[Route('/suivi-sellin/image/{code}', name: 'app_suivi_sellin_image', requirements: ['code' => '\d{7}'], methods: ['GET'])]
    public function image(string $code, ProductImageLibrary $images): Response
    {
        $this->denyUnlessAccess();
        $file = $images->file($code);
        if ($file === null) {
            return new Response('', Response::HTTP_NOT_FOUND, ['Cache-Control' => 'private, max-age=3600']);
        }

        $response = new BinaryFileResponse($file['path']);
        $response->headers->set('Content-Type', $file['mime']);
        $response->setPrivate();
        $response->setMaxAge(86400);

        return $response;
    }

    /** Même image en base64 pour la trame PowerPoint (le navigateur la remet dans un canvas). */
    #[Route('/suivi-sellin/image/{code}/base64', name: 'app_suivi_sellin_image_base64', requirements: ['code' => '\d{7}'], methods: ['GET'])]
    public function imageBase64(string $code, ProductImageLibrary $images): JsonResponse
    {
        $this->denyUnlessAccess();
        $file = $images->file($code);
        if ($file === null) {
            return new JsonResponse(['success' => false]);
        }

        return new JsonResponse([
            'success'   => true,
            'base64'    => base64_encode((string) file_get_contents($file['path'])),
            'imageType' => $file['mime'] === 'image/png' ? 'png' : ($file['mime'] === 'image/webp' ? 'webp' : 'jpeg'),
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
