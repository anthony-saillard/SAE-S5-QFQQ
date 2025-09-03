<?php

namespace App\Tests\Entity;

use App\Entity\CourseTeacher;
use App\Entity\Users;
use App\Entity\Resources;
use App\Entity\SubResources;
use App\Entity\Assignments;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class UsersTest extends KernelTestCase
{
    private Users $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = new Users();
    }

    public function testUserCreation(): void
    {
        $this->assertNull($this->user->getId());
        $this->assertNull($this->user->getLogin());
        $this->assertNull($this->user->getPassword());
        $this->assertEquals('ROLE_USER', $this->user->getRole());
    }

    public function testUserIdentifier(): void
    {
        $this->assertEquals('', $this->user->getUserIdentifier());

        $this->user->setLogin('testuser');
        $this->assertEquals('testuser', $this->user->getUserIdentifier());
    }

    public function testGetRoles(): void
    {
        $this->assertEquals(['ROLE_USER'], $this->user->getRoles());

        $this->user->setRole('ROLE_ADMIN');
        $this->assertEquals(['ROLE_ADMIN'], $this->user->getRoles());
    }

    public function testFluentInterfaces(): void
    {
        $user = $this->user
            ->setLogin('testuser')
            ->setPassword('password123')
            ->setEmail('test@example.com')
            ->setFirstName('John')
            ->setLastName('Doe')
            ->setPhone('0123456789')
            ->setRole('ROLE_USER');

        $this->assertInstanceOf(Users::class, $user);
        $this->assertEquals('testuser', $user->getLogin());
        $this->assertEquals('password123', $user->getPassword());
        $this->assertEquals('test@example.com', $user->getEmail());
    }

    public function testNullableFields(): void
    {
        $this->assertNull($this->user->getEmail());
        $this->assertNull($this->user->getPhone());
        $this->assertNull($this->user->getFirstName());
        $this->assertNull($this->user->getLastName());

        $this->user->setEmail(null);
        $this->user->setPhone(null);
        $this->user->setFirstName(null);
        $this->user->setLastName(null);

        $this->assertNull($this->user->getEmail());
        $this->assertNull($this->user->getPhone());
        $this->assertNull($this->user->getFirstName());
        $this->assertNull($this->user->getLastName());
    }

    public function testCollections(): void
    {
        $this->assertInstanceOf(Collection::class, $this->user->getAssignments());
        $this->assertInstanceOf(Collection::class, $this->user->getSubResources());
        $this->assertInstanceOf(Collection::class, $this->user->getResources());

        $this->assertCount(0, $this->user->getAssignments());
        $this->assertCount(0, $this->user->getSubResources());
        $this->assertCount(0, $this->user->getResources());
    }

    public function testResourceRelation(): void
    {
        // Test de la relation avec Resources
        $resource = new Resources();

        $this->user->addResource($resource);
        $this->assertCount(1, $this->user->getResources());
        $this->assertTrue($this->user->getResources()->contains($resource));

        $this->user->removeResource($resource);
        $this->assertCount(0, $this->user->getResources());
        $this->assertFalse($this->user->getResources()->contains($resource));
    }

    public function testSubResourceRelation(): void
    {
        $subResource = new SubResources();

        $this->user->addSubResource($subResource);
        $this->assertCount(1, $this->user->getSubResources());
        $this->assertTrue($this->user->getSubResources()->contains($subResource));

        $this->user->removeSubResource($subResource);
        $this->assertCount(0, $this->user->getSubResources());
        $this->assertFalse($this->user->getSubResources()->contains($subResource));
    }

    public function testAssignmentRelation(): void
    {
        $assignment = new Assignments();

        $this->user->addAssignment($assignment);
        $this->assertCount(1, $this->user->getAssignments());
        $this->assertTrue($this->user->getAssignments()->contains($assignment));

        $this->user->removeAssignment($assignment);
        $this->assertCount(0, $this->user->getAssignments());
        $this->assertFalse($this->user->getAssignments()->contains($assignment));
    }

    public function testUserSecurity(): void
    {
        $this->assertInstanceOf(UserInterface::class, $this->user);
        $this->assertInstanceOf(PasswordAuthenticatedUserInterface::class, $this->user);

        $this->user->setPassword('password123');
        $this->user->eraseCredentials();
        $this->assertEquals('password123', $this->user->getPassword());
    }

    public function testCourseTeacherRelation(): void
    {
        $this->assertInstanceOf(Collection::class, $this->user->getCourseTeachers());
        $this->assertCount(0, $this->user->getCourseTeachers());

        $courseTeacher = new CourseTeacher();

        $this->user->addCourseTeacher($courseTeacher);
        $this->assertCount(1, $this->user->getCourseTeachers());
        $this->assertTrue($this->user->getCourseTeachers()->contains($courseTeacher));
        $this->assertSame($this->user, $courseTeacher->getIdUser());

        $this->user->removeCourseTeacher($courseTeacher);
        $this->assertCount(0, $this->user->getCourseTeachers());
        $this->assertFalse($this->user->getCourseTeachers()->contains($courseTeacher));
        $this->assertNull($courseTeacher->getIdUser());
    }
}