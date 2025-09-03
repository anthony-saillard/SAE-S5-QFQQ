<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\SubResourcesRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SubResourcesRepository::class)]
class SubResources
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $name = null;

    #[ORM\ManyToOne(inversedBy: 'subResources')]
    private ?Resources $id_resources = null;

    /**
     * @var Collection<int, Assignments>
     */
    #[ORM\OneToMany(mappedBy: 'id_sub_resources', targetEntity: Assignments::class)]
    private Collection $assignments;

    #[ORM\ManyToOne(inversedBy: 'subResources')]
    private ?Users $id_users = null;

    /**
     * @var Collection<int, Notifications>
     */
    #[ORM\OneToMany(mappedBy: 'id_sub_resources', targetEntity: Notifications::class)]
    private Collection $notifications;

    /**
     * @var Collection<int, CourseTeacher>
     */
    #[ORM\OneToMany(mappedBy: 'id_sub_ressource', targetEntity: CourseTeacher::class)]
    private Collection $courseTeachers;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $status = null;

    public function __construct()
    {
        $this->assignments = new ArrayCollection();
        $this->notifications = new ArrayCollection();
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

    public function getIdResources(): ?Resources
    {
        return $this->id_resources;
    }

    public function setIdResources(?Resources $id_resources): static
    {
        $this->id_resources = $id_resources;

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
            $assignment->setIdSubResources($this);
        }

        return $this;
    }

    public function removeAssignment(Assignments $assignment): static
    {
        if ($this->assignments->removeElement($assignment)) {
            if ($assignment->getIdSubResources() === $this) {
                $assignment->setIdSubResources(null);
            }
        }

        return $this;
    }

    public function getIdUsers(): ?Users
    {
        return $this->id_users;
    }

    public function setIdUsers(?Users $id_users): static
    {
        $this->id_users = $id_users;

        return $this;
    }

    /**
     * @return Collection<int, Notifications>
     */
    public function getNotifications(): Collection
    {
        return $this->notifications;
    }

    public function addNotification(Notifications $notification): static
    {
        if (!$this->notifications->contains($notification)) {
            $this->notifications->add($notification);
            $notification->setIdSubResources($this);
        }

        return $this;
    }

    public function removeNotification(Notifications $notification): static
    {
        if ($this->notifications->removeElement($notification)) {
            if ($notification->getIdSubResources() === $this) {
                $notification->setIdSubResources(null);
            }
        }

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
            $courseTeacher->setIdSubResource($this);
        }

        return $this;
    }

    public function removeCourseTeacher(CourseTeacher $courseTeacher): static
    {
        if ($this->courseTeachers->removeElement($courseTeacher)) {
            if ($courseTeacher->getIdSubResource() === $this) {
                $courseTeacher->setIdSubResource(null);
            }
        }

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(?string $status): static
    {
        $this->status = $status;
        return $this;
    }
}