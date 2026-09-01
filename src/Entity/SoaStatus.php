<?php

namespace App\Entity;

use App\Repository\SoaStatusRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SoaStatusRepository::class)]
#[ORM\Table(name: 'soa_status')]
class SoaStatus
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50, unique: true)]
    private string $code = '';

    #[ORM\Column(length: 100)]
    private string $label = '';

    /** Couleur CSS hex (#rrggbb) pour l'affichage du badge */
    #[ORM\Column(length: 20)]
    private string $color = '#6c757d';

    /** Couleur de texte hex pour le badge */
    #[ORM\Column(length: 20)]
    private string $textColor = '#ffffff';

    #[ORM\Column]
    private int $orderIndex = 0;

    public function getId(): ?int { return $this->id; }

    public function getCode(): string { return $this->code; }
    public function setCode(string $code): static { $this->code = $code; return $this; }

    public function getLabel(): string { return $this->label; }
    public function setLabel(string $label): static { $this->label = $label; return $this; }

    public function getColor(): string { return $this->color; }
    public function setColor(string $color): static { $this->color = $color; return $this; }

    public function getTextColor(): string { return $this->textColor; }
    public function setTextColor(string $textColor): static { $this->textColor = $textColor; return $this; }

    public function getOrderIndex(): int { return $this->orderIndex; }
    public function setOrderIndex(int $orderIndex): static { $this->orderIndex = $orderIndex; return $this; }
}
