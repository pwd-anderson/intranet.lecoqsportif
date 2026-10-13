<?php

namespace App\Entity\Oc;

use App\Repository\Oc\OfferLineRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Une ligne d'OC = un article (parent, sans variant). Elle conserve À LA FOIS ce que X3 donnait à
 * l'enregistrement (tarif X3, MSRP X3, collection...) et ce que le commercial a décidé (tarif OC, MSRP
 * utilisé, TVA, marge, quantités) : on peut ainsi comparer plus tard l'un et l'autre.
 */
#[ORM\Entity(repositoryClass: OfferLineRepository::class)]
#[ORM\Table(name: 'oc_offer_line')]
#[ORM\Index(name: 'idx_oc_offer_line_article', columns: ['article_code'])]
class OfferLine
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'lines')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Offer $offer;

    #[ORM\Column]
    private int $position = 1;

    // ── Article, figé depuis X3 ──────────────────────────────────────────────
    #[ORM\Column(length: 50)]
    private string $articleCode;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $articleLabel = null;

    /** Collection la plus récente de l'article à l'enregistrement. */
    #[ORM\Column(length: 30, nullable: true)]
    private ?string $collection = null;

    /** Code modèle (ITMMASTER.ZMODELCOD_0), clé de lecture des tarifs. */
    #[ORM\Column(length: 50, nullable: true)]
    private ?string $modelCode = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 4, nullable: true)]
    private ?string $x3Tariff = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 4, nullable: true)]
    private ?string $x3Msrp = null;

    /** Prix d'achat X3 (liste 1PFCOL) et sa devise, figés à l'enregistrement. Visibles de la Direction seulement (vue de validation). */
    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 4, nullable: true)]
    private ?string $purchasePrice = null;

    #[ORM\Column(length: 10, nullable: true)]
    private ?string $purchaseCurrency = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $x3ReadAt;

    // ── Décision du commercial ───────────────────────────────────────────────
    /** Tarif OC saisi. */
    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 4)]
    private string $tariff = '0.0000';

    /** MSRP utilisé pour la marge (proposé depuis X3, modifiable). */
    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 4, nullable: true)]
    private ?string $msrp = null;

    /** TVA appliquée pour calculer la marge (placeholder 20 % tant que sa source n'est pas définie). */
    #[ORM\Column(type: Types::DECIMAL, precision: 6, scale: 2, nullable: true)]
    private ?string $vatRate = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 8, scale: 4, nullable: true)]
    private ?string $margin = null;

    /**
     * Prix ENVOYÉ à X3 (WDISPRI), calculé par le serveur : tarif OC / (1 − remise client / 100). X3 applique la remise
     * client à toutes les lignes ; en la compensant ici, le client paie bien le tarif OC saisi (voir OfferCalculator).
     */
    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 4, nullable: true)]
    private ?string $injectedPrice = null;

    /** Remise client (%) utilisée pour calculer le prix envoyé : figée avec la ligne pour l'audit. */
    #[ORM\Column(type: Types::DECIMAL, precision: 8, scale: 4, nullable: true)]
    private ?string $injectedDiscount = null;

    #[ORM\Column]
    private int $minQty = 0;

    #[ORM\Column]
    private int $maxQty = 999999;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $comment = null;

    public function __construct()
    {
        $this->x3ReadAt = new \DateTimeImmutable();
    }

    private static function dec(float|string|null $v, int $scale): ?string
    {
        return $v === null || $v === '' ? null : number_format((float) $v, $scale, '.', '');
    }

    public function getId(): ?int { return $this->id; }

    public function getOffer(): Offer { return $this->offer; }
    public function setOffer(Offer $offer): static { $this->offer = $offer; return $this; }

    public function getPosition(): int { return $this->position; }
    public function setPosition(int $v): static { $this->position = $v; return $this; }

    public function getArticleCode(): string { return $this->articleCode; }
    public function setArticleCode(string $v): static { $this->articleCode = $v; return $this; }

    public function getArticleLabel(): ?string { return $this->articleLabel; }
    public function setArticleLabel(?string $v): static { $this->articleLabel = $v; return $this; }

    public function getCollection(): ?string { return $this->collection; }
    public function setCollection(?string $v): static { $this->collection = $v; return $this; }

    public function getModelCode(): ?string { return $this->modelCode; }
    public function setModelCode(?string $v): static { $this->modelCode = $v; return $this; }

    public function getX3Tariff(): ?string { return $this->x3Tariff; }
    public function setX3Tariff(float|string|null $v): static { $this->x3Tariff = self::dec($v, 4); return $this; }

    public function getX3Msrp(): ?string { return $this->x3Msrp; }
    public function setX3Msrp(float|string|null $v): static { $this->x3Msrp = self::dec($v, 4); return $this; }

    public function getX3ReadAt(): \DateTimeImmutable { return $this->x3ReadAt; }
    public function setX3ReadAt(\DateTimeImmutable $v): static { $this->x3ReadAt = $v; return $this; }

    public function getTariff(): string { return $this->tariff; }
    public function setTariff(float|string $v): static { $this->tariff = self::dec($v, 4) ?? '0.0000'; return $this; }

    public function getMsrp(): ?string { return $this->msrp; }
    public function setMsrp(float|string|null $v): static { $this->msrp = self::dec($v, 4); return $this; }

    public function getVatRate(): ?string { return $this->vatRate; }
    public function setVatRate(float|string|null $v): static { $this->vatRate = self::dec($v, 2); return $this; }

    public function getMargin(): ?string { return $this->margin; }
    public function setMargin(float|string|null $v): static { $this->margin = self::dec($v, 4); return $this; }

    public function getPurchasePrice(): ?string { return $this->purchasePrice; }
    public function setPurchasePrice(float|string|null $v): static { $this->purchasePrice = self::dec($v, 4); return $this; }
    public function getPurchaseCurrency(): ?string { return $this->purchaseCurrency; }
    public function setPurchaseCurrency(?string $v): static { $this->purchaseCurrency = $v !== null && trim($v) !== '' ? trim($v) : null; return $this; }

    public function getInjectedPrice(): ?string { return $this->injectedPrice; }
    public function setInjectedPrice(float|string|null $v): static { $this->injectedPrice = self::dec($v, 4); return $this; }

    public function getInjectedDiscount(): ?string { return $this->injectedDiscount; }
    public function setInjectedDiscount(float|string|null $v): static { $this->injectedDiscount = self::dec($v, 4); return $this; }

    public function getMinQty(): int { return $this->minQty; }
    public function setMinQty(int $v): static { $this->minQty = $v; return $this; }

    public function getMaxQty(): int { return $this->maxQty; }
    public function setMaxQty(int $v): static { $this->maxQty = $v; return $this; }

    public function getComment(): ?string { return $this->comment; }
    public function setComment(?string $v): static { $this->comment = $v; return $this; }
}
