<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\SchoolYearRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SchoolYearRepository::class)]
class SchoolYear
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $label = null;

    /**
     * @var Collection<int, Formation>
     */
    #[ORM\OneToMany(mappedBy: 'id_school_year', targetEntity: Formation::class)]
    private Collection $formations;

    /**
     * @var Collection<int, CourseTypes>
     */
    #[ORM\OneToMany(mappedBy: 'id_school_year', targetEntity: CourseTypes::class)]
    private Collection $courseTypes;

    #[ORM\Column(nullable: true)]
    private ?bool $current_school_year = null;

    public function __construct()
    {
        $this->formations = new ArrayCollection();
        $this->courseTypes = new ArrayCollection();
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

    /**
     * @return Collection<int, Formation>
     */
    public function getFormations(): Collection
    {
        return $this->formations;
    }

    public function addFormation(Formation $formation): static
    {
        if (!$this->formations->contains($formation)) {
            $this->formations->add($formation);
            $formation->setIdSchoolYear($this);
        }

        return $this;
    }

    public function removeFormation(Formation $formation): static
    {
        if ($this->formations->removeElement($formation)) {
            if ($formation->getIdSchoolYear() === $this) {
                $formation->setIdSchoolYear(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, CourseTypes>
     */
    public function getCourseTypes(): Collection
    {
        return $this->courseTypes;
    }

    public function addCourseType(CourseTypes $courseType): static
    {
        if (!$this->courseTypes->contains($courseType)) {
            $this->courseTypes->add($courseType);
            $courseType->setIdSchoolYear($this);
        }

        return $this;
    }

    public function removeCourseType(CourseTypes $courseType): static
    {
        if ($this->courseTypes->removeElement($courseType)) {
            if ($courseType->getIdSchoolYear() === $this) {
                $courseType->setIdSchoolYear(null);
            }
        }

        return $this;
    }

    public function isCurrentSchoolYear(): ?bool
    {
        return $this->current_school_year;
    }

    public function setCurrentSchoolYear(?bool $current_school_year): static
    {
        $this->current_school_year = $current_school_year;

        return $this;
    }
}
