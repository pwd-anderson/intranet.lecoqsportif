<?php

namespace App\Controller;

use App\Entity\MdfHistory;
use App\Entity\MdfRequest;
use App\Entity\MdfRequestDocument;
use App\Entity\SalesWebService;
use App\Repository\MdfHistoryRepository;
use App\Repository\MdfRequestDocumentRepository;
use App\Repository\MdfRequestRepository;
use App\Repository\MdfStatusRepository;
use App\Service\MdfMailer;
use App\Service\MdfX3Service;
use App\Service\Tools\GraphMailer;
use App\Service\Tools\Helpers;
use App\Service\Webservice\XmlBuilder;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
final class MdfController extends AbstractController
{
    // ── Pages ────────────────────────────────────────────────────────────────

    #[Route('/mdf', name: 'app_mdf_index')]
    public function index(): Response
    {
        return $this->render('mdf/index.html.twig');
    }

    #[Route('/mdf/new', name: 'app_mdf_new')]
    public function new(MdfRequestRepository $mdfRepo): Response
    {
        if (!$this->isGranted('ROLE_SALES') && !$this->isGranted('ROLE_MARKETING') && !$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_MANAGEMENT')) {
            throw $this->createAccessDeniedException('Accès refusé.');
        }

        $numero = $mdfRepo->generateNumero();

        return $this->render('mdf/new.html.twig', [
            'numero'       => $numero,
            'representant' => $this->getUser()->getUserIdentifier(),
        ]);
    }

    #[Route('/mdf/{id}/edit', name: 'app_mdf_edit', requirements: ['id' => '\d+'])]
    public function edit(int $id, MdfRequestRepository $mdfRepo): Response
    {
        $mdf = $mdfRepo->find($id);

        if (!$mdf) {
            throw $this->createNotFoundException('MDF introuvable.');
        }

        $currentUser    = $this->getUser()->getUserIdentifier();
        $isManagement   = $this->isGranted('ROLE_ADMIN') || $this->isGranted('ROLE_MANAGEMENT');
        $isRepresentant = $mdf->getRepresentant() === $currentUser;
        $statusCode     = $mdf->getStatus()->getCode();

        $mode = match($statusCode) {
            'brouillon'                                => $isRepresentant || $isManagement ? 'form'       : null,
            'attente_validation', 'attente_val_finale'  => $isManagement                    ? 'validation' : null,
            'valide_direction'                          => $isRepresentant                  ? 'preuves'    : null,
            default                                     => null,
        };

        if ($mode === null) {
            return $this->redirectToRoute('app_mdf_show', ['id' => $id]);
        }

        return $this->render('mdf/edit.html.twig', [
            'mdf_id'        => $id,
            'mode'          => $mode,
            'is_management' => $isManagement,
        ]);
    }

    #[Route('/mdf/{id}', name: 'app_mdf_show', requirements: ['id' => '\d+'])]
    public function show(int $id, MdfRequestRepository $mdfRepo): Response
    {
        $mdf = $mdfRepo->find($id);

        if (!$mdf) {
            throw $this->createNotFoundException('MDF introuvable.');
        }

        $currentUser    = $this->getUser()->getUserIdentifier();
        $isManagement   = $this->isGranted('ROLE_ADMIN') || $this->isGranted('ROLE_MANAGEMENT');
        $isRepresentant = $mdf->getRepresentant() === $currentUser;

        if (!$isRepresentant && !$isManagement) {
            throw $this->createAccessDeniedException('Accès refusé.');
        }

        return $this->render('mdf/show.html.twig', [
            'mdf'             => $mdf,
            'is_management'   => $isManagement,
            'is_representant' => $isRepresentant,
        ]);
    }

    // ── Download preuve ───────────────────────────────────────────────────────

    #[Route('/mdf/{id}/proof/{docId}/download', name: 'mdf_proof_download', requirements: ['id' => '\d+', 'docId' => '\d+'])]
    public function downloadProof(int $id, int $docId, MdfRequestDocumentRepository $docRepo): Response
    {
        $doc = $docRepo->find($docId);

        if (!$doc || $doc->getMdfRequest()->getId() !== $id) {
            throw $this->createNotFoundException('Document introuvable.');
        }

        $currentUser    = $this->getUser()->getUserIdentifier();
        $isManagement   = $this->isGranted('ROLE_ADMIN') || $this->isGranted('ROLE_MANAGEMENT');
        $isRepresentant = $doc->getMdfRequest()->getRepresentant() === $currentUser;

        if (!$isRepresentant && !$isManagement) {
            throw $this->createAccessDeniedException('Accès refusé.');
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

    #[Route('/mdf/api/clients', name: 'api_mdf_clients', methods: ['GET'])]
    public function searchClients(Request $request, MdfX3Service $x3, Helpers $helpers, LoggerInterface $logger, GraphMailer $graphMailer): JsonResponse
    {
        $q = trim($request->query->get('q', ''));

        if (mb_strlen($q) < 2) {
            return $this->json([]);
        }

        try {
            return $this->json($helpers->convertArrayToUtf8($x3->searchClients($q)));
        } catch (\Exception $e) {
            $graphMailer->notifyError('❌ LCS Erreur MDF : Recherche clients X3', $e);
            $logger->error('LCS Erreur MDF : Recherche clients X3', ['exception' => $e]);

            return $this->json([]);
        }
    }

    #[Route('/mdf/api/ca-facture', name: 'api_mdf_ca_facture', methods: ['GET'])]
    public function getCaFacture(Request $request, MdfX3Service $x3, LoggerInterface $logger, GraphMailer $graphMailer): JsonResponse
    {
        $client = trim($request->query->get('client', ''));

        if ($client === '') {
            return $this->json(['montant' => null]);
        }

        try {
            return $this->json(['montant' => $x3->getMontantFacture($client)]);
        } catch (\Exception $e) {
            $graphMailer->notifyError('❌ LCS Erreur MDF : CA facturé X3', $e);
            $logger->error('LCS Erreur MDF : CA facturé X3', ['exception' => $e]);

            return $this->json(['montant' => null]);
        }
    }

    #[Route('/mdf/api/backlog', name: 'api_mdf_backlog', methods: ['GET'])]
    public function getBacklog(Request $request, MdfX3Service $x3, LoggerInterface $logger, GraphMailer $graphMailer): JsonResponse
    {
        $client = trim($request->query->get('client', ''));

        if ($client === '') {
            return $this->json(['montant' => null]);
        }

        try {
            return $this->json(['montant' => $x3->getMontantBacklogClient($client)]);
        } catch (\Exception $e) {
            $graphMailer->notifyError('❌ LCS Erreur MDF : Backlog client X3', $e);
            $logger->error('LCS Erreur MDF : Backlog client X3', ['exception' => $e]);

            return $this->json(['montant' => null]);
        }
    }

    #[Route('/mdf/api/mdf-total', name: 'api_mdf_mdf_total', methods: ['GET'])]
    public function getMdfTotal(Request $request, MdfRequestRepository $mdfRepo): JsonResponse
    {
        $client    = trim($request->query->get('client', ''));
        $excludeId = $request->query->get('exclude_id');

        if ($client === '') {
            return $this->json(['montant' => 0.0]);
        }

        $montant = $mdfRepo->getMontantTotalArchiveParClient($client, $excludeId !== null ? (int) $excludeId : null);

        return $this->json(['montant' => $montant]);
    }

    #[Route('/mdf/api/list', name: 'api_mdf_list', methods: ['GET'])]
    public function list(MdfRequestRepository $mdfRepo): JsonResponse
    {
        $user    = $this->getUser()->getUserIdentifier();
        $isAdmin = $this->isGranted('ROLE_ADMIN') || $this->isGranted('ROLE_MANAGEMENT');
        $rows    = $isAdmin ? $mdfRepo->findAllForList() : $mdfRepo->findByRepresentant($user);

        $data = array_map(fn($mdf) => [
            'id'            => $mdf->getId(),
            'numero'        => $mdf->getNumero(),
            'representant'  => $mdf->getRepresentant(),
            'client_code'   => $mdf->getClientCode(),
            'client_nom'    => $mdf->getClientNom(),
            'type_activite' => $mdf->getTypeActivite(),
            'date_debut'    => $mdf->getDateDebut()->format('Y-m-d'),
            'date_fin'      => $mdf->getDateFin()->format('Y-m-d'),
            'montant_mdf'   => (float) $mdf->getMontantMdf(),
            'statut'        => $mdf->getStatus()->getCode(),
            'statut_label'  => $mdf->getStatus()->getLabel(),
            'created_at'    => $mdf->getCreatedAt()->format('Y-m-d'),
        ], $rows);

        return $this->json($data);
    }

    #[Route('/mdf/api/{id}', name: 'api_mdf_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function apiShow(int $id, MdfRequestRepository $mdfRepo, MdfHistoryRepository $historyRepo): JsonResponse
    {
        $mdf = $mdfRepo->find($id);
        if (!$mdf) {
            return $this->json(['error' => 'MDF introuvable.'], 404);
        }

        $currentUser    = $this->getUser()->getUserIdentifier();
        $isManagement   = $this->isGranted('ROLE_ADMIN') || $this->isGranted('ROLE_MANAGEMENT');
        $isRepresentant = $mdf->getRepresentant() === $currentUser;

        if (!$isRepresentant && !$isManagement) {
            return $this->json(['error' => 'Accès refusé.'], 403);
        }

        $preuves = array_values(array_map(fn($doc) => [
            'id'           => $doc->getId(),
            'nom_fichier'  => $doc->getNomFichier(),
            'taille'       => $doc->getTaille(),
            'uploaded_by'  => $doc->getUploadedBy(),
            'uploaded_at'  => $doc->getUploadedAt()->format('d/m/Y H:i'),
            'download_url' => $this->generateUrl('mdf_proof_download', ['id' => $mdf->getId(), 'docId' => $doc->getId()]),
        ], array_filter($mdf->getDocuments()->toArray(), fn($d) => $d->getType() === MdfRequestDocument::TYPE_PREUVE)));

        return $this->json([
            'id'                       => $mdf->getId(),
            'numero'                   => $mdf->getNumero(),
            'titre'                    => $mdf->getTitre(),
            'representant'             => $mdf->getRepresentant(),
            'statut'                   => $mdf->getStatus()->getCode(),
            'statut_label'             => $mdf->getStatus()->getLabel(),
            'client_code'              => $mdf->getClientCode(),
            'client_nom'               => $mdf->getClientNom(),
            'client_langue'            => $mdf->getClientLangue(),
            'client_devise'            => $mdf->getClientDevise(),
            'client_emails'            => $mdf->getClientEmails(),
            'date_debut'               => $mdf->getDateDebut()->format('Y-m-d'),
            'date_fin'                 => $mdf->getDateFin()->format('Y-m-d'),
            'type_activite'            => $mdf->getTypeActivite(),
            'montant_mdf'              => (float) $mdf->getMontantMdf(),
            'montant_facture'          => $mdf->getMontantFacture() !== null ? (float) $mdf->getMontantFacture() : null,
            'montant_backlog_client'   => $mdf->getMontantBacklogClient() !== null ? (float) $mdf->getMontantBacklogClient() : null,
            'montant_mdf_total_client' => $mdf->getMontantMdfTotalClient() !== null ? (float) $mdf->getMontantMdfTotalClient() : null,
            'roi'                      => $mdf->getRoi() !== null ? (float) $mdf->getRoi() : null,
            'roi_total'                => $mdf->getRoiTotal() !== null ? (float) $mdf->getRoiTotal() : null,
            'focus_produit'            => $mdf->getFocusProduit(),
            'audience'                 => $mdf->getAudience(),
            'commentaire'              => $mdf->getCommentaire(),
            'created_at'               => $mdf->getCreatedAt()->format('d/m/Y'),
            'updated_at'               => $mdf->getUpdatedAt()->format('d/m/Y H:i'),
            'preuves'                  => $preuves,
            'historique'               => array_map(fn($h) => [
                'user'         => $h->getUser(),
                'statut'       => $h->getStatut(),
                'statut_label' => $h->getStatutLabel(),
                'date'         => $h->getCreatedAt()->format('d/m/Y H:i'),
            ], $historyRepo->findBy(['mdfRequest' => $mdf], ['createdAt' => 'ASC'])),
        ]);
    }

    // ── API Transition (valider / refuser) ────────────────────────────────────

    #[Route('/mdf/api/{id}/transition', name: 'api_mdf_transition', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function transition(
        int                    $id,
        Request                $request,
        MdfRequestRepository   $mdfRepo,
        MdfStatusRepository    $statusRepo,
        MdfMailer              $mailer,
        EntityManagerInterface $em,
        LoggerInterface        $logger,
        GraphMailer            $graphMailer,
    ): JsonResponse {
        if (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_MANAGEMENT')) {
            return $this->json(['error' => 'Accès refusé.'], 403);
        }

        $mdf = $mdfRepo->find($id);
        if (!$mdf) {
            return $this->json(['error' => 'MDF introuvable.'], 404);
        }

        $data   = json_decode($request->getContent(), true);
        $action = $data['action'] ?? '';

        $currentCode = $mdf->getStatus()->getCode();

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

        $mdf->setStatus($newStatus);
        $em->flush();

        $history = new MdfHistory();
        $history->setMdfRequest($mdf);
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
                $ws->setParameter(XmlBuilder::buildMDF($mdf));
                $ws->setMdfRequestId($mdf->getId());
                $em->persist($ws);
                $em->flush();
            } catch (\Throwable $e) {
                $xmlError = $e->getMessage();
                $graphMailer->notifyError('❌ MDF Erreur génération XML archive', $e);
                $logger->error('MDF Erreur génération XML archive', ['exception' => $e, 'mdf_id' => $mdf->getId()]);
            }
        }

        try {
            if ($newCode === 'valide_direction') {
                $mailer->sendValidationRepresentant($mdf);
                $mailer->sendContratClient($mdf);
            } elseif ($newCode === 'refuse') {
                $mailer->sendRefus($mdf);
            } elseif ($newCode === 'archive') {
                $mailer->sendArchive($mdf);
            }
        } catch (\Throwable $e) {
            $graphMailer->notifyError('❌ MDF Erreur envoi email — transition ' . $newCode, $e);
            $logger->error('MDF Erreur envoi email — transition', ['exception' => $e, 'mdf_id' => $mdf->getId(), 'statut' => $newCode]);
        }

        return $this->json([
            'statut'       => $newCode,
            'statut_label' => $newStatus->getLabel(),
            'xml_error'    => $xmlError,
        ]);
    }

    // ── API Upload preuve ─────────────────────────────────────────────────────

    #[Route('/mdf/api/{id}/proof/upload', name: 'api_mdf_upload_proof', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function uploadProof(
        int                    $id,
        Request                $request,
        MdfRequestRepository   $mdfRepo,
        EntityManagerInterface $em,
    ): JsonResponse {
        $mdf = $mdfRepo->find($id);
        if (!$mdf) {
            return $this->json(['error' => 'MDF introuvable.'], 404);
        }

        if ($mdf->getRepresentant() !== $this->getUser()->getUserIdentifier()) {
            return $this->json(['error' => 'Accès refusé.'], 403);
        }

        if ($mdf->getStatus()->getCode() !== 'valide_direction') {
            return $this->json(['error' => 'Upload non autorisé dans ce statut.'], 403);
        }

        $file = $request->files->get('file');
        if (!$file) {
            return $this->json(['error' => 'Aucun fichier reçu.'], 400);
        }

        $projectDir = $this->getParameter('kernel.project_dir');
        $dir        = $projectDir . '/var/uploads/mdf/' . $id . '/';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $originalName = $file->getClientOriginalName();
        $safeName     = uniqid('proof_') . '.' . $file->getClientOriginalExtension();
        $mimeType     = $file->getMimeType() ?? 'application/octet-stream';
        $taille       = $file->getSize() ?? 0;
        $file->move($dir, $safeName);

        $doc = new MdfRequestDocument();
        $doc->setMdfRequest($mdf);
        $doc->setType(MdfRequestDocument::TYPE_PREUVE);
        $doc->setNomFichier($originalName);
        $doc->setChemin('mdf/' . $id . '/' . $safeName);
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
            'download_url' => $this->generateUrl('mdf_proof_download', ['id' => $id, 'docId' => $doc->getId()]),
        ]);
    }

    // ── API Delete preuve ─────────────────────────────────────────────────────

    #[Route('/mdf/api/{id}/proof/{docId}/delete', name: 'api_mdf_delete_proof', methods: ['POST'], requirements: ['id' => '\d+', 'docId' => '\d+'])]
    public function deleteProof(
        int                          $id,
        int                          $docId,
        MdfRequestRepository         $mdfRepo,
        MdfRequestDocumentRepository $docRepo,
        EntityManagerInterface       $em,
    ): JsonResponse {
        $mdf = $mdfRepo->find($id);
        if (!$mdf) {
            return $this->json(['error' => 'MDF introuvable.'], 404);
        }

        if ($mdf->getRepresentant() !== $this->getUser()->getUserIdentifier()) {
            return $this->json(['error' => 'Accès refusé.'], 403);
        }

        if ($mdf->getStatus()->getCode() !== 'valide_direction') {
            return $this->json(['error' => 'Suppression non autorisée dans ce statut.'], 403);
        }

        $doc = $docRepo->find($docId);
        if (!$doc || $doc->getMdfRequest()->getId() !== $id) {
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

    // ── API Soumettre les preuves (simplifié : pas de quantité, juste au moins 1 fichier) ─

    #[Route('/mdf/api/{id}/submit-preuves', name: 'api_mdf_submit_preuves', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function submitPreuves(
        int                    $id,
        MdfRequestRepository   $mdfRepo,
        MdfStatusRepository    $statusRepo,
        MdfMailer              $mailer,
        EntityManagerInterface $em,
        LoggerInterface        $logger,
        GraphMailer            $graphMailer,
    ): JsonResponse {
        $mdf = $mdfRepo->find($id);
        if (!$mdf) {
            return $this->json(['error' => 'MDF introuvable.'], 404);
        }

        if ($mdf->getRepresentant() !== $this->getUser()->getUserIdentifier()) {
            return $this->json(['error' => 'Accès refusé.'], 403);
        }

        if ($mdf->getStatus()->getCode() !== 'valide_direction') {
            return $this->json(['error' => 'Soumission non autorisée dans ce statut.'], 403);
        }

        $preuves = array_filter($mdf->getDocuments()->toArray(), fn($d) => $d->getType() === MdfRequestDocument::TYPE_PREUVE);
        if (empty($preuves)) {
            return $this->json(['error' => 'Vous devez uploader au moins un justificatif avant de soumettre.'], 400);
        }

        $newStatus = $statusRepo->findByCode('attente_val_finale');
        if (!$newStatus) {
            return $this->json(['error' => 'Statut cible introuvable.'], 500);
        }

        $mdf->setStatus($newStatus);
        $em->flush();

        $history = new MdfHistory();
        $history->setMdfRequest($mdf);
        $history->setUser($this->getUser()->getUserIdentifier());
        $history->setStatut('attente_val_finale');
        $history->setStatutLabel($newStatus->getLabel());
        $em->persist($history);
        $em->flush();

        try {
            $mailer->sendValidationFinaleRepresentant($mdf);
        } catch (\Throwable $e) {
            $graphMailer->notifyError('❌ MDF Erreur envoi email — validation finale représentant', $e);
            $logger->error('MDF Erreur envoi email — validation finale représentant', ['exception' => $e, 'mdf_id' => $mdf->getId()]);
        }

        return $this->json(['statut' => 'attente_val_finale', 'statut_label' => $newStatus->getLabel()]);
    }

    // ── API Save (brouillon / soumettre) ──────────────────────────────────────

    #[Route('/mdf/api/save', name: 'api_mdf_save', methods: ['POST'])]
    public function save(
        Request                $request,
        MdfRequestRepository   $mdfRepo,
        MdfStatusRepository    $statusRepo,
        MdfX3Service           $x3,
        MdfMailer              $mailer,
        EntityManagerInterface $em,
        LoggerInterface        $logger,
        GraphMailer            $graphMailer,
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return $this->json(['error' => 'Données invalides.'], 400);
        }

        $required = ['numero', 'client_code', 'client_nom', 'date_debut', 'date_fin', 'type_activite', 'montant_mdf', 'statut'];
        foreach ($required as $field) {
            if (!isset($data[$field]) || $data[$field] === '') {
                return $this->json(['error' => "Champ obligatoire manquant : {$field}."], 400);
            }
        }

        $statusCode = $data['statut'];

        if (!in_array($statusCode, ['brouillon', 'attente_validation'], true)) {
            return $this->json(['error' => "Statut non autorisé via ce endpoint : {$statusCode}."], 400);
        }

        $status = $statusRepo->findByCode($statusCode);
        if (!$status) {
            return $this->json(['error' => "Statut inconnu : {$statusCode}."], 400);
        }

        $typeActiviteAllowed = [
            'Event - Retailer specific',
            'Print - Retailer catalog',
            'Print - Cobranded advertising',
            'Print - Outdoor',
            'Instore - POP',
            'Instore - Space',
            'Digital - Retailer specific',
            'Digital - Cobranded',
            'S.O.A',
            'Other',
        ];
        if (!in_array($data['type_activite'], $typeActiviteAllowed, true)) {
            return $this->json(['error' => "Type d'activité non autorisé : {$data['type_activite']}."], 400);
        }

        if (!is_numeric($data['montant_mdf']) || (float) $data['montant_mdf'] <= 0) {
            return $this->json(['error' => 'Montant MDF invalide : doit être un nombre supérieur à 0.'], 400);
        }

        try {
            $dateDebut = new \DateTime($data['date_debut']);
            $dateFin   = new \DateTime($data['date_fin']);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Format de date invalide pour date_debut ou date_fin.'], 400);
        }

        $numero = $data['numero'];
        $mdf    = $mdfRepo->findOneBy(['numero' => $numero]);

        if (!$mdf) {
            if (!$this->isGranted('ROLE_SALES') && !$this->isGranted('ROLE_MARKETING') && !$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_MANAGEMENT')) {
                return $this->json(['error' => 'Accès refusé.'], 403);
            }

            $mdf = new MdfRequest();
            $mdf->setNumero($numero);
            $mdf->setRepresentant($this->getUser()->getUserIdentifier());
        } else {
            if ($mdf->getStatus()->getCode() !== 'brouillon') {
                return $this->json(['error' => 'Ce MDF ne peut plus être modifié.'], 403);
            }

            if ($mdf->getRepresentant() !== $this->getUser()->getUserIdentifier()) {
                return $this->json(['error' => 'Accès refusé.'], 403);
            }
        }

        $previousStatut = $mdf->getId() ? $mdf->getStatus()->getCode() : null;
        $clientCode     = $data['client_code'];

        $mdf->setStatus($status);
        $mdf->setClientCode($clientCode);
        $mdf->setClientNom($data['client_nom']);
        $mdf->setClientLangue($data['client_langue'] ?? '');
        $mdf->setClientDevise($data['client_devise'] ?? 'EUR');
        $mdf->setClientEmails($data['client_emails'] ?? []);
        $mdf->setTitre($data['titre'] ?? ($numero . ' — ' . $data['client_nom']));
        $mdf->setDateDebut($dateDebut);
        $mdf->setDateFin($dateFin);
        $mdf->setTypeActivite($data['type_activite']);
        $mdf->setMontantMdf((string) $data['montant_mdf']);
        $mdf->setFocusProduit($data['focus_produit'] ?? null);
        $mdf->setAudience($data['audience'] ?? null);
        $mdf->setCommentaire($data['commentaire'] ?? null);

        // Recalcul serveur des montants snapshot — jamais fait confiance aux valeurs envoyées par le client
        try {
            $montantFacture        = $x3->getMontantFacture($clientCode);
            $montantBacklogClient  = $x3->getMontantBacklogClient($clientCode);
        } catch (\Exception $e) {
            $graphMailer->notifyError('❌ LCS Erreur MDF : Récupération données client X3 (save)', $e);
            $logger->error('LCS Erreur MDF : Récupération données client X3 (save)', ['exception' => $e]);

            return $this->json(['error' => 'Erreur lors de la récupération des données client depuis X3.'], 500);
        }

        $mdf->setMontantFacture($montantFacture !== null ? (string) $montantFacture : null);
        $mdf->setMontantBacklogClient($montantBacklogClient !== null ? (string) $montantBacklogClient : null);
        $mdf->setMontantMdfTotalClient((string) $mdfRepo->getMontantTotalArchiveParClient($clientCode, $mdf->getId()));
        $mdf->recalculate();

        $em->persist($mdf);
        $em->flush();

        $history = new MdfHistory();
        $history->setMdfRequest($mdf);
        $history->setUser($this->getUser()->getUserIdentifier());
        $history->setStatut($mdf->getStatus()->getCode());
        $history->setStatutLabel($mdf->getStatus()->getLabel());
        $em->persist($history);
        $em->flush();

        try {
            $newStatut = $mdf->getStatus()->getCode();

            if ($newStatut === 'attente_validation' && $previousStatut !== 'attente_validation') {
                $mailer->sendSoumissionDirection($mdf);
            }
        } catch (\Throwable $e) {
            $graphMailer->notifyError('❌ MDF Erreur envoi email — soumission direction', $e);
            $logger->error('MDF Erreur envoi email — soumission direction', ['exception' => $e, 'mdf_id' => $mdf->getId()]);
        }

        return $this->json(['id' => $mdf->getId(), 'numero' => $mdf->getNumero()]);
    }
}
