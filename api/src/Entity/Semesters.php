<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\SemestersRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SemestersRepository::class)]
class Semesters
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $name = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $start_date = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $end_date = null;

    #[ORM\Column(nullable: true)]
    private ?int $order_number = null;

    #[ORM\ManyToOne(inversedBy: 'semesters')]
    private ?Formation $id_formation = null;

    /**
     * @var Collection<int, Resources>
     */
    #[ORM\OneToMany(mappedBy: 'id_semesters', targetEntity: Resources::class)]
    private Collection $resources;

    public function __construct()
    {
        $this->resources = new ArrayCollection();
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

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getStartDate(): ?\DateTimeInterface
    {
        return $this->start_date;
    }

    public function setStartDate(?\DateTimeInterface $start_date): static
    {
        $this->start_date = $start_date;

        return $this;
    }

    public function getEndDate(): ?\DateTimeInterface
    {
        return $this->end_date;
    }

    public function setEndDate(?\DateTimeInterface $end_date): static
    {
        $this->end_date = $end_date;

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

    public function getIdFormation(): ?Formation
    {
        return $this->id_formation;
    }

    public function setIdFormation(?Formation $id_formation): static
    {
        $this->id_formation = $id_formation;

        return $this;
    }

    /**
     * @return Collection<int, Resources>
     */
    public function getResources(): Collection
    {
        return $this->resources;
    }

    public function addResource(Resources $resource): static
    {
        if (!$this->resources->contains($resource)) {
            $this->resources->add($resource);
            $resource->setIdSemesters($this);
        }

        return $this;
    }

    public function removeResource(Resources $resource): static
    {
        if ($this->resources->removeElement($resource)) {
            if ($resource->getIdSemesters() === $this) {
                $resource->setIdSemesters(null);
            }
        }

        return $this;
    }
}
