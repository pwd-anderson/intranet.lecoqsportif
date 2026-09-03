<?php

namespace App\Controller;

use App\Entity\SalesWebService;
use App\Entity\SoaHistory;
use App\Entity\SoaRequest;
use App\Entity\SoaRequestDocument;
use App\Entity\SoaRequestProduct;
use App\Repository\SoaHistoryRepository;
use App\Repository\SoaRequestDocumentRepository;
use App\Repository\SoaRequestRepository;
use App\Repository\SoaStatusRepository;
use App\Service\SoaMailer;
use App\Service\Tools\Helpers;
use App\Service\SoaX3Service;
use App\Service\Webservice\XmlBuilder;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
final class SoaController extends AbstractController
{
    // ── Pages ────────────────────────────────────────────────────────────────

    #[Route('/soa', name: 'app_soa_index')]
    public function index(): Response
    {
        return $this->render('soa/index.html.twig');
    }

    #[Route('/soa/new', name: 'app_soa_new')]
    public function new(SoaRequestRepository $soaRepo): Response
    {
        $numero = $soaRepo->generateNumero();

        return $this->render('soa/new.html.twig', [
            'numero'       => $numero,
            'representant' => $this->getUser()->getUserIdentifier(),
        ]);
    }

    #[Route('/soa/{id}/edit', name: 'app_soa_edit', requirements: ['id' => '\d+'])]
    public function edit(int $id, SoaRequestRepository $soaRepo): Response
    {
        $soa = $soaRepo->find($id);

        if (!$soa) {
            throw $this->createNotFoundException('SOA introuvable.');
        }

        $currentUser    = $this->getUser()->getUserIdentifier();
        $isManagement   = $this->isGranted('ROLE_ADMIN') || $this->isGranted('ROLE_MANAGEMENT');
        $isRepresentant = $soa->getRepresentant() === $currentUser;
        $statusCode     = $soa->getStatus()->getCode();

        $mode = match($statusCode) {
            'brouillon'                              => $isRepresentant || $isManagement ? 'form'       : null,
            'attente_validation', 'attente_val_finale' => $isManagement                   ? 'validation' : null,
            'valide_direction'                       => $isRepresentant                   ? 'preuves'    : null,
            default                                  => null,
        };

        if ($mode === null) {
            return $this->redirectToRoute('app_soa_show', ['id' => $id]);
        }

        return $this->render('soa/edit.html.twig', [
            'soa_id'       => $id,
            'mode'         => $mode,
            'is_management'=> $isManagement,
        ]);
    }

    #[Route('/soa/{id}', name: 'app_soa_show', requirements: ['id' => '\d+'])]
    public function show(int $id, SoaRequestRepository $soaRepo): Response
    {
        $soa = $soaRepo->find($id);

        if (!$soa) {
            throw $this->createNotFoundException('SOA introuvable.');
        }

        $currentUser   = $this->getUser()->getUserIdentifier();
        $isManagement  = $this->isGranted('ROLE_ADMIN') || $this->isGranted('ROLE_MANAGEMENT');
        $isRepresentant = $soa->getRepresentant() === $currentUser;

        return $this->render('soa/show.html.twig', [
            'soa'            => $soa,
            'is_management'  => $isManagement,
            'is_representant' => $isRepresentant,
        ]);
    }

    // ── Download preuve ───────────────────────────────────────────────────────

    #[Route('/soa/{id}/proof/{docId}/download', name: 'soa_proof_download', requirements: ['id' => '\d+', 'docId' => '\d+'])]
    public function downloadProof(int $id, int $docId, SoaRequestDocumentRepository $docRepo): Response
    {
        $doc = $docRepo->find($docId);

        if (!$doc || $doc->getSoaRequest()->getId() !== $id) {
            throw $this->createNotFoundException('Document introuvable.');
        }

        $filePath = $this->getParameter('kernel.project_dir') . '/var/uploads/' . $doc->getChemin();

        if (!file_exists($filePath)) {
            throw $this->createNotFoundException('Fichier introuvable.');
        }

        $response = new BinaryFileResponse($filePath);
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $doc->getNomFichier());

        return $response;
    }

    // ── API X3 ───────────────────────────────────────────────────────────────

    #[Route('/soa/api/clients', name: 'api_soa_clients', methods: ['GET'])]
    public function searchClients(Request $request, SoaX3Service $x3, Helpers $helpers): JsonResponse
    {
        $q = trim($request->query->get('q', ''));

        if (mb_strlen($q) < 2) {
            return $this->json([]);
        }

        return $this->json($helpers->convertArrayToUtf8($x3->searchClients($q)));
    }

    #[Route('/soa/api/articles', name: 'api_soa_articles', methods: ['GET'])]
    public function searchArticles(Request $request, SoaX3Service $x3, Helpers $helpers): JsonResponse
    {
        $q = trim($request->query->get('q', ''));

        if (mb_strlen($q) < 2) {
            return $this->json([]);
        }

        return $this->json($helpers->convertArrayToUtf8($x3->searchArticles($q)));
    }

    #[Route('/soa/api/articles/prix', name: 'api_soa_article_prix', methods: ['GET'])]
    public function getArticlePrix(Request $request, SoaX3Service $x3): JsonResponse
    {
        $modele = trim($request->query->get('modele', ''));

        if ($modele === '') {
            return $this->json(['prix' => null]);
        }

        return $this->json(['prix' => $x3->getPrixAchat($modele)]);
    }

    #[Route('/soa/api/ca-facture', name: 'api_soa_ca_facture', methods: ['GET'])]
    public function getCaFacture(Request $request, SoaX3Service $x3): JsonResponse
    {
        $client = trim($request->query->get('client', ''));

        if ($client === '') {
            return $this->json(['montant' => null]);
        }

        return $this->json(['montant' => $x3->getCaFactureClient($client)]);
    }

    #[Route('/soa/api/list', name: 'api_soa_list', methods: ['GET'])]
    public function list(SoaRequestRepository $soaRepo): JsonResponse
    {
        $user    = $this->getUser()->getUserIdentifier();
        $isAdmin = $this->isGranted('ROLE_ADMIN') || $this->isGranted('ROLE_MANAGEMENT');
        $rows    = $isAdmin ? $soaRepo->findAllForList() : $soaRepo->findByRepresentant($user);

        $data = array_map(fn($soa) => [
            'id'            => $soa->getId(),
            'numero'        => $soa->getNumero(),
            'representant'  => $soa->getRepresentant(),
            'client_code'   => $soa->getClientCode(),
            'client_nom'    => $soa->getClientNom(),
            'date_debut'    => $soa->getDateDebut()->format('Y-m-d'),
            'date_fin'      => $soa->getDateFin()->format('Y-m-d'),
            'montant_total' => array_reduce(
                $soa->getProducts()->toArray(),
                fn($carry, $p) => $carry + (float) $p->getMontantMax(),
                0.0
            ),
            'statut'        => $soa->getStatus()->getCode(),
            'statut_label'  => $soa->getStatus()->getLabel(),
            'created_at'    => $soa->getCreatedAt()->format('Y-m-d'),
        ], $rows);

        return $this->json($data);
    }

    #[Route('/soa/api/{id}', name: 'api_soa_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function apiShow(int $id, SoaRequestRepository $soaRepo, SoaHistoryRepository $historyRepo, SoaRequestDocumentRepository $docRepo): JsonResponse
    {
        $soa = $soaRepo->find($id);
        if (!$soa) {
            return $this->json(['error' => 'SOA introuvable.'], 404);
        }

        $products = array_map(fn($p) => [
            'id'              => $p->getId(),
            'article_code'    => $p->getArticleCode(),
            'article_nom'     => $p->getArticleNom(),
            'prix_achat'      => $p->getPrixAchat() !== null ? (float) $p->getPrixAchat() : null,
            'qte_max'         => $p->getQteMax(),
            'qte_vendue'      => $p->getQteVendue(),
            'montant_soa'     => (float) $p->getMontantSoa(),
            'devise'          => $p->getDevise(),
            'montant_max'     => (float) $p->getMontantMax(),
            'ca_facture_annee'=> $p->getCaFactureAnnee() !== null ? (float) $p->getCaFactureAnnee() : null,
            'roi'             => $p->getRoi() !== null ? (float) $p->getRoi() : null,
        ], $soa->getProducts()->toArray());

        $preuves = array_values(array_map(fn($doc) => [
            'id'           => $doc->getId(),
            'nom_fichier'  => $doc->getNomFichier(),
            'taille'       => $doc->getTaille(),
            'uploaded_by'  => $doc->getUploadedBy(),
            'uploaded_at'  => $doc->getUploadedAt()->format('d/m/Y H:i'),
            'download_url' => $this->generateUrl('soa_proof_download', ['id' => $soa->getId(), 'docId' => $doc->getId()]),
        ], array_filter($soa->getDocuments()->toArray(), fn($d) => $d->getType() === SoaRequestDocument::TYPE_PREUVE)));

        return $this->json([
            'id'            => $soa->getId(),
            'numero'        => $soa->getNumero(),
            'titre'         => $soa->getTitre(),
            'representant'  => $soa->getRepresentant(),
            'statut'        => $soa->getStatus()->getCode(),
            'statut_label'  => $soa->getStatus()->getLabel(),
            'client_code'   => $soa->getClientCode(),
            'client_nom'    => $soa->getClientNom(),
            'client_langue' => $soa->getClientLangue(),
            'client_devise' => $soa->getClientDevise(),
            'client_emails' => $soa->getClientEmails(),
            'date_debut'    => $soa->getDateDebut()->format('Y-m-d'),
            'date_fin'      => $soa->getDateFin()->format('Y-m-d'),
            'focus_produit' => $soa->getFocusProduit(),
            'commentaire'   => $soa->getCommentaire(),
            'created_at'    => $soa->getCreatedAt()->format('d/m/Y'),
            'updated_at'    => $soa->getUpdatedAt()->format('d/m/Y H:i'),
            'produits'      => $products,
            'preuves'       => $preuves,
            'historique'    => array_map(fn($h) => [
                'user'         => $h->getUser(),
                'statut'       => $h->getStatut(),
                'statut_label' => $h->getStatutLabel(),
                'date'         => $h->getCreatedAt()->format('d/m/Y H:i'),
            ], $historyRepo->findBy(['soaRequest' => $soa], ['createdAt' => 'ASC'])),
        ]);
    }

    // ── API Transition (valider / refuser) ────────────────────────────────────

    #[Route('/soa/api/{id}/transition', name: 'api_soa_transition', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function transition(
        int                    $id,
        Request                $request,
        SoaRequestRepository   $soaRepo,
        SoaStatusRepository    $statusRepo,
        SoaHistoryRepository   $historyRepo,
        SoaMailer              $mailer,
        EntityManagerInterface $em,
    ): JsonResponse {
        if (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_MANAGEMENT')) {
            return $this->json(['error' => 'Accès refusé.'], 403);
        }

        $soa = $soaRepo->find($id);
        if (!$soa) {
            return $this->json(['error' => 'SOA introuvable.'], 404);
        }

        $data   = json_decode($request->getContent(), true);
        $action = $data['action'] ?? '';

        $currentCode = $soa->getStatus()->getCode();

        $transitions = [
            'attente_validation' => ['valider' => 'valide_direction', 'refuser' => 'refuse'],
            'attente_val_finale' => ['valider' => 'archive',          'refuser' => 'refuse'],
        ];

        if (!isset($transitions[$currentCode][$action])) {
            return $this->json(['error' => "Transition '{$action}' non autorisée depuis '{$currentCode}'."], 400);
        }

        $newCode   = $transitions[$currentCode][$action];
        $newStatus = $statusRepo->findByCode($newCode);
        if (!$newStatus) {
            return $this->json(['error' => "Statut cible inconnu : {$newCode}."], 500);
        }

        $soa->setStatus($newStatus);
        $em->flush();

        $history = new SoaHistory();
        $history->setSoaRequest($soa);
        $history->setUser($this->getUser()->getUserIdentifier());
        $history->setStatut($newCode);
        $history->setStatutLabel($newStatus->getLabel());
        $em->persist($history);
        $em->flush();

        $xmlError = null;
        if ($newCode === 'archive') {
            try {
                $ws = new SalesWebService();
                $ws->setName('WSCRESIH');
                $ws->setParameter(XmlBuilder::buildSOA($soa));
                $ws->setSoaRequestId($soa->getId());
                $em->persist($ws);
                $em->flush();
            } catch (\Throwable $e) {
                $xmlError = $e->getMessage();
            }
        }

        try {
            if ($newCode === 'valide_direction') {
                $mailer->sendValidationRepresentant($soa);
                $mailer->sendContratClient($soa);
            } elseif ($newCode === 'refuse') {
                $mailer->sendRefus($soa);
            } elseif ($newCode === 'archive') {
                $mailer->sendArchive($soa);
            }
        } catch (\Throwable) {}

        return $this->json([
            'statut'       => $newCode,
            'statut_label' => $newStatus->getLabel(),
            'xml_error'    => $xmlError,
        ]);
    }

    // ── API Upload preuve ─────────────────────────────────────────────────────

    #[Route('/soa/api/{id}/proof/upload', name: 'api_soa_upload_proof', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function uploadProof(
        int                    $id,
        Request                $request,
        SoaRequestRepository   $soaRepo,
        EntityManagerInterface $em,
    ): JsonResponse {
        $soa = $soaRepo->find($id);
        if (!$soa) {
            return $this->json(['error' => 'SOA introuvable.'], 404);
        }

        if ($soa->getStatus()->getCode() !== 'valide_direction') {
            return $this->json(['error' => 'Upload non autorisé dans ce statut.'], 403);
        }

        if ($soa->getRepresentant() !== $this->getUser()->getUserIdentifier()) {
            return $this->json(['error' => 'Accès refusé.'], 403);
        }

        $file = $request->files->get('file');
        if (!$file) {
            return $this->json(['error' => 'Aucun fichier reçu.'], 400);
        }

        $projectDir   = $this->getParameter('kernel.project_dir');
        $dir          = $projectDir . '/var/uploads/soa/' . $id . '/';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $originalName = $file->getClientOriginalName();
        $safeName     = uniqid('proof_') . '.' . $file->getClientOriginalExtension();
        $mimeType     = $file->getMimeType() ?? 'application/octet-stream';
        $taille       = $file->getSize() ?? 0;
        $file->move($dir, $safeName);

        $doc = new SoaRequestDocument();
        $doc->setSoaRequest($soa);
        $doc->setType(SoaRequestDocument::TYPE_PREUVE);
        $doc->setNomFichier($originalName);
        $doc->setChemin('soa/' . $id . '/' . $safeName);
        $doc->setMimeType($mimeType);
        $doc->setTaille($taille);
        $doc->setUploadedBy($this->getUser()->getUserIdentifier());

        $em->persist($doc);
        $em->flush();

        return $this->json([
            'id'           => $doc->getId(),
            'nom_fichier'  => $doc->getNomFichier(),
            'taille'       => $doc->getTaille(),
            'uploaded_at'  => $doc->getUploadedAt()->format('d/m/Y H:i'),
            'download_url' => $this->generateUrl('soa_proof_download', ['id' => $id, 'docId' => $doc->getId()]),
        ]);
    }

    // ── API Delete preuve ─────────────────────────────────────────────────────

    #[Route('/soa/api/{id}/proof/{docId}/delete', name: 'api_soa_delete_proof', methods: ['POST'], requirements: ['id' => '\d+', 'docId' => '\d+'])]
    public function deleteProof(
        int                    $id,
        int                    $docId,
        SoaRequestRepository   $soaRepo,
        SoaRequestDocumentRepository $docRepo,
        EntityManagerInterface $em,
    ): JsonResponse {
        $soa = $soaRepo->find($id);
        if (!$soa) {
            return $this->json(['error' => 'SOA introuvable.'], 404);
        }

        if ($soa->getStatus()->getCode() !== 'valide_direction') {
            return $this->json(['error' => 'Suppression non autorisée dans ce statut.'], 403);
        }

        if ($soa->getRepresentant() !== $this->getUser()->getUserIdentifier()) {
            return $this->json(['error' => 'Accès refusé.'], 403);
        }

        $doc = $docRepo->find($docId);
        if (!$doc || $doc->getSoaRequest()->getId() !== $id) {
            return $this->json(['error' => 'Document introuvable.'], 404);
        }

        $filePath = $this->getParameter('kernel.project_dir') . '/var/uploads/' . $doc->getChemin();
        if (file_exists($filePath)) {
            unlink($filePath);
        }

        $em->remove($doc);
        $em->flush();

        return $this->json(['ok' => true]);
    }

    // ── API Soumettre les preuves ─────────────────────────────────────────────

    #[Route('/soa/api/{id}/submit-preuves', name: 'api_soa_submit_preuves', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function submitPreuves(
        int                    $id,
        Request                $request,
        SoaRequestRepository   $soaRepo,
        SoaStatusRepository    $statusRepo,
        SoaHistoryRepository   $historyRepo,
        SoaMailer              $mailer,
        EntityManagerInterface $em,
    ): JsonResponse {
        $soa = $soaRepo->find($id);
        if (!$soa) {
            return $this->json(['error' => 'SOA introuvable.'], 404);
        }

        if ($soa->getStatus()->getCode() !== 'valide_direction') {
            return $this->json(['error' => 'Soumission non autorisée dans ce statut.'], 403);
        }

        if ($soa->getRepresentant() !== $this->getUser()->getUserIdentifier()) {
            return $this->json(['error' => 'Accès refusé.'], 403);
        }

        // Vérifier qu'il y a au moins un document preuve
        $preuves = array_filter($soa->getDocuments()->toArray(), fn($d) => $d->getType() === SoaRequestDocument::TYPE_PREUVE);
        if (empty($preuves)) {
            return $this->json(['error' => 'Vous devez uploader au moins un justificatif avant de soumettre.'], 400);
        }

        $data   = json_decode($request->getContent(), true);
        $lignes = $data['lignes'] ?? [];

        // Indexer les lignes reçues par product_id
        $qtesById = [];
        foreach ($lignes as $ligne) {
            $qtesById[(int) $ligne['product_id']] = (int) $ligne['qte_vendue'];
        }

        // Valider et appliquer les quantités
        foreach ($soa->getProducts() as $product) {
            $qte = $qtesById[$product->getId()] ?? 0;
            if ($qte <= 0 || $qte > $product->getQteMax()) {
                return $this->json([
                    'error' => "Quantité invalide pour l'article {$product->getArticleCode()} (doit être entre 1 et {$product->getQteMax()}).",
                ], 400);
            }
            $product->setQteVendue($qte);
        }

        $newStatus = $statusRepo->findByCode('attente_val_finale');
        if (!$newStatus) {
            return $this->json(['error' => 'Statut cible introuvable.'], 500);
        }

        $soa->setStatus($newStatus);
        $em->flush();

        $history = new SoaHistory();
        $history->setSoaRequest($soa);
        $history->setUser($this->getUser()->getUserIdentifier());
        $history->setStatut('attente_val_finale');
        $history->setStatutLabel($newStatus->getLabel());
        $em->persist($history);
        $em->flush();

        try {
            $mailer->sendValidationFinaleRepresentant($soa);
        } catch (\Throwable) {}

        return $this->json(['statut' => 'attente_val_finale', 'statut_label' => $newStatus->getLabel()]);
    }

    // ── API Save (brouillon / soumettre) ──────────────────────────────────────

    #[Route('/soa/api/save', name: 'api_soa_save', methods: ['POST'])]
    public function save(
        Request                $request,
        SoaRequestRepository   $soaRepo,
        SoaStatusRepository    $statusRepo,
        SoaHistoryRepository   $historyRepo,
        SoaMailer              $mailer,
        EntityManagerInterface $em,
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return $this->json(['error' => 'Données invalides.'], 400);
        }

        $required = ['numero', 'client_code', 'client_nom', 'date_debut', 'date_fin', 'statut'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                return $this->json(['error' => "Champ obligatoire manquant : {$field}."], 400);
            }
        }

        $statusCode = $data['statut'];

        // Seuls brouillon et attente_validation sont autorisés via ce endpoint
        if (!in_array($statusCode, ['brouillon', 'attente_validation'], true)) {
            return $this->json(['error' => "Statut non autorisé via ce endpoint : {$statusCode}."], 400);
        }

        $status = $statusRepo->findByCode($statusCode);
        if (!$status) {
            return $this->json(['error' => "Statut inconnu : {$statusCode}."], 400);
        }

        $numero = $data['numero'];
        $soa    = $soaRepo->findOneBy(['numero' => $numero]);

        if (!$soa) {
            $soa = new SoaRequest();
            $soa->setNumero($numero);
            $soa->setRepresentant($this->getUser()->getUserIdentifier());
        } else {
            // Vérifier que le SOA est encore modifiable
            if ($soa->getStatus()->getCode() !== 'brouillon') {
                return $this->json(['error' => 'Ce SOA ne peut plus être modifié.'], 403);
            }
        }

        $previousStatut = $soa->getId() ? $soa->getStatus()->getCode() : null;

        $soa->setStatus($status);
        $soa->setClientCode($data['client_code']);
        $soa->setClientNom($data['client_nom']);
        $soa->setClientLangue($data['client_langue'] ?? '');
        $soa->setClientDevise($data['client_devise'] ?? 'EUR');
        $soa->setClientEmails($data['client_emails'] ?? []);
        $soa->setTitre($data['titre'] ?? ($numero . ' — ' . $data['client_nom']));
        $soa->setDateDebut(new \DateTime($data['date_debut']));
        $soa->setDateFin(new \DateTime($data['date_fin']));
        $soa->setFocusProduit($data['focus_produit'] ?? null);
        $soa->setCommentaire($data['commentaire'] ?? null);

        foreach ($soa->getProducts() as $p) {
            $soa->removeProduct($p);
        }

        foreach ($data['lignes'] ?? [] as $ligne) {
            if (empty($ligne['article_code'])) {
                continue;
            }

            $product = new SoaRequestProduct();
            $product->setArticleCode($ligne['article_code']);
            $product->setArticleNom($ligne['article_nom'] ?? '');
            $product->setPrixAchat(isset($ligne['prix_achat']) && $ligne['prix_achat'] !== '' ? (string) $ligne['prix_achat'] : null);
            $product->setQteMax((int) ($ligne['qte_max'] ?? 0));
            $product->setMontantSoa((string) ($ligne['montant_soa'] ?? '0'));
            $product->setDevise($data['client_devise'] ?? 'EUR');
            $product->setCaFactureAnnee(isset($ligne['ca_facture']) && $ligne['ca_facture'] !== '' ? (string) $ligne['ca_facture'] : null);
            $product->recalculate();

            $soa->addProduct($product);
        }

        $em->persist($soa);
        $em->flush();

        $history = new SoaHistory();
        $history->setSoaRequest($soa);
        $history->setUser($this->getUser()->getUserIdentifier());
        $history->setStatut($soa->getStatus()->getCode());
        $history->setStatutLabel($soa->getStatus()->getLabel());
        $em->persist($history);
        $em->flush();

        try {
            $newStatut = $soa->getStatus()->getCode();

            if ($newStatut === 'attente_validation' && $previousStatut !== 'attente_validation') {
                $mailer->sendSoumissionDirection($soa);
            }
        } catch (\Throwable) {}

        return $this->json(['id' => $soa->getId(), 'numero' => $soa->getNumero()]);
    }
}
