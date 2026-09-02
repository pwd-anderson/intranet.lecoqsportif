<?php

namespace App\Entity;

use App\Repository\SoaHistoryRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SoaHistoryRepository::class)]
#[ORM\Table(name: 'soa_history')]
class SoaHistory
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: SoaRequest::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private SoaRequest $soaRequest;

    #[ORM\Column(length: 150)]
    private string $user = '';

    #[ORM\Column(length: 50)]
    private string $statut = '';

    #[ORM\Column(length: 255)]
    private string $statutLabel = '';

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int { return $this->id; }

    public function getSoaRequest(): SoaRequest { return $this->soaRequest; }
    public function setSoaRequest(SoaRequest $soaRequest): static { $this->soaRequest = $soaRequest; return $this; }

    public function getUser(): string { return $this->user; }
    public function setUser(string $user): static { $this->user = $user; return $this; }

    public function getStatut(): string { return $this->statut; }
    public function setStatut(string $statut): static { $this->statut = $statut; return $this; }

    public function getStatutLabel(): string { return $this->statutLabel; }
    public function setStatutLabel(string $statutLabel): static { $this->statutLabel = $statutLabel; return $this; }

    public function getCreatedAt(): \DateTimeInterface { return $this->createdAt; }
}
