<?php

namespace App\Tests\Entity;

use App\Entity\Assignments;
use App\Entity\CourseTeacher;
use App\Entity\Notifications;
use App\Entity\Resources;
use App\Entity\SubResources;
use App\Entity\Users;
use Doctrine\Common\Collections\Collection;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class SubResourcesTest extends KernelTestCase
{
    private SubResources $subResource;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subResource = new SubResources();
    }

    public function testSubResourceCreation(): void
    {
        $this->assertNull($this->subResource->getId());
        $this->assertNull($this->subResource->getName());
        $this->assertNull($this->subResource->getIdResources());
        $this->assertNull($this->subResource->getIdUsers());
    }

    public function testFluentInterfaces(): void
    {
        $subResource = $this->subResource
            ->setName('Test SubResource')
            ->setId(1);

        $this->assertInstanceOf(SubResources::class, $subResource);
        $this->assertEquals('Test SubResource', $subResource->getName());
        $this->assertEquals(1, $subResource->getId());
    }

    public function testNullableFields(): void
    {
        $this->assertNull($this->subResource->getName());

        $this->subResource->setName(null);

        $this->assertNull($this->subResource->getName());
    }

    public function testCollections(): void
    {
        $this->assertInstanceOf(Collection::class, $this->subResource->getAssignments());
        $this->assertInstanceOf(Collection::class, $this->subResource->getNotifications());

        $this->assertCount(0, $this->subResource->getAssignments());
        $this->assertCount(0, $this->subResource->getNotifications());
    }

    public function testResourceRelation(): void
    {
        $resource = new Resources();

        $this->subResource->setIdResources($resource);
        $this->assertSame($resource, $this->subResource->getIdResources());

        $this->subResource->setIdResources(null);
        $this->assertNull($this->subResource->getIdResources());
    }

    public function testUserRelation(): void
    {
        $user = new Users();

        $this->subResource->setIdUsers($user);
        $this->assertSame($user, $this->subResource->getIdUsers());

        $this->subResource->setIdUsers(null);
        $this->assertNull($this->subResource->getIdUsers());
    }

    public function testAssignmentRelation(): void
    {
        $assignment = new Assignments();

        $this->subResource->addAssignment($assignment);
        $this->assertCount(1, $this->subResource->getAssignments());
        $this->assertTrue($this->subResource->getAssignments()->contains($assignment));

        $this->subResource->removeAssignment($assignment);
        $this->assertCount(0, $this->subResource->getAssignments());
        $this->assertFalse($this->subResource->getAssignments()->contains($assignment));
    }

    public function testNotificationRelation(): void
    {
        $notification = new Notifications();

        $this->subResource->addNotification($notification);
        $this->assertCount(1, $this->subResource->getNotifications());
        $this->assertTrue($this->subResource->getNotifications()->contains($notification));

        $this->subResource->removeNotification($notification);
        $this->assertCount(0, $this->subResource->getNotifications());
        $this->assertFalse($this->subResource->getNotifications()->contains($notification));
    }

    public function testCourseTeacherRelation(): void
    {
        $this->assertInstanceOf(Collection::class, $this->subResource->getCourseTeachers());
        $this->assertCount(0, $this->subResource->getCourseTeachers());

        $courseTeacher = new CourseTeacher();

        $this->subResource->addCourseTeacher($courseTeacher);
        $this->assertCount(1, $this->subResource->getCourseTeachers());
        $this->assertTrue($this->subResource->getCourseTeachers()->contains($courseTeacher));
        $this->assertSame($this->subResource, $courseTeacher->getIdSubResource());

        $this->subResource->removeCourseTeacher($courseTeacher);
        $this->assertCount(0, $this->subResource->getCourseTeachers());
        $this->assertFalse($this->subResource->getCourseTeachers()->contains($courseTeacher));
        $this->assertNull($courseTeacher->getIdSubResource());
    }
}
