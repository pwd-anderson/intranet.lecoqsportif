<?php

namespace App\Entity;

use App\Repository\MdfRequestRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MdfRequestRepository::class)]
#[ORM\Table(name: 'mdf_request')]
#[ORM\HasLifecycleCallbacks]
class MdfRequest
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /** Format : MDFMMAAXXXXXXXXXX */
    #[ORM\Column(length: 20, unique: true)]
    private string $numero = '';

    #[ORM\Column(length: 255)]
    private string $titre = '';

    /** Identifiant Azure (email) du commercial créateur */
    #[ORM\Column(length: 150)]
    private string $representant = '';

    #[ORM\ManyToOne(targetEntity: MdfStatus::class)]
    #[ORM\JoinColumn(nullable: false)]
    private MdfStatus $status;

    #[ORM\Column(length: 50)]
    private string $clientCode = '';

    #[ORM\Column(length: 255)]
    private string $clientNom = '';

    #[ORM\Column(length: 10)]
    private string $clientLangue = '';

    #[ORM\Column(length: 10)]
    private string $clientDevise = 'EUR';

    #[ORM\Column(type: Types::JSON)]
    private array $clientEmails = [];

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private \DateTimeInterface $dateDebut;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private \DateTimeInterface $dateFin;

    /** Une des valeurs de la liste fermée (voir Global Constraints), stockée telle quelle */
    #[ORM\Column(length: 100)]
    private string $typeActivite = '';

    /** Montant MDF saisi manuellement par le commercial */
    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 2)]
    private string $montantMdf = '0.00';

    /** Snapshot X3 au moment du calcul (CA facturé année en cours) */
    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 2, nullable: true)]
    private ?string $montantFacture = null;

    /** Snapshot X3 au moment du calcul (backlog client, montant devise) */
    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 2, nullable: true)]
    private ?string $montantBacklogClient = null;

    /** Snapshot MySQL : somme des MDF archivées de ce client sur l'année en cours */
    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 2, nullable: true)]
    private ?string $montantMdfTotalClient = null;

    /** ROI = montantMdf / (montantFacture + montantBacklogClient) x 100 */
    #[ORM\Column(type: Types::DECIMAL, precision: 8, scale: 2, nullable: true)]
    private ?string $roi = null;

    /** ROI Total = montantMdf / (montantFacture + montantBacklogClient + montantMdfTotalClient) x 100 */
    #[ORM\Column(type: Types::DECIMAL, precision: 8, scale: 2, nullable: true)]
    private ?string $roiTotal = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $focusProduit = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $audience = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $commentaire = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $createdAt;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $updatedAt;

    #[ORM\OneToMany(targetEntity: MdfRequestDocument::class, mappedBy: 'mdfRequest', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $documents;

    public function __construct()
    {
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

    public function getStatus(): MdfStatus { return $this->status; }
    public function setStatus(MdfStatus $status): static { $this->status = $status; return $this; }

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

    public function getTypeActivite(): string { return $this->typeActivite; }
    public function setTypeActivite(string $typeActivite): static { $this->typeActivite = $typeActivite; return $this; }

    public function getMontantMdf(): string { return $this->montantMdf; }
    public function setMontantMdf(string $montantMdf): static { $this->montantMdf = $montantMdf; return $this; }

    public function getMontantFacture(): ?string { return $this->montantFacture; }
    public function setMontantFacture(?string $montantFacture): static { $this->montantFacture = $montantFacture; return $this; }

    public function getMontantBacklogClient(): ?string { return $this->montantBacklogClient; }
    public function setMontantBacklogClient(?string $montantBacklogClient): static { $this->montantBacklogClient = $montantBacklogClient; return $this; }

    public function getMontantMdfTotalClient(): ?string { return $this->montantMdfTotalClient; }
    public function setMontantMdfTotalClient(?string $montantMdfTotalClient): static { $this->montantMdfTotalClient = $montantMdfTotalClient; return $this; }

    public function getRoi(): ?string { return $this->roi; }
    public function setRoi(?string $roi): static { $this->roi = $roi; return $this; }

    public function getRoiTotal(): ?string { return $this->roiTotal; }
    public function setRoiTotal(?string $roiTotal): static { $this->roiTotal = $roiTotal; return $this; }

    public function getFocusProduit(): ?string { return $this->focusProduit; }
    public function setFocusProduit(?string $focusProduit): static { $this->focusProduit = $focusProduit; return $this; }

    public function getAudience(): ?string { return $this->audience; }
    public function setAudience(?string $audience): static { $this->audience = $audience; return $this; }

    public function getCommentaire(): ?string { return $this->commentaire; }
    public function setCommentaire(?string $commentaire): static { $this->commentaire = $commentaire; return $this; }

    public function getCreatedAt(): \DateTimeInterface { return $this->createdAt; }
    public function getUpdatedAt(): \DateTimeInterface { return $this->updatedAt; }

    /** @return Collection<int, MdfRequestDocument> */
    public function getDocuments(): Collection { return $this->documents; }

    public function addDocument(MdfRequestDocument $document): static
    {
        if (!$this->documents->contains($document)) {
            $this->documents->add($document);
            $document->setMdfRequest($this);
        }
        return $this;
    }

    public function removeDocument(MdfRequestDocument $document): static
    {
        $this->documents->removeElement($document);
        return $this;
    }

    /**
     * Recalcule roi et roiTotal à partir des montants courants.
     * null si le dénominateur correspondant est nul ou absent (jamais de division par zéro).
     */
    public function recalculate(): void
    {
        $montantMdf = (float) $this->montantMdf;
        $facture    = $this->montantFacture !== null ? (float) $this->montantFacture : 0.0;
        $backlog    = $this->montantBacklogClient !== null ? (float) $this->montantBacklogClient : 0.0;
        $mdfTotal   = $this->montantMdfTotalClient !== null ? (float) $this->montantMdfTotalClient : 0.0;

        $denomRoi = $facture + $backlog;
        $this->roi = $denomRoi > 0
            ? (string) round($montantMdf / $denomRoi * 100, 2)
            : null;

        $denomRoiTotal = $facture + $backlog + $mdfTotal;
        $this->roiTotal = $denomRoiTotal > 0
            ? (string) round($montantMdf / $denomRoiTotal * 100, 2)
            : null;
    }
}
