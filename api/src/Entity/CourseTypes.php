<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\CourseTypesRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CourseTypesRepository::class)]
class CourseTypes
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $name = null;

    #[ORM\Column(nullable: true)]
    private ?float $hourly_rate = null;

    #[ORM\ManyToOne(inversedBy: 'courseTypes')]
    private ?SchoolYear $id_school_year = null;

    /**
     * @var Collection<int, Groups>
     */
    #[ORM\OneToMany(mappedBy: 'id_course_types', targetEntity: Groups::class)]
    private Collection $groups;

    /**
     * @var Collection<int, Assignments>
     */
    #[ORM\OneToMany(mappedBy: 'id_course_types', targetEntity: Assignments::class)]
    private Collection $assignments;

    public function __construct()
    {
        $this->groups = new ArrayCollection();
        $this->assignments = new ArrayCollection();
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

    public function getHourlyRate(): ?float
    {
        return $this->hourly_rate;
    }

    public function setHourlyRate(?float $hourly_rate): static
    {
        $this->hourly_rate = $hourly_rate;

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
            $group->setIdCourseTypes($this);
        }

        return $this;
    }

    public function removeGroup(Groups $group): static
    {
        if ($this->groups->removeElement($group)) {
            if ($group->getIdCourseTypes() === $this) {
                $group->setIdCourseTypes(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Assignments>
     */
    public function getAssignments(): Collection
    {
        return $this->assignments;
    }

    public function addAssignment(Assignments $assignment): static
    {
        if (!$this->assignments->contains($assignment)) {
            $this->assignments->add($assignment);
            $assignment->setIdCourseTypes($this);
        }

        return $this;
    }

    public function removeAssignment(Assignments $assignment): static
    {
        if ($this->assignments->removeElement($assignment)) {
            if ($assignment->getIdCourseTypes() === $this) {
                $assignment->setIdCourseTypes(null);
            }
        }

        return $this;
    }
}
