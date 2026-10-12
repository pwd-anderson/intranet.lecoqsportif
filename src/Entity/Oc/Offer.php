<?php

namespace App\Entity\Oc;

use App\Entity\User;
use App\Repository\Oc\OfferRepository;
use App\Service\Oc\OcStatus;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * En-tête d'une offre commerciale (une OC = un client). Le client et ses valeurs X3 (code tarif, remise
 * REMCLI) sont FIGÉS à l'enregistrement : on doit pouvoir expliquer plus tard pourquoi un tarif ou une
 * remise a changé. Le détail par article est dans OfferLine, les événements dans OfferHistory.
 */
#[ORM\Entity(repositoryClass: OfferRepository::class)]
#[ORM\Table(name: 'oc_offer')]
#[ORM\UniqueConstraint(name: 'uniq_oc_offer_number', columns: ['number'])]
#[ORM\Index(name: 'idx_oc_offer_status', columns: ['status'])]
#[ORM\Index(name: 'idx_oc_offer_customer', columns: ['customer_code'])]
class Offer
{
    public const TYPE_TARIF_SPE = 'tarif_spe';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 40)]
    private string $number;

    #[ORM\Column(length: 30)]
    private string $type = self::TYPE_TARIF_SPE;

    #[ORM\Column(length: 20, enumType: OcStatus::class)]
    private OcStatus $status = OcStatus::DRAFT;

    // ── Client, figé depuis X3 ───────────────────────────────────────────────
    #[ORM\Column(length: 30)]
    private string $customerCode;

    #[ORM\Column(length: 255)]
    private string $customerName;

    #[ORM\Column(length: 10, nullable: true)]
    private ?string $customerCurrency = null;

    /** Groupe tarifaire du client (BPCUSTOMER.TSCCOD_0). */
    #[ORM\Column(length: 30, nullable: true)]
    private ?string $customerTariffCode = null;

    /** Remise client REMCLI en %, telle que lue dans X3 à l'enregistrement. */
    #[ORM\Column(type: Types::DECIMAL, precision: 8, scale: 4, nullable: true)]
    private ?string $customerDiscount = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $customerDiscountStart = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $customerDiscountEnd = null;

    /** Date et heure de la lecture X3 des valeurs ci-dessus. */
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $x3ReadAt;

    // ── Validité et texte ────────────────────────────────────────────────────
    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private \DateTimeImmutable $startDate;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private \DateTimeImmutable $endDate;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $comment = null;

    // ── Personnes et dates ───────────────────────────────────────────────────
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    private ?User $createdBy = null;

    /** Nom du créateur au moment de la création (conservé même si le compte disparaît). */
    #[ORM\Column(length: 255)]
    private string $createdByName;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $updatedAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $submittedAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $decidedAt = null;

    /** Pour une OC créée à partir d'une OC rejetée : l'OC d'origine. */
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    private ?Offer $sourceOffer = null;

    /** @var Collection<int, OfferLine> */
    #[ORM\OneToMany(targetEntity: OfferLine::class, mappedBy: 'offer', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['position' => 'ASC'])]
    private Collection $lines;

    /** @var Collection<int, OfferHistory> */
    #[ORM\OneToMany(targetEntity: OfferHistory::class, mappedBy: 'offer', cascade: ['persist'])]
    #[ORM\OrderBy(['createdAt' => 'ASC', 'id' => 'ASC'])]
    private Collection $history;

    public function __construct()
    {
        $this->lines = new ArrayCollection();
        $this->history = new ArrayCollection();
        $now = new \DateTimeImmutable();
        $this->createdAt = $now;
        $this->updatedAt = $now;
        $this->x3ReadAt = $now;
    }

    public function getId(): ?int { return $this->id; }

    public function getNumber(): string { return $this->number; }
    public function setNumber(string $number): static { $this->number = $number; return $this; }

    public function getType(): string { return $this->type; }
    public function setType(string $type): static { $this->type = $type; return $this; }

    public function getStatus(): OcStatus { return $this->status; }
    public function setStatus(OcStatus $status): static { $this->status = $status; return $this; }

    public function getCustomerCode(): string { return $this->customerCode; }
    public function setCustomerCode(string $v): static { $this->customerCode = $v; return $this; }

    public function getCustomerName(): string { return $this->customerName; }
    public function setCustomerName(string $v): static { $this->customerName = $v; return $this; }

    public function getCustomerCurrency(): ?string { return $this->customerCurrency; }
    public function setCustomerCurrency(?string $v): static { $this->customerCurrency = $v; return $this; }

    public function getCustomerTariffCode(): ?string { return $this->customerTariffCode; }
    public function setCustomerTariffCode(?string $v): static { $this->customerTariffCode = $v; return $this; }

    public function getCustomerDiscount(): ?string { return $this->customerDiscount; }
    public function setCustomerDiscount(float|string|null $v): static
    {
        $this->customerDiscount = $v === null ? null : number_format((float) $v, 4, '.', '');
        return $this;
    }

    public function getCustomerDiscountStart(): ?\DateTimeImmutable { return $this->customerDiscountStart; }
    public function setCustomerDiscountStart(?\DateTimeImmutable $v): static { $this->customerDiscountStart = $v; return $this; }

    public function getCustomerDiscountEnd(): ?\DateTimeImmutable { return $this->customerDiscountEnd; }
    public function setCustomerDiscountEnd(?\DateTimeImmutable $v): static { $this->customerDiscountEnd = $v; return $this; }

    public function getX3ReadAt(): \DateTimeImmutable { return $this->x3ReadAt; }
    public function setX3ReadAt(\DateTimeImmutable $v): static { $this->x3ReadAt = $v; return $this; }

    public function getStartDate(): \DateTimeImmutable { return $this->startDate; }
    public function setStartDate(\DateTimeImmutable $v): static { $this->startDate = $v; return $this; }

    public function getEndDate(): \DateTimeImmutable { return $this->endDate; }
    public function setEndDate(\DateTimeImmutable $v): static { $this->endDate = $v; return $this; }

    public function getComment(): ?string { return $this->comment; }
    public function setComment(?string $v): static { $this->comment = $v; return $this; }

    public function getCreatedBy(): ?User { return $this->createdBy; }
    public function setCreatedBy(?User $v): static { $this->createdBy = $v; return $this; }

    public function getCreatedByName(): string { return $this->createdByName; }
    public function setCreatedByName(string $v): static { $this->createdByName = $v; return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }

    public function getUpdatedAt(): \DateTimeImmutable { return $this->updatedAt; }
    public function touch(): static { $this->updatedAt = new \DateTimeImmutable(); return $this; }

    public function getSubmittedAt(): ?\DateTimeImmutable { return $this->submittedAt; }
    public function setSubmittedAt(?\DateTimeImmutable $v): static { $this->submittedAt = $v; return $this; }

    public function getDecidedAt(): ?\DateTimeImmutable { return $this->decidedAt; }
    public function setDecidedAt(?\DateTimeImmutable $v): static { $this->decidedAt = $v; return $this; }

    public function getSourceOffer(): ?Offer { return $this->sourceOffer; }
    public function setSourceOffer(?Offer $v): static { $this->sourceOffer = $v; return $this; }

    /** @return Collection<int, OfferLine> */
    public function getLines(): Collection { return $this->lines; }

    public function addLine(OfferLine $line): static
    {
        if (!$this->lines->contains($line)) {
            $this->lines->add($line);
            $line->setOffer($this);
        }
        return $this;
    }

    public function clearLines(): static { $this->lines->clear(); return $this; }

    /** @return Collection<int, OfferHistory> */
    public function getHistory(): Collection { return $this->history; }

    public function addHistory(OfferHistory $entry): static
    {
        if (!$this->history->contains($entry)) {
            $this->history->add($entry);
            $entry->setOffer($this);
        }
        return $this;
    }
}
