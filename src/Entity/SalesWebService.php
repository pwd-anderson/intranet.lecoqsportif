<?php

namespace App\Entity;

use App\Repository\SalesWebServiceRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SalesWebServiceRepository::class)]
#[ORM\Table(name: 'sales_web_service')]
class SalesWebService
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private string $name = '';

    #[ORM\Column(type: Types::TEXT)]
    private string $parameter = '';

    #[ORM\Column]
    private bool $executed = false;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $result = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $message = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $erpDocumentId = null;

    #[ORM\Column(nullable: true)]
    private ?int $soaRequestId = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $createdAt;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int { return $this->id; }
    public function getName(): string { return $this->name; }
    public function setName(string $name): static { $this->name = $name; return $this; }
    public function getParameter(): string { return $this->parameter; }
    public function setParameter(string $parameter): static { $this->parameter = $parameter; return $this; }
    public function isExecuted(): bool { return $this->executed; }
    public function setExecuted(bool $executed): static { $this->executed = $executed; return $this; }
    public function getResult(): ?string { return $this->result; }
    public function setResult(?string $result): static { $this->result = $result; return $this; }
    public function getMessage(): ?string { return $this->message; }
    public function setMessage(?string $message): static { $this->message = $message; return $this; }
    public function getErpDocumentId(): ?string { return $this->erpDocumentId; }
    public function setErpDocumentId(?string $id): static { $this->erpDocumentId = $id; return $this; }
    public function getSoaRequestId(): ?int { return $this->soaRequestId; }
    public function setSoaRequestId(?int $id): static { $this->soaRequestId = $id; return $this; }

    public function getCreatedAt(): \DateTimeInterface { return $this->createdAt; }
    public function getUpdatedAt(): ?\DateTimeInterface { return $this->updatedAt; }
    public function setUpdatedAt(?\DateTimeInterface $dt): static { $this->updatedAt = $dt; return $this; }
}
