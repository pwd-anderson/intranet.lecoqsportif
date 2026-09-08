<?php

namespace App\Entity;

use App\Repository\MdfRequestDocumentRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MdfRequestDocumentRepository::class)]
#[ORM\Table(name: 'mdf_request_document')]
class MdfRequestDocument
{
    public const TYPE_CONTRAT = 'contrat';
    public const TYPE_PREUVE  = 'preuve';
    public const TYPE_AUTRE   = 'autre';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: MdfRequest::class, inversedBy: 'documents')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private MdfRequest $mdfRequest;

    #[ORM\Column(length: 20)]
    private string $type = self::TYPE_AUTRE;

    #[ORM\Column(length: 255)]
    private string $nomFichier = '';

    #[ORM\Column(length: 500)]
    private string $chemin = '';

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $mimeType = null;

    #[ORM\Column(nullable: true)]
    private ?int $taille = null;

    #[ORM\Column(length: 150)]
    private string $uploadedBy = '';

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $uploadedAt;

    public function __construct()
    {
        $this->uploadedAt = new \DateTime();
    }

    public function getId(): ?int { return $this->id; }

    public function getMdfRequest(): MdfRequest { return $this->mdfRequest; }
    public function setMdfRequest(MdfRequest $mdfRequest): static { $this->mdfRequest = $mdfRequest; return $this; }

    public function getType(): string { return $this->type; }
    public function setType(string $type): static { $this->type = $type; return $this; }

    public function getNomFichier(): string { return $this->nomFichier; }
    public function setNomFichier(string $nomFichier): static { $this->nomFichier = $nomFichier; return $this; }

    public function getChemin(): string { return $this->chemin; }
    public function setChemin(string $chemin): static { $this->chemin = $chemin; return $this; }

    public function getMimeType(): ?string { return $this->mimeType; }
    public function setMimeType(?string $mimeType): static { $this->mimeType = $mimeType; return $this; }

    public function getTaille(): ?int { return $this->taille; }
    public function setTaille(?int $taille): static { $this->taille = $taille; return $this; }

    public function getUploadedBy(): string { return $this->uploadedBy; }
    public function setUploadedBy(string $uploadedBy): static { $this->uploadedBy = $uploadedBy; return $this; }

    public function getUploadedAt(): \DateTimeInterface { return $this->uploadedAt; }
}
