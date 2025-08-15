<?php

namespace App\Entity;

use App\Repository\ExchangeRatesMoyenRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ExchangeRatesMoyenRepository::class)]
class ExchangeRatesMoyen
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 10)]
    private ?string $sourceCurrency = null;

    #[ORM\Column(length: 10)]
    private ?string $targetCurrency = null;

    #[ORM\Column]
    private ?float $rate = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTime $dateCours = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $insertedAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSourceCurrency(): ?string
    {
        return $this->sourceCurrency;
    }

    public function setSourceCurrency(string $sourceCurrency): static
    {
        $this->sourceCurrency = $sourceCurrency;

        return $this;
    }

    public function getTargetCurrency(): ?string
    {
        return $this->targetCurrency;
    }

    public function setTargetCurrency(string $targetCurrency): static
    {
        $this->targetCurrency = $targetCurrency;

        return $this;
    }

    public function getRate(): ?float
    {
        return $this->rate;
    }

    public function setRate(float $rate): static
    {
        $this->rate = $rate;

        return $this;
    }

    public function getDateCours(): ?\DateTime
    {
        return $this->dateCours;
    }

    public function setDateCours(\DateTime $dateCours): static
    {
        $this->dateCours = $dateCours;

        return $this;
    }

    public function getInsertedAt(): ?\DateTimeImmutable
    {
        return $this->insertedAt;
    }

    public function setInsertedAt(\DateTimeImmutable $insertedAt): static
    {
        $this->insertedAt = $insertedAt;

        return $this;
    }
}
