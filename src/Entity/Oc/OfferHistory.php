<?php

namespace App\Entity\Oc;

use App\Entity\User;
use App\Repository\Oc\OfferHistoryRepository;
use App\Service\Oc\OcAction;
use App\Service\Oc\OcStatus;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Historique d'une OC : une ligne par événement (qui, quoi, quand, de quel statut à quel statut, pourquoi).
 * Ajout seulement : ces lignes ne sont jamais modifiées ni supprimées.
 */
#[ORM\Entity(repositoryClass: OfferHistoryRepository::class)]
#[ORM\Table(name: 'oc_offer_history')]
#[ORM\Index(name: 'idx_oc_offer_history_offer', columns: ['offer_id'])]
class OfferHistory
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'history')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Offer $offer;

    #[ORM\Column(length: 20, enumType: OcAction::class)]
    private OcAction $action;

    #[ORM\Column(length: 20, nullable: true, enumType: OcStatus::class)]
    private ?OcStatus $fromStatus = null;

    #[ORM\Column(length: 20, enumType: OcStatus::class)]
    private OcStatus $toStatus;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    private ?User $user = null;

    /** Nom de l'utilisateur au moment de l'action (conservé même si le compte disparaît). */
    #[ORM\Column(length: 255)]
    private string $userName;

    /** Motif (obligatoire pour un rejet) ou commentaire libre. */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $reason = null;

    /** Détails utiles à l'audit (champs modifiés, message d'erreur X3...). */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $context = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    public function __construct(OcAction $action, ?OcStatus $fromStatus, OcStatus $toStatus, ?User $user, string $userName, ?string $reason = null, ?array $context = null)
    {
        $this->action = $action;
        $this->fromStatus = $fromStatus;
        $this->toStatus = $toStatus;
        $this->user = $user;
        $this->userName = $userName;
        $this->reason = $reason;
        $this->context = $context;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }
    public function getOffer(): Offer { return $this->offer; }
    public function setOffer(Offer $offer): static { $this->offer = $offer; return $this; }
    public function getAction(): OcAction { return $this->action; }
    public function getFromStatus(): ?OcStatus { return $this->fromStatus; }
    public function getToStatus(): OcStatus { return $this->toStatus; }
    public function getUser(): ?User { return $this->user; }
    public function getUserName(): string { return $this->userName; }
    public function getReason(): ?string { return $this->reason; }
    public function getContext(): ?array { return $this->context; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
}
