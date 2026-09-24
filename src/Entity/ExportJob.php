<?php

namespace App\Entity;

use App\Repository\ExportJobRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Demande d'export volumineux, generee en tache de fond.
 *
 * Les gros exports (le Backlog Client v2 produit 68 Mo et 171 000 lignes) depassent
 * la limite de temps du serveur web. La demande est donc enregistree ici, la generation
 * se fait hors requete HTTP, et l'utilisateur recupere son fichier par un lien.
 *
 * Cette table fait aussi office de file d'attente : si PHP-FPM interdit de lancer un
 * processus, un cron peut traiter les demandes en attente sans rien changer d'autre.
 */
#[ORM\Entity(repositoryClass: ExportJobRepository::class)]
#[ORM\Table(name: 'export_job')]
#[ORM\Index(name: 'idx_export_job_statut', columns: ['statut'])]
class ExportJob
{
    public const string STATUT_EN_ATTENTE = 'en_attente';
    public const string STATUT_EN_COURS   = 'en_cours';
    public const string STATUT_TERMINE    = 'termine';
    public const string STATUT_ERREUR     = 'erreur';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /** Identifiant de la stat concernee, ex. "backlog_client_v2". */
    #[ORM\Column(length: 60)]
    private string $statKey;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $user = null;

    /** Filtres, tri et options de la grille au moment de la demande. */
    #[ORM\Column(type: 'json')]
    private array $payload = [];

    #[ORM\Column(length: 20)]
    private string $statut = self::STATUT_EN_ATTENTE;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $fichier = null;

    #[ORM\Column(nullable: true)]
    private ?int $lignes = null;

    #[ORM\Column(nullable: true)]
    private ?int $taille = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $erreur = null;

    #[ORM\Column]
    private \DateTimeImmutable $creeLe;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $termineLe = null;

    public function __construct(string $statKey, array $payload, ?User $user)
    {
        $this->statKey = $statKey;
        $this->payload = $payload;
        $this->user    = $user;
        $this->creeLe  = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }
    public function getStatKey(): string { return $this->statKey; }
    public function getUser(): ?User { return $this->user; }
    public function getPayload(): array { return $this->payload; }
    public function getStatut(): string { return $this->statut; }
    public function getFichier(): ?string { return $this->fichier; }
    public function getLignes(): ?int { return $this->lignes; }
    public function getTaille(): ?int { return $this->taille; }
    public function getErreur(): ?string { return $this->erreur; }
    public function getCreeLe(): \DateTimeImmutable { return $this->creeLe; }
    public function getTermineLe(): ?\DateTimeImmutable { return $this->termineLe; }

    public function estTermine(): bool { return $this->statut === self::STATUT_TERMINE; }
    public function estEnErreur(): bool { return $this->statut === self::STATUT_ERREUR; }

    public function marquerEnCours(): static
    {
        $this->statut = self::STATUT_EN_COURS;

        return $this;
    }

    public function marquerTermine(string $fichier, int $lignes, int $taille): static
    {
        $this->statut    = self::STATUT_TERMINE;
        $this->fichier   = $fichier;
        $this->lignes    = $lignes;
        $this->taille    = $taille;
        $this->termineLe = new \DateTimeImmutable();

        return $this;
    }

    public function marquerErreur(string $message): static
    {
        $this->statut    = self::STATUT_ERREUR;
        $this->erreur    = mb_substr($message, 0, 2000);
        $this->termineLe = new \DateTimeImmutable();

        return $this;
    }
}
