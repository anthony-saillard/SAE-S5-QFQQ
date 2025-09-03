<?php

namespace App\Entity;

use App\Repository\CourseTeacherRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CourseTeacherRepository::class)]
class CourseTeacher
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'courseTeachers')]
    private ?SubResources $id_sub_resource = null;

    #[ORM\ManyToOne(inversedBy: 'courseTeachers')]
    private ?Users $id_user = null;

    #[ORM\ManyToOne(inversedBy: 'courseTeachers')]
    private ?Groups $id_groups = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): CourseTeacher
    {
        $this->id = $id;

        return $this;
    }

    public function getIdSubResource(): ?SubResources
    {
        return $this->id_sub_resource;
    }

    public function setIdSubResource(?SubResources $id_sub_resource): static
    {
        $this->id_sub_resource = $id_sub_resource;

        return $this;
    }

    public function getIdUser(): ?Users
    {
        return $this->id_user;
    }

    public function setIdUser(?Users $id_user): static
    {
        $this->id_user = $id_user;

        return $this;
    }

    public function getIdGroups(): ?Groups
    {
        return $this->id_groups;
    }

    public function setIdGroups(?Groups $id_groups): static
    {
        $this->id_groups = $id_groups;

        return $this;
    }
}
