<?php

namespace App\Entity;

use App\Repository\KpiDeckPresentationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: KpiDeckPresentationRepository::class)]
class KpiDeckPresentation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 60)]
    private ?string $viewKey = null;

    #[ORM\Column]
    private ?int $year = null;

    #[ORM\Column(nullable: true)]
    private ?int $week = null;

    #[ORM\Column(length: 40)]
    private ?string $storeKey = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $commentHtml = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private ?\DateTimeImmutable $createDate = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private ?\DateTimeImmutable $updateDate = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getViewKey(): ?string
    {
        return $this->viewKey;
    }

    public function setViewKey(string $viewKey): static
    {
        $this->viewKey = $viewKey;

        return $this;
    }

    public function getYear(): ?int
    {
        return $this->year;
    }

    public function setYear(int $year): static
    {
        $this->year = $year;

        return $this;
    }

    public function getWeek(): ?int
    {
        return $this->week;
    }

    public function setWeek(?int $week): static
    {
        $this->week = $week;

        return $this;
    }

    public function getStoreKey(): ?string
    {
        return $this->storeKey;
    }

    public function setStoreKey(string $storeKey): static
    {
        $this->storeKey = $storeKey;

        return $this;
    }

    public function getCommentHtml(): ?string
    {
        return $this->commentHtml;
    }

    public function setCommentHtml(?string $commentHtml): static
    {
        $this->commentHtml = $commentHtml;

        return $this;
    }

    public function getCreateDate(): ?\DateTimeImmutable
    {
        return $this->createDate;
    }

    public function setCreateDate(\DateTimeImmutable $createDate): static
    {
        $this->createDate = $createDate;

        return $this;
    }

    public function getUpdateDate(): ?\DateTimeImmutable
    {
        return $this->updateDate;
    }

    public function setUpdateDate(\DateTimeImmutable $updateDate): static
    {
        $this->updateDate = $updateDate;

        return $this;
    }
}
