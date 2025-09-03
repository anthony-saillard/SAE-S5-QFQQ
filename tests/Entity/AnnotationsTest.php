<?php

namespace App\Tests\Entity;

use App\Entity\Annotations;
use App\Entity\Notifications;
use App\Entity\Resources;
use Doctrine\Common\Collections\Collection;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class AnnotationsTest extends KernelTestCase
{
    private Annotations $annotation;

    protected function setUp(): void
    {
        parent::setUp();
        $this->annotation = new Annotations();
    }

    public function testAnnotationCreation(): void
    {
        $this->assertNull($this->annotation->getId());
        $this->assertNull($this->annotation->getDescription());
        $this->assertNull($this->annotation->getIdResources());
    }

    public function testFluentInterfaces(): void
    {
        $annotation = $this->annotation
            ->setDescription('Test description')
            ->setId(1);

        $this->assertInstanceOf(Annotations::class, $annotation);
        $this->assertEquals('Test description', $annotation->getDescription());
        $this->assertEquals(1, $annotation->getId());
    }

    public function testResourceRelation(): void
    {
        $resource = new Resources();
        $this->annotation->setIdResources($resource);

        $this->assertInstanceOf(Resources::class, $this->annotation->getIdResources());
        $this->assertEquals($resource, $this->annotation->getIdResources());
    }

    public function testNotificationsCollection(): void
    {
        $this->assertInstanceOf(Collection::class, $this->annotation->getNotifications());
        $this->assertCount(0, $this->annotation->getNotifications());

        $notification = new Notifications();
        $this->annotation->addNotification($notification);

        $this->assertCount(1, $this->annotation->getNotifications());
        $this->assertTrue($this->annotation->getNotifications()->contains($notification));

        $this->annotation->removeNotification($notification);
        $this->assertCount(0, $this->annotation->getNotifications());
    }
}
