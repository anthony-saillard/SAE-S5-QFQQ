<?php

namespace App\Entity;

use App\Repository\AssignmentsRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AssignmentsRepository::class)]
class Assignments
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $allocated_hours = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $assignment_date = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $annotation = null;

    #[ORM\ManyToOne(inversedBy: 'assignments')]
    private ?SubResources $id_sub_resources = null;

    #[ORM\ManyToOne(inversedBy: 'assignments')]
    private ?Users $id_users = null;

    /**
     * @var Collection<int, Notifications>
     */
    #[ORM\OneToMany(mappedBy: 'id_assignments', targetEntity: Notifications::class)]
    private Collection $notifications;

    #[ORM\ManyToOne(inversedBy: 'assignments')]
    private ?CourseTypes $id_course_types = null;

    public function __construct()
    {
        $this->notifications = new ArrayCollection();
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

    public function getAllocatedHours(): ?float
    {
        return $this->allocated_hours;
    }

    public function setAllocatedHours(?float $allocated_hours): static
    {
        $this->allocated_hours = $allocated_hours;

        return $this;
    }

    public function getAssignmentDate(): ?\DateTimeInterface
    {
        return $this->assignment_date;
    }

    public function setAssignmentDate(?\DateTimeInterface $assignment_date): static
    {
        $this->assignment_date = $assignment_date;

        return $this;
    }

    public function getAnnotation(): ?string
    {
        return $this->annotation;
    }

    public function setAnnotation(?string $annotation): static
    {
        $this->annotation = $annotation;

        return $this;
    }

    public function getIdSubResources(): ?SubResources
    {
        return $this->id_sub_resources;
    }

    public function setIdSubResources(?SubResources $id_sub_resources): static
    {
        $this->id_sub_resources = $id_sub_resources;

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
            $notification->setIdAssignments($this);
        }

        return $this;
    }

    public function removeNotification(Notifications $notification): static
    {
        if ($this->notifications->removeElement($notification)) {
            if ($notification->getIdAssignments() === $this) {
                $notification->setIdAssignments(null);
            }
        }

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
}
