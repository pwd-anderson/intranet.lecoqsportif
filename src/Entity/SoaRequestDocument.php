<?php

namespace App\Entity;

use App\Repository\SoaRequestDocumentRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SoaRequestDocumentRepository::class)]
#[ORM\Table(name: 'soa_request_document')]
class SoaRequestDocument
{
    public const TYPE_CONTRAT  = 'contrat';
    public const TYPE_PREUVE   = 'preuve';
    public const TYPE_AUTRE    = 'autre';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: SoaRequest::class, inversedBy: 'documents')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private SoaRequest $soaRequest;

    /** contrat | preuve | autre */
    #[ORM\Column(length: 20)]
    private string $type = self::TYPE_AUTRE;

    /** Nom original du fichier tel qu'uploadé */
    #[ORM\Column(length: 255)]
    private string $nomFichier = '';

    /** Chemin relatif depuis var/uploads/ */
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

    public function getSoaRequest(): SoaRequest { return $this->soaRequest; }
    public function setSoaRequest(SoaRequest $soaRequest): static { $this->soaRequest = $soaRequest; return $this; }

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
