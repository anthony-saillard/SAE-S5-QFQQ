<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\ResourcesRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ResourcesRepository::class)]
class Resources
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $identifier = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\ManyToOne(inversedBy: 'resources')]
    private ?Semesters $id_semesters = null;

    /**
     * @var Collection<int, Annotations>
     */
    #[ORM\OneToMany(mappedBy: 'id_resources', targetEntity: Annotations::class)]
    private Collection $annotations;

    /**
     * @var Collection<int, SubResources>
     */
    #[ORM\OneToMany(mappedBy: 'id_resources', targetEntity: SubResources::class)]
    private Collection $subResources;

    #[ORM\ManyToOne(inversedBy: 'resources')]
    private ?Users $id_users = null;

    /**
     * @var Collection<int, Notifications>
     */
    #[ORM\OneToMany(mappedBy: 'id_ressources', targetEntity: Notifications::class)]
    private Collection $notifications;

    #[ORM\Column(nullable: true)]
    private ?int $total_hours = null;

    public function __construct()
    {
        $this->annotations = new ArrayCollection();
        $this->subResources = new ArrayCollection();
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

    public function getIdentifier(): ?string
    {
        return $this->identifier;
    }

    public function setIdentifier(?string $identifier): static
    {
        $this->identifier = $identifier;

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

    public function getIdSemesters(): ?Semesters
    {
        return $this->id_semesters;
    }

    public function setIdSemesters(?Semesters $id_semesters): static
    {
        $this->id_semesters = $id_semesters;

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
            $annotation->setIdResources($this);
        }

        return $this;
    }

    public function removeAnnotation(Annotations $annotation): static
    {
        if ($this->annotations->removeElement($annotation)) {
            if ($annotation->getIdResources() === $this) {
                $annotation->setIdResources(null);
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
            $subResource->setIdResources($this);
        }

        return $this;
    }

    public function removeSubResource(SubResources $subResource): static
    {
        if ($this->subResources->removeElement($subResource)) {
            if ($subResource->getIdResources() === $this) {
                $subResource->setIdResources(null);
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
            $notification->setIdRessources($this);
        }

        return $this;
    }

    public function removeNotification(Notifications $notification): static
    {
        if ($this->notifications->removeElement($notification)) {
            if ($notification->getIdRessources() === $this) {
                $notification->setIdRessources(null);
            }
        }

        return $this;
    }

    public function getTotalHours(): ?int
    {
        return $this->total_hours;
    }

    public function setTotalHours(?int $total_hours): static
    {
        $this->total_hours = $total_hours;

        return $this;
    }
}
