<?php

namespace App\Entity;

use App\Repository\X3CollectionRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: X3CollectionRepository::class)]
#[ORM\Table(name: 'x3_collection')]
#[ORM\UniqueConstraint(name: 'uniq_seriescode', columns: ['series_code'])]
class X3Collection
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 20)]
    private ?string $seriesCode = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSeriesCode(): ?string
    {
        return $this->seriesCode;
    }

    public function setSeriesCode(string $seriesCode): static
    {
        $this->seriesCode = $seriesCode;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }
}
