<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\FormationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FormationRepository::class)]
class Formation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $label = null;

    #[ORM\Column(nullable: true)]
    private ?int $order_number = null;

    #[ORM\ManyToOne(inversedBy: 'formations')]
    private ?SchoolYear $id_school_year = null;

    /**
     * @var Collection<int, PedagogicalInterruptions>
     */
    #[ORM\OneToMany(mappedBy: 'id_formation', targetEntity: PedagogicalInterruptions::class)]
    private Collection $pedagogicalInterruptions;

    /**
     * @var Collection<int, Semesters>
     */
    #[ORM\OneToMany(mappedBy: 'id_formation', targetEntity: Semesters::class)]
    private Collection $semesters;

    /**
     * @var Collection<int, Groups>
     */
    #[ORM\OneToMany(mappedBy: 'id_formation', targetEntity: Groups::class)]
    private Collection $groups;


    public function __construct()
    {
        $this->pedagogicalInterruptions = new ArrayCollection();
        $this->semesters = new ArrayCollection();
        $this->groups = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(?string $label): static
    {
        $this->label = $label;

        return $this;
    }

    public function getOrderNumber(): ?int
    {
        return $this->order_number;
    }

    public function setOrderNumber(?int $order_number): static
    {
        $this->order_number = $order_number;

        return $this;
    }

    public function getIdSchoolYear(): ?SchoolYear
    {
        return $this->id_school_year;
    }

    public function setIdSchoolYear(?SchoolYear $id_school_year): static
    {
        $this->id_school_year = $id_school_year;

        return $this;
    }

    /**
     * @return Collection<int, PedagogicalInterruptions>
     */
    public function getPedagogicalInterruptions(): Collection
    {
        return $this->pedagogicalInterruptions;
    }

    public function addPedagogicalInterruption(PedagogicalInterruptions $pedagogicalInterruption): static
    {
        if (!$this->pedagogicalInterruptions->contains($pedagogicalInterruption)) {
            $this->pedagogicalInterruptions->add($pedagogicalInterruption);
            $pedagogicalInterruption->setIdFormation($this);
        }

        return $this;
    }

    public function removePedagogicalInterruption(PedagogicalInterruptions $pedagogicalInterruption): static
    {
        if ($this->pedagogicalInterruptions->removeElement($pedagogicalInterruption)) {
            if ($pedagogicalInterruption->getIdFormation() === $this) {
                $pedagogicalInterruption->setIdFormation(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Semesters>
     */
    public function getSemesters(): Collection
    {
        return $this->semesters;
    }

    public function addSemester(Semesters $semester): static
    {
        if (!$this->semesters->contains($semester)) {
            $this->semesters->add($semester);
            $semester->setIdFormation($this);
        }

        return $this;
    }

    public function removeSemester(Semesters $semester): static
    {
        if ($this->semesters->removeElement($semester)) {
            if ($semester->getIdFormation() === $this) {
                $semester->setIdFormation(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Groups>
     */
    public function getGroups(): Collection
    {
        return $this->groups;
    }

    public function addGroup(Groups $group): static
    {
        if (!$this->groups->contains($group)) {
            $this->groups->add($group);
            $group->setIdFormation($this);
        }

        return $this;
    }

    public function removeGroup(Groups $group): static
    {
        if ($this->groups->removeElement($group)) {
            if ($group->getIdFormation() === $this) {
                $group->setIdFormation(null);
            }
        }

        return $this;
    }

}
