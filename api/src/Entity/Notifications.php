<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\NotificationsRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: NotificationsRepository::class)]
class Notifications
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $modification_date = null;

    #[ORM\Column(nullable: true)]
    private ?int $status = null;

    #[ORM\ManyToOne(inversedBy: 'notifications')]
    private ?Annotations $id_annotations = null;

    #[ORM\ManyToOne(inversedBy: 'notifications')]
    private ?Resources $id_ressources = null;

    #[ORM\ManyToOne(inversedBy: 'notifications')]
    private ?SubResources $id_sub_resources = null;

    #[ORM\ManyToOne(inversedBy: 'notifications')]
    private ?Assignments $id_assignments = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getModificationDate(): ?\DateTimeInterface
    {
        return $this->modification_date;
    }

    public function setModificationDate(?\DateTimeInterface $modification_date): static
    {
        $this->modification_date = $modification_date;

        return $this;
    }

    public function getStatus(): ?int
    {
        return $this->status;
    }

    public function setStatus(?int $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getIdAnnotations(): ?Annotations
    {
        return $this->id_annotations;
    }

    public function setIdAnnotations(?Annotations $id_annotations): static
    {
        $this->id_annotations = $id_annotations;

        return $this;
    }

    public function getIdRessources(): ?Resources
    {
        return $this->id_ressources;
    }

    public function setIdRessources(?Resources $id_ressources): static
    {
        $this->id_ressources = $id_ressources;

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

    public function getIdAssignments(): ?Assignments
    {
        return $this->id_assignments;
    }

    public function setIdAssignments(?Assignments $id_assignments): static
    {
        $this->id_assignments = $id_assignments;

        return $this;
    }
}
