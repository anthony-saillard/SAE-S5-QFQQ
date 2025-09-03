<?php

namespace App\Tests\Entity;

use App\Entity\Annotations;
use App\Entity\Notifications;
use App\Entity\Resources;
use App\Entity\Semesters;
use App\Entity\SubResources;
use App\Entity\Users;
use Doctrine\Common\Collections\Collection;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ResourcesTest extends KernelTestCase
{
    private Resources $resource;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resource = new Resources();
    }

    public function testResourceCreation(): void
    {
        $this->assertNull($this->resource->getId());
        $this->assertNull($this->resource->getIdentifier());
        $this->assertNull($this->resource->getName());
        $this->assertNull($this->resource->getDescription());
        $this->assertNull($this->resource->getIdSemesters());
        $this->assertNull($this->resource->getIdUsers());
        $this->assertNull($this->resource->getTotalHours());
    }

    public function testFluentInterfaces(): void
    {
        $resource = $this->resource
            ->setId(1)
            ->setIdentifier('TEST-001')
            ->setName('Test Resource')
            ->setDescription('Test Description')
            ->setTotalHours(10);

        $this->assertInstanceOf(Resources::class, $resource);
        $this->assertEquals(1, $resource->getId());
        $this->assertEquals('TEST-001', $resource->getIdentifier());
        $this->assertEquals('Test Resource', $resource->getName());
        $this->assertEquals('Test Description', $resource->getDescription());
        $this->assertEquals(10, $resource->getTotalHours());
    }

    public function testCollections(): void
    {
        $this->assertInstanceOf(Collection::class, $this->resource->getAnnotations());
        $this->assertInstanceOf(Collection::class, $this->resource->getSubResources());
        $this->assertInstanceOf(Collection::class, $this->resource->getNotifications());

        $this->assertCount(0, $this->resource->getAnnotations());
        $this->assertCount(0, $this->resource->getSubResources());
        $this->assertCount(0, $this->resource->getNotifications());
    }

    public function testRelations(): void
    {
        $semester = new Semesters();
        $this->resource->setIdSemesters($semester);
        $this->assertSame($semester, $this->resource->getIdSemesters());

        $user = new Users();
        $this->resource->setIdUsers($user);
        $this->assertSame($user, $this->resource->getIdUsers());
    }

    public function testAnnotationsRelation(): void
    {
        $annotation = new Annotations();

        $this->resource->addAnnotation($annotation);
        $this->assertCount(1, $this->resource->getAnnotations());
        $this->assertTrue($this->resource->getAnnotations()->contains($annotation));

        $this->resource->removeAnnotation($annotation);
        $this->assertCount(0, $this->resource->getAnnotations());
        $this->assertFalse($this->resource->getAnnotations()->contains($annotation));
    }

    public function testSubResourcesRelation(): void
    {
        $subResource = new SubResources();

        $this->resource->addSubResource($subResource);
        $this->assertCount(1, $this->resource->getSubResources());
        $this->assertTrue($this->resource->getSubResources()->contains($subResource));

        $this->resource->removeSubResource($subResource);
        $this->assertCount(0, $this->resource->getSubResources());
        $this->assertFalse($this->resource->getSubResources()->contains($subResource));
    }

    public function testNotificationsRelation(): void
    {
        $notification = new Notifications();

        $this->resource->addNotification($notification);
        $this->assertCount(1, $this->resource->getNotifications());
        $this->assertTrue($this->resource->getNotifications()->contains($notification));

        $this->resource->removeNotification($notification);
        $this->assertCount(0, $this->resource->getNotifications());
        $this->assertFalse($this->resource->getNotifications()->contains($notification));
    }
}
