<?php

namespace App\Tests\Entity;

use App\Entity\Assignments;
use App\Entity\CourseTypes;
use App\Entity\Notifications;
use App\Entity\SubResources;
use App\Entity\Users;
use Doctrine\Common\Collections\Collection;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class AssignmentsTest extends KernelTestCase
{
    private Assignments $assignment;

    protected function setUp(): void
    {
        parent::setUp();
        $this->assignment = new Assignments();
    }

    public function testAssignmentCreation(): void
    {
        $this->assertNull($this->assignment->getId());
        $this->assertNull($this->assignment->getAllocatedHours());
        $this->assertNull($this->assignment->getAssignmentDate());
        $this->assertNull($this->assignment->getAnnotation());
    }

    public function testFluentInterfaces(): void
    {
        $assignment = $this->assignment
            ->setId(1)
            ->setAllocatedHours(40)
            ->setAssignmentDate(new \DateTime('@1234567890'))
            ->setAnnotation('Test annotation');

        $this->assertInstanceOf(Assignments::class, $assignment);
        $this->assertEquals(40, $assignment->getAllocatedHours());
        $this->assertEquals(new \DateTime('@1234567890'), $assignment->getAssignmentDate());
        $this->assertEquals('Test annotation', $assignment->getAnnotation());
    }

    public function testRelations(): void
    {
        $subResource = new SubResources();
        $this->assignment->setIdSubResources($subResource);
        $this->assertEquals($subResource, $this->assignment->getIdSubResources());

        $user = new Users();
        $this->assignment->setIdUsers($user);
        $this->assertEquals($user, $this->assignment->getIdUsers());

        $courseType = new courseTypes();
        $this->assignment->setIdCourseTypes($courseType);
        $this->assertEquals($courseType, $this->assignment->getIdCourseTypes());
    }

    public function testNotificationsCollection(): void
    {
        $this->assertInstanceOf(Collection::class, $this->assignment->getNotifications());
        $this->assertCount(0, $this->assignment->getNotifications());

        $notification = new Notifications();
        $this->assignment->addNotification($notification);

        $this->assertCount(1, $this->assignment->getNotifications());
        $this->assertTrue($this->assignment->getNotifications()->contains($notification));

        $this->assignment->removeNotification($notification);
        $this->assertCount(0, $this->assignment->getNotifications());
    }
}
