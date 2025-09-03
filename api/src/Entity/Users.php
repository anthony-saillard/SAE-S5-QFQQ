<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\UsersRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UsersRepository::class)]
class Users implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $login = null;

    #[ORM\Column(length: 255)]
    private ?string $password = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $last_name = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $first_name = null;

    #[ORM\Column(type: Types::STRING)]
    private string $role = 'ROLE_USER';

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $phone = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $email = null;

    /**
     * @var Collection<int, Assignments>
     */
    #[ORM\OneToMany(mappedBy: 'id_users', targetEntity: Assignments::class)]
    private Collection $assignments;

    /**
     * @var Collection<int, SubResources>
     */
    #[ORM\OneToMany(mappedBy: 'id_users', targetEntity: SubResources::class)]
    private Collection $subResources;

    /**
     * @var Collection<int, Resources>
     */
    #[ORM\OneToMany(mappedBy: 'id_users', targetEntity: Resources::class)]
    private Collection $resources;

    /**
     * @var Collection<int, CourseTeacher>
     */
    #[ORM\OneToMany(mappedBy: 'id_user', targetEntity: CourseTeacher::class)]
    private Collection $courseTeachers;

    #[ORM\Column(nullable: true)]
    private ?bool $disable = null;

    /**
     * @var Collection<int, Annotations>
     */
    #[ORM\OneToMany(mappedBy: 'id_user', targetEntity: Annotations::class)]
    private Collection $annotations;

    public function __construct()
    {
        $this->assignments = new ArrayCollection();
        $this->subResources = new ArrayCollection();
        $this->resources = new ArrayCollection();
        $this->courseTeachers = new ArrayCollection();
        $this->annotations = new ArrayCollection();
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

    public function getLogin(): ?string
    {
        return $this->login;
    }

    public function setLogin(string $login): static
    {
        $this->login = $login;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->last_name;
    }

    public function setLastName(?string $last_name): static
    {
        $this->last_name = $last_name;

        return $this;
    }

    public function getFirstName(): ?string
    {
        return $this->first_name;
    }

    public function setFirstName(?string $first_name): static
    {
        $this->first_name = $first_name;

        return $this;
    }

    public function getRole(): string
    {
        return $this->role;
    }

    public function setRole(string $role): self
    {
        $this->role = $role;
        return $this;
    }

    public function getRoles(): array
    {
        return [$this->role];
    }

    public function getUserIdentifier(): string
    {
        return $this->login ?? '';
    }

    public function eraseCredentials(): void
    {
        // Delete sensible data if necessary
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): static
    {
        $this->phone = $phone;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): static
    {
        $this->email = $email;

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
            $assignment->setIdUsers($this);
        }

        return $this;
    }

    public function removeAssignment(Assignments $assignment): static
    {
        if ($this->assignments->removeElement($assignment)) {
            if ($assignment->getIdUsers() === $this) {
                $assignment->setIdUsers(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, SubResources>
     */
    public function getSubResources(): Collection
    {
        return $this->subResources;
    }

    public function addSubResource(SubResources $subResource): static
    {
        if (!$this->subResources->contains($subResource)) {
            $this->subResources->add($subResource);
            $subResource->setIdUsers($this);
        }

        return $this;
    }

    public function removeSubResource(SubResources $subResource): static
    {
        if ($this->subResources->removeElement($subResource)) {
            if ($subResource->getIdUsers() === $this) {
                $subResource->setIdUsers(null);
            }
        }

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
            $resource->setIdUsers($this);
        }

        return $this;
    }

    public function removeResource(Resources $resource): static
    {
        if ($this->resources->removeElement($resource)) {
            if ($resource->getIdUsers() === $this) {
                $resource->setIdUsers(null);
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
            $courseTeacher->setIdUser($this);
        }

        return $this;
    }

    public function removeCourseTeacher(CourseTeacher $courseTeacher): static
    {
        if ($this->courseTeachers->removeElement($courseTeacher)) {
            if ($courseTeacher->getIdUser() === $this) {
                $courseTeacher->setIdUser(null);
            }
        }

        return $this;
    }

    public function isDisable(): ?bool
    {
        return $this->disable;
    }

    public function setDisable(bool $disable): static
    {
        $this->disable = $disable;

        return $this;
    }

    /**
     * @return Collection<int, Annotations>
     */
    public function getAnnotations(): Collection
    {
        return $this->annotations;
    }

    public function addAnnotation(Annotations $annotation): static
    {
        if (!$this->annotations->contains($annotation)) {
            $this->annotations->add($annotation);
            $annotation->setIdUser($this);
        }

        return $this;
    }

    public function removeAnnotation(Annotations $annotation): static
    {
        if ($this->annotations->removeElement($annotation)) {
            if ($annotation->getIdUser() === $this) {
                $annotation->setIdUser(null);
            }
        }

        return $this;
    }
}
