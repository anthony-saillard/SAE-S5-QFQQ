<?php

namespace App\Entity;

use App\Repository\GroupsRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: GroupsRepository::class)]

class Groups
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $name = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(nullable: true)]
    private ?int $order_number = null;

    #[ORM\ManyToOne(targetEntity: self::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: "SET NULL")]
    private ?self $id_groups = null;


    #[ORM\ManyToOne(inversedBy: 'groups')]
    private ?CourseTypes $id_course_types = null;

    #[ORM\ManyToOne(inversedBy: 'groups')]
    private ?Formation $id_formation = null;

    /**
     * @var Collection<int, CourseTeacher>
     */
    #[ORM\OneToMany(mappedBy: 'id_groups', targetEntity: CourseTeacher::class)]
    private Collection $courseTeachers;

    public function __construct()
    {
        $this->courseTeachers = new ArrayCollection();
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

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

    public function getIdGroups(): ?self
    {
        return $this->id_groups;
    }

    public function setIdGroups(?self $id_groups): static
    {
        $this->id_groups = $id_groups;

        return $this;
    }

    public function getIdCourseTypes(): ?CourseTypes
    {
        return $this->id_course_types;
    }

    public function setIdCourseTypes(?CourseTypes $id_course_types): static
    {
        $this->id_course_types = $id_course_types;

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
     * @return Collection<int, CourseTeacher>
     */
    public function getCourseTeachers(): Collection
    {
        return $this->courseTeachers;
    }

    public function addCourseTeacher(CourseTeacher $courseTeacher): static
    {
        if (!$this->courseTeachers->contains($courseTeacher)) {
            $this->courseTeachers->add($courseTeacher);
            $courseTeacher->setIdGroups($this);
        }

        return $this;
    }

    public function removeCourseTeacher(CourseTeacher $courseTeacher): static
    {
        if ($this->courseTeachers->removeElement($courseTeacher)) {
            if ($courseTeacher->getIdGroups() === $this) {
                $courseTeacher->setIdGroups(null);
            }
        }

        return $this;
    }
}
