<?php

namespace App\Entity;

use App\Repository\SoaRequestProductRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SoaRequestProductRepository::class)]
#[ORM\Table(name: 'soa_request_product')]
class SoaRequestProduct
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: SoaRequest::class, inversedBy: 'products')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private SoaRequest $soaRequest;

    #[ORM\Column(length: 50)]
    private string $articleCode = '';

    #[ORM\Column(length: 255)]
    private string $articleNom = '';

    /** Prix d'achat récupéré depuis X3 au moment de la saisie */
    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 2, nullable: true)]
    private ?string $prixAchat = null;

    #[ORM\Column]
    private int $qteMax = 0;

    /** Montant SOA saisi par le commercial (par unité) */
    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 2)]
    private string $montantSoa = '0.00';

    #[ORM\Column(length: 10)]
    private string $devise = 'EUR';

    /** Montant max à réclamer = qteMax × montantSoa (calculé et stocké) */
    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 2)]
    private string $montantMax = '0.00';

    /** CA total facturé à ce client pour cet article sur l'année en cours (depuis X3) */
    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 2, nullable: true)]
    private ?string $caFactureAnnee = null;

    /** ROI calculé = montantMax / caFactureAnnee × 100 */
    #[ORM\Column(type: Types::DECIMAL, precision: 8, scale: 2, nullable: true)]
    private ?string $roi = null;

    /** Quantité réellement vendue — saisie lors de l'étape "preuves" */
    #[ORM\Column(nullable: true)]
    private ?int $qteVendue = null;

    public function getId(): ?int { return $this->id; }

    public function getSoaRequest(): SoaRequest { return $this->soaRequest; }
    public function setSoaRequest(SoaRequest $soaRequest): static { $this->soaRequest = $soaRequest; return $this; }

    public function getArticleCode(): string { return $this->articleCode; }
    public function setArticleCode(string $articleCode): static { $this->articleCode = $articleCode; return $this; }

    public function getArticleNom(): string { return $this->articleNom; }
    public function setArticleNom(string $articleNom): static { $this->articleNom = $articleNom; return $this; }

    public function getPrixAchat(): ?string { return $this->prixAchat; }
    public function setPrixAchat(?string $prixAchat): static { $this->prixAchat = $prixAchat; return $this; }

    public function getQteMax(): int { return $this->qteMax; }
    public function setQteMax(int $qteMax): static { $this->qteMax = $qteMax; return $this; }

    public function getMontantSoa(): string { return $this->montantSoa; }
    public function setMontantSoa(string $montantSoa): static { $this->montantSoa = $montantSoa; return $this; }

    public function getDevise(): string { return $this->devise; }
    public function setDevise(string $devise): static { $this->devise = $devise; return $this; }

    public function getMontantMax(): string { return $this->montantMax; }
    public function setMontantMax(string $montantMax): static { $this->montantMax = $montantMax; return $this; }

    public function getCaFactureAnnee(): ?string { return $this->caFactureAnnee; }
    public function setCaFactureAnnee(?string $caFactureAnnee): static { $this->caFactureAnnee = $caFactureAnnee; return $this; }

    public function getRoi(): ?string { return $this->roi; }
    public function setRoi(?string $roi): static { $this->roi = $roi; return $this; }

    public function getQteVendue(): ?int { return $this->qteVendue; }
    public function setQteVendue(?int $qteVendue): static { $this->qteVendue = $qteVendue; return $this; }

    public function recalculate(): void
    {
        $this->montantMax = (string) ((float) $this->montantSoa * $this->qteMax);

        if ($this->caFactureAnnee !== null && (float) $this->caFactureAnnee > 0) {
            $this->roi = (string) round((float) $this->montantMax / (float) $this->caFactureAnnee * 100, 2);
        } else {
            $this->roi = null;
        }
    }
}
