<?php

namespace App\Entity;

use App\Repository\UserStatExclusionRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserStatExclusionRepository::class)]
#[ORM\Table(name: 'user_stat_exclusion')]
#[ORM\UniqueConstraint(columns: ['user_id', 'stat_key'])]
class UserStatExclusion
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\Column(length: 100)]
    private string $statKey = '';

    public function getId(): ?int { return $this->id; }

    public function getUser(): User { return $this->user; }
    public function setUser(User $user): static { $this->user = $user; return $this; }

    public function getStatKey(): string { return $this->statKey; }
    public function setStatKey(string $statKey): static { $this->statKey = $statKey; return $this; }
}
