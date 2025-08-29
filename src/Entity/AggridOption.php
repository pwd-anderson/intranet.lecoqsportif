<?php

namespace App\Entity;

use App\Repository\AggridOptionRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AggridOptionRepository::class)]
class AggridOption
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $gridName = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $field = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $headerName = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $type = null;

    #[ORM\Column(nullable: true)]
    private ?int $minWidth = null;

    #[ORM\Column(nullable: true)]
    private ?bool $sortable = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $filter = null;

    #[ORM\Column(nullable: true)]
    private ?array $cellStyle = null;

    #[ORM\Column(nullable: true)]
    private ?int $flex = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $aggFunc = null;

    #[ORM\Column(nullable: true)]
    private ?bool $visible = null;

    #[ORM\Column(nullable: true)]
    private ?int $orderIndex = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $cellClass = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getGridName(): ?string
    {
        return $this->gridName;
    }

    public function setGridName(?string $gridName): static
    {
        $this->gridName = $gridName;

        return $this;
    }

    public function getField(): ?string
    {
        return $this->field;
    }

    public function setField(?string $field): static
    {
        $this->field = $field;

        return $this;
    }

    public function getHeaderName(): ?string
    {
        return $this->headerName;
    }

    public function setHeaderName(?string $headerName): static
    {
        $this->headerName = $headerName;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getMinWidth(): ?int
    {
        return $this->minWidth;
    }

    public function setMinWidth(?int $minWidth): static
    {
        $this->minWidth = $minWidth;

        return $this;
    }

    public function isSortable(): ?bool
    {
        return $this->sortable;
    }

    public function setSortable(?bool $sortable): static
    {
        $this->sortable = $sortable;

        return $this;
    }

    public function getFilter(): ?string
    {
        return $this->filter;
    }

    public function setFilter(?string $filter): static
    {
        $this->filter = $filter;

        return $this;
    }

    public function getCellStyle(): ?array
    {
        return $this->cellStyle;
    }

    public function setCellStyle(?array $cellStyle): static
    {
        $this->cellStyle = $cellStyle;

        return $this;
    }

    public function getFlex(): ?int
    {
        return $this->flex;
    }

    public function setFlex(?int $flex): static
    {
        $this->flex = $flex;

        return $this;
    }

    public function getAggFunc(): ?string
    {
        return $this->aggFunc;
    }

    public function setAggFunc(?string $aggFunc): static
    {
        $this->aggFunc = $aggFunc;

        return $this;
    }

    public function isVisible(): ?bool
    {
        return $this->visible;
    }

    public function setVisible(?bool $visible): static
    {
        $this->visible = $visible;

        return $this;
    }

    public function getOrderIndex(): ?int
    {
        return $this->orderIndex;
    }

    public function setOrderIndex(?int $orderIndex): static
    {
        $this->orderIndex = $orderIndex;

        return $this;
    }

    public function getCellClass(): ?string
    {
        return $this->cellClass;
    }

    public function setCellClass(?string $cellClass): static
    {
        $this->cellClass = $cellClass;

        return $this;
    }
}
