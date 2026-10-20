<?php

namespace App\Entity;

use App\Repository\SuiviSellinNoteRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Note de suivi sur un client (fiche magasin du module Suivi Sellin global). Partagée avec toute l'équipe qui a accès
 * au module. L'auteur est figé (id + nom) : la note reste lisible si le compte change.
 */
#[ORM\Entity(repositoryClass: SuiviSellinNoteRepository::class)]
#[ORM\Table(name: 'suivi_sellin_note')]
#[ORM\Index(columns: ['customer_code'], name: 'idx_ssn_customer')]
class SuiviSellinNote
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 30)]
    private string $customerCode = '';

    #[ORM\Column(type: Types::TEXT)]
    private string $text = '';

    #[ORM\Column]
    private int $authorId = 0;

    #[ORM\Column(length: 255)]
    private string $authorName = '';

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getCustomerCode(): string { return $this->customerCode; }
    public function setCustomerCode(string $customerCode): static { $this->customerCode = $customerCode; return $this; }

    public function getText(): string { return $this->text; }
    public function setText(string $text): static { $this->text = $text; return $this; }

    public function getAuthorId(): int { return $this->authorId; }
    public function setAuthorId(int $authorId): static { $this->authorId = $authorId; return $this; }

    public function getAuthorName(): string { return $this->authorName; }
    public function setAuthorName(string $authorName): static { $this->authorName = $authorName; return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
}
