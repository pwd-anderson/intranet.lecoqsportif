<?php

namespace App\Entity;

use App\Repository\IntranetPageRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: IntranetPageRepository::class)]
class IntranetPage
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, unique: true)]
    private ?string $routeName = null;

    #[ORM\Column(length: 255)]
    private ?string $label = null;

    /**
     * @var Collection<int, AccessGroup>
     */
    #[ORM\ManyToMany(targetEntity: AccessGroup::class)]
    private Collection $accessGroups;

    public function __construct()
    {
        $this->accessGroups = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRouteName(): ?string
    {
        return $this->routeName;
    }

    public function setRouteName(string $routeName): static
    {
        $this->routeName = $routeName;

        return $this;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(string $label): static
    {
        $this->label = $label;

        return $this;
    }

    /**
     * @return Collection<int, AccessGroup>
     */
    public function getAccessGroups(): Collection
    {
        return $this->accessGroups;
    }

    public function addAccessGroup(AccessGroup $accessGroup): static
    {
        if (!$this->accessGroups->contains($accessGroup)) {
            $this->accessGroups->add($accessGroup);
        }

        return $this;
    }

    public function removeAccessGroup(AccessGroup $accessGroup): static
    {
        $this->accessGroups->removeElement($accessGroup);

        return $this;
    }

    public function __toString(): string
    {
        return $this->label ?? '';
    }
}
