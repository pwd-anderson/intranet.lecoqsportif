<?php

namespace App\Entity;

use App\Repository\SoaRequestRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SoaRequestRepository::class)]
#[ORM\Table(name: 'soa_request')]
#[ORM\HasLifecycleCallbacks]
class SoaRequest
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /** Format : SOAMMAAXXXXXXXXXX */
    #[ORM\Column(length: 20, unique: true)]
    private string $numero = '';

    #[ORM\Column(length: 255)]
    private string $titre = '';

    /** Identifiant Azure (email) du commercial créateur */
    #[ORM\Column(length: 150)]
    private string $representant = '';

    #[ORM\ManyToOne(targetEntity: SoaStatus::class)]
    #[ORM\JoinColumn(nullable: false)]
    private SoaStatus $status;

    #[ORM\Column(length: 50)]
    private string $clientCode = '';

    #[ORM\Column(length: 255)]
    private string $clientNom = '';

    #[ORM\Column(length: 10)]
    private string $clientLangue = '';

    #[ORM\Column(length: 10)]
    private string $clientDevise = 'EUR';

    /** Adresses email sélectionnées pour l'envoi du contrat */
    #[ORM\Column(type: Types::JSON)]
    private array $clientEmails = [];

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private \DateTimeInterface $dateDebut;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private \DateTimeInterface $dateFin;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $focusProduit = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $commentaire = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $createdAt;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $updatedAt;

    #[ORM\OneToMany(targetEntity: SoaRequestProduct::class, mappedBy: 'soaRequest', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $products;

    #[ORM\OneToMany(targetEntity: SoaRequestDocument::class, mappedBy: 'soaRequest', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $documents;

    public function __construct()
    {
        $this->products  = new ArrayCollection();
        $this->documents = new ArrayCollection();
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTime();
    }

    public function getId(): ?int { return $this->id; }

    public function getNumero(): string { return $this->numero; }
    public function setNumero(string $numero): static { $this->numero = $numero; return $this; }

    public function getTitre(): string { return $this->titre; }
    public function setTitre(string $titre): static { $this->titre = $titre; return $this; }

    public function getRepresentant(): string { return $this->representant; }
    public function setRepresentant(string $representant): static { $this->representant = $representant; return $this; }

    public function getStatus(): SoaStatus { return $this->status; }
    public function setStatus(SoaStatus $status): static { $this->status = $status; return $this; }

    public function getClientCode(): string { return $this->clientCode; }
    public function setClientCode(string $clientCode): static { $this->clientCode = $clientCode; return $this; }

    public function getClientNom(): string { return $this->clientNom; }
    public function setClientNom(string $clientNom): static { $this->clientNom = $clientNom; return $this; }

    public function getClientLangue(): string { return $this->clientLangue; }
    public function setClientLangue(string $clientLangue): static { $this->clientLangue = $clientLangue; return $this; }

    public function getClientDevise(): string { return $this->clientDevise; }
    public function setClientDevise(string $clientDevise): static { $this->clientDevise = $clientDevise; return $this; }

    public function getClientEmails(): array { return $this->clientEmails; }
    public function setClientEmails(array $clientEmails): static { $this->clientEmails = $clientEmails; return $this; }

    public function getDateDebut(): \DateTimeInterface { return $this->dateDebut; }
    public function setDateDebut(\DateTimeInterface $dateDebut): static { $this->dateDebut = $dateDebut; return $this; }

    public function getDateFin(): \DateTimeInterface { return $this->dateFin; }
    public function setDateFin(\DateTimeInterface $dateFin): static { $this->dateFin = $dateFin; return $this; }

    public function getFocusProduit(): ?string { return $this->focusProduit; }
    public function setFocusProduit(?string $focusProduit): static { $this->focusProduit = $focusProduit; return $this; }

    public function getCommentaire(): ?string { return $this->commentaire; }
    public function setCommentaire(?string $commentaire): static { $this->commentaire = $commentaire; return $this; }

    public function getCreatedAt(): \DateTimeInterface { return $this->createdAt; }
    public function getUpdatedAt(): \DateTimeInterface { return $this->updatedAt; }

    /** @return Collection<int, SoaRequestProduct> */
    public function getProducts(): Collection { return $this->products; }

    public function addProduct(SoaRequestProduct $product): static
    {
        if (!$this->products->contains($product)) {
            $this->products->add($product);
            $product->setSoaRequest($this);
        }
        return $this;
    }

    public function removeProduct(SoaRequestProduct $product): static
    {
        $this->products->removeElement($product);
        return $this;
    }

    /** @return Collection<int, SoaRequestDocument> */
    public function getDocuments(): Collection { return $this->documents; }

    public function addDocument(SoaRequestDocument $document): static
    {
        if (!$this->documents->contains($document)) {
            $this->documents->add($document);
            $document->setSoaRequest($this);
        }
        return $this;
    }

    public function removeDocument(SoaRequestDocument $document): static
    {
        $this->documents->removeElement($document);
        return $this;
    }
}
