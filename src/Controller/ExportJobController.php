<?php

namespace App\Controller;

use App\Entity\ExportJob;
use App\Entity\User;
use App\Repository\ExportJobRepository;
use App\Service\Export\ExportJobRunner;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Exports volumineux generes en tache de fond.
 *
 * Le navigateur demande un export, recoit aussitot un identifiant, puis interroge
 * l'etat jusqu'a pouvoir telecharger le fichier. La requete HTTP ne reste donc jamais
 * ouverte pendant la generation, qui depasse la limite de temps du serveur web.
 */
final class ExportJobController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ExportJobRepository $exportJobRepository,
        private ExportJobRunner $runner,
        private LoggerInterface $logger,
        #[Autowire('%kernel.project_dir%')]
        private string $projectDir,
        #[Autowire('%kernel.environment%')]
        private string $environment,
    ) {}

    #[Route('/export/demander/{statKey}', name: 'app_export_demander', methods: ['POST'])]
    public function demander(string $statKey, Request $request): JsonResponse
    {
        if (!ExportJobRunner::statConnue($statKey)) {
            return new JsonResponse(['erreur' => 'Export inconnu.'], Response::HTTP_BAD_REQUEST);
        }

        /** @var User|null $user */
        $user = $this->getUser();

        // Un seul export a la fois par utilisateur : un clic repete renvoie le meme
        // export au lieu d'en lancer un second, qui tirerait a nouveau tout le jeu de donnees.
        $enCours = $this->exportJobRepository->trouverEnCoursPourUtilisateur($user, $statKey);
        if ($enCours !== null) {
            return new JsonResponse(['id' => $enCours->getId(), 'deja_en_cours' => true]);
        }

        $payload = json_decode($request->getContent(), true) ?? [];

        $job = new ExportJob($statKey, is_array($payload) ? $payload : [], $user);
        $this->entityManager->persist($job);
        $this->entityManager->flush();

        $this->lancerEnTacheDeFond($job);

        return new JsonResponse(['id' => $job->getId(), 'deja_en_cours' => false]);
    }

    #[Route('/export/etat/{id}', name: 'app_export_etat', methods: ['GET'])]
    public function etat(int $id): JsonResponse
    {
        $job = $this->jobDeLUtilisateur($id);

        if ($job === null) {
            return new JsonResponse(['erreur' => 'Export introuvable.'], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse([
            'id'      => $job->getId(),
            'statut'  => $job->getStatut(),
            'lignes'  => $job->getLignes(),
            'taille'  => $job->getTaille(),
            'erreur'  => $job->estEnErreur() ? "La génération a échoué. L'équipe a été prévenue." : null,
            'url'     => $job->estTermine()
                ? $this->generateUrl('app_export_telecharger', ['id' => $job->getId()])
                : null,
        ]);
    }

    #[Route('/export/telecharger/{id}', name: 'app_export_telecharger', methods: ['GET'])]
    public function telecharger(int $id): Response
    {
        $job = $this->jobDeLUtilisateur($id);

        if ($job === null || !$job->estTermine()) {
            throw $this->createNotFoundException('Export introuvable.');
        }

        $chemin = $this->runner->cheminFichier($job);

        if ($chemin === null || !is_file($chemin)) {
            // Le fichier a ete purge alors que la demande existe encore
            throw $this->createNotFoundException('Fichier expiré, relancez l\'export.');
        }

        $reponse = new BinaryFileResponse($chemin);
        $reponse->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, (string) $job->getFichier());
        $reponse->headers->set('Content-Type', 'text/csv; charset=UTF-8');

        return $reponse;
    }

    /** Un utilisateur ne voit que ses propres exports. */
    private function jobDeLUtilisateur(int $id): ?ExportJob
    {
        $job = $this->exportJobRepository->find($id);

        if ($job === null) {
            return null;
        }

        $user = $this->getUser();

        if ($job->getUser() !== null && $user !== null && $job->getUser()->getId() !== $user->getId()) {
            return null;
        }

        return $job;
    }

    /**
     * Demarre la generation sans attendre sa fin.
     *
     * Le processus doit survivre a la requete HTTP. Un Process::start() ne suffit pas :
     * le destructeur de l'objet Process appelle stop(), donc l'enfant est tue des que le
     * controleur rend la main. On passe donc par un shell avec nohup et "&", qui detache
     * reellement le processus ; la commande shell, elle, rend la main immediatement.
     *
     * Si le serveur web n'a pas le droit de lancer un processus, la demande reste en
     * attente : un cron `app:export:run --pending` la traitera. Rien n'est perdu.
     */
    private function lancerEnTacheDeFond(ExportJob $job): void
    {
        try {
            // Sous PHP-FPM, PHP_BINARY designe le binaire FPM et non le PHP en ligne de
            // commande : on cherche donc explicitement l'executable CLI. La variable
            // d'environnement PHP_CLI_BINARY permet de le forcer (utile sous Plesk,
            // ou le CLI vit dans /opt/plesk/php/8.3/bin/php).
            $binaire = $_ENV['PHP_CLI_BINARY']
                ?? (new PhpExecutableFinder())->find()
                ?: PHP_BINARY;

            // La sortie part dans un journal : sans cela, un echec au demarrage serait
            // invisible et la demande resterait "en attente" sans explication.
            $journal = $this->projectDir . '/var/log/export_job.log';

            $commande = sprintf(
                'nohup %s %s app:export:run %s --env=%s >> %s 2>&1 &',
                escapeshellarg($binaire),
                escapeshellarg($this->projectDir . '/bin/console'),
                escapeshellarg((string) $job->getId()),
                escapeshellarg($this->environment),
                escapeshellarg($journal)
            );

            $process = Process::fromShellCommandline($commande, $this->projectDir);
            $process->setTimeout(10);
            $process->run();
        } catch (\Throwable $e) {
            // La demande reste en statut "en_attente" et sera reprise par le cron
            $this->logger->warning("Impossible de lancer l'export en tache de fond", [
                'job' => $job->getId(),
                'erreur' => $e->getMessage(),
            ]);
        }
    }
}
