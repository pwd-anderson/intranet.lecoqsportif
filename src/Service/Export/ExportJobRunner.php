<?php

namespace App\Service\Export;

use App\Entity\ExportJob;
use App\Repository\AggridOptionRepository;
use App\Service\AgGrid\Ssrm\SsrmRequest;
use App\Service\BacklogClientV2;
use App\Service\Tools\GraphMailer;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Genere le fichier d'un ExportJob, hors requete HTTP.
 *
 * Les exports volumineux depassent la limite de temps du serveur web : la generation
 * se fait donc dans un processus separe, et l'utilisateur telecharge le fichier ensuite.
 */
class ExportJobRunner
{
    /** Stats exportables : cle => [grille AG Grid, prefixe du fichier]. */
    private const array STATS = [
        'backlog_client_v2' => ['grid' => 'backlog_client_v2_grid', 'prefixe' => 'Backlog_Client_v2'],
    ];

    public function __construct(
        private EntityManagerInterface $entityManager,
        private AggridOptionRepository $aggridOptionRepository,
        private BacklogClientV2 $backlogClientV2,
        private GraphMailer $graphMailer,
        private LoggerInterface $logger,
        #[Autowire('%kernel.project_dir%')]
        private string $projectDir,
    ) {}

    public static function statConnue(string $statKey): bool
    {
        return isset(self::STATS[$statKey]);
    }

    /** Dossier des fichiers generes, cree au besoin. */
    public function dossier(string $statKey): string
    {
        $dossier = $this->projectDir . '/var/upload/export/' . $statKey;

        if (!is_dir($dossier)) {
            mkdir($dossier, 0775, true);
        }

        return $dossier;
    }

    public function cheminFichier(ExportJob $job): ?string
    {
        $fichier = $job->getFichier();

        return $fichier === null ? null : $this->dossier($job->getStatKey()) . '/' . $fichier;
    }

    /**
     * Genere le fichier et met la demande a jour. N'echoue jamais bruyamment :
     * une erreur est enregistree sur la demande et notifiee par mail.
     */
    public function executer(ExportJob $job): bool
    {
        $statKey = $job->getStatKey();

        if (!self::statConnue($statKey)) {
            $job->marquerErreur(sprintf('Stat inconnue : %s', $statKey));
            $this->entityManager->flush();

            return false;
        }

        $job->marquerEnCours();
        $this->entityManager->flush();

        $payload  = $job->getPayload();
        $fichier  = sprintf(
            '%s_%s_%d.csv',
            self::STATS[$statKey]['prefixe'],
            (new \DateTimeImmutable())->format('Y-m-d_His'),
            $job->getId()
        );
        $chemin = $this->dossier($statKey) . '/' . $fichier;

        $out = fopen($chemin, 'w');

        try {
            $colonnes = $this->colonnesVisibles(self::STATS[$statKey]['grid']);
            $requete  = SsrmRequest::fromArray($payload);

            $this->backlogClientV2->writeCsv($out, $requete, $colonnes);
            fclose($out);

            // Une ligne d'en-tete et la ligne "sep=;" ne sont pas des donnees
            $lignes = max(0, $this->compterLignes($chemin) - 2);
            $job->marquerTermine($fichier, $lignes, (int) filesize($chemin));
            $this->entityManager->flush();

            return true;
        } catch (\Throwable $e) {
            if (is_resource($out)) {
                fclose($out);
            }
            if (is_file($chemin)) {
                unlink($chemin);
            }

            $job->marquerErreur($e->getMessage());
            $this->entityManager->flush();

            $this->graphMailer->notifyError('❌ LCS Erreur export en tache de fond', $e);
            $this->logger->error('Export en tache de fond en echec', [
                'job' => $job->getId(),
                'stat' => $statKey,
                'exception' => $e,
            ]);

            return false;
        }
    }

    /**
     * Colonnes visibles de la grille, dans l'ordre de la configuration AG Grid.
     *
     * @return array<string, array{header: string, type: string}>
     */
    private function colonnesVisibles(string $gridName): array
    {
        $colonnes = [];

        foreach ($this->aggridOptionRepository->findBy(['gridName' => $gridName], ['orderIndex' => 'ASC']) as $option) {
            if ($option->isVisible() !== false) {
                $colonnes[$option->getField()] = [
                    'header' => $option->getHeaderName(),
                    'type'   => (string) $option->getType(),
                ];
            }
        }

        return $colonnes;
    }

    private function compterLignes(string $chemin): int
    {
        $handle = fopen($chemin, 'r');
        $lignes = 0;

        while (fgets($handle) !== false) {
            ++$lignes;
        }

        fclose($handle);

        return $lignes;
    }
}
