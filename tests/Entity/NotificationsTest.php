<?php

namespace App\Tests\Entity;

use App\Entity\Annotations;
use App\Entity\Assignments;
use App\Entity\Notifications;
use App\Entity\Resources;
use App\Entity\SubResources;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class NotificationsTest extends KernelTestCase
{
    private Notifications $notification;

    protected function setUp(): void
    {
        parent::setUp();
        $this->notification = new Notifications();
    }

    public function testNotificationCreation(): void
    {
        $this->assertNull($this->notification->getId());
        $this->assertNull($this->notification->getModificationDate());
        $this->assertNull($this->notification->getStatus());
    }

    public function testFluentInterfaces(): void
    {
        $date = new \DateTime();
        $notification = $this->notification
            ->setId(1)
            ->setModificationDate($date)
            ->setStatus(1);

        $this->assertInstanceOf(Notifications::class, $notification);
        $this->assertEquals($date, $notification->getModificationDate());
        $this->assertEquals(1, $notification->getStatus());
    }

    public function testRelations(): void
    {
        $annotation = new Annotations();
        $this->notification->setIdAnnotations($annotation);
        $this->assertEquals($annotation, $this->notification->getIdAnnotations());

        $resource = new Resources();
        $this->notification->setIdRessources($resource);
        $this->assertEquals($resource, $this->notification->getIdRessources());

        $subResource = new SubResources();
        $this->notification->setIdSubResources($subResource);
        $this->assertEquals($subResource, $this->notification->getIdSubResources());

        $assignment = new Assignments();
        $this->notification->setIdAssignments($assignment);
        $this->assertEquals($assignment, $this->notification->getIdAssignments());
    }
}