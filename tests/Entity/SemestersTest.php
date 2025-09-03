<?php

namespace App\Tests\Entity;

use App\Entity\Formation;
use App\Entity\Resources;
use App\Entity\Semesters;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Doctrine\Common\Collections\Collection;

class SemestersTest extends KernelTestCase
{
    private Semesters $semester;

    protected function setUp(): void
    {
        parent::setUp();
        $this->semester = new Semesters();
    }

    public function testSemesterCreation(): void
    {
        $this->assertNull($this->semester->getId());
        $this->assertNull($this->semester->getName());
        $this->assertNull($this->semester->getStartDate());
        $this->assertNull($this->semester->getEndDate());
        $this->assertNull($this->semester->getOrderNumber());
        $this->assertNull($this->semester->getIdFormation());
        $this->assertInstanceOf(Collection::class, $this->semester->getResources());
    }

    public function testFluentInterfaces(): void
    {
        $date = new \DateTime();
        $formation = new Formation();

        $semester = $this->semester
            ->setId(1)
            ->setName('Semester 1')
            ->setStartDate($date)
            ->setEndDate($date)
            ->setOrderNumber(1)
            ->setIdFormation($formation);

        $this->assertInstanceOf(Semesters::class, $semester);
        $this->assertEquals(1, $semester->getId());
        $this->assertEquals('Semester 1', $semester->getName());
        $this->assertEquals($date, $semester->getStartDate());
        $this->assertEquals($date, $semester->getEndDate());
        $this->assertEquals(1, $semester->getOrderNumber());
        $this->assertEquals($formation, $semester->getIdFormation());
    }

    public function testNullableFields(): void
    {
        $this->assertNull($this->semester->getName());
        $this->assertNull($this->semester->getStartDate());
        $this->assertNull($this->semester->getEndDate());
        $this->assertNull($this->semester->getOrderNumber());
        $this->assertNull($this->semester->getIdFormation());

        $this->semester->setName(null);
        $this->semester->setStartDate(null);
        $this->semester->setEndDate(null);
        $this->semester->setOrderNumber(null);
        $this->semester->setIdFormation(null);

        $this->assertNull($this->semester->getName());
        $this->assertNull($this->semester->getStartDate());
        $this->assertNull($this->semester->getEndDate());
        $this->assertNull($this->semester->getOrderNumber());
        $this->assertNull($this->semester->getIdFormation());
    }

    public function testCollections(): void
    {
        $this->assertInstanceOf(Collection::class, $this->semester->getResources());
        $this->assertCount(0, $this->semester->getResources());
    }

    public function testResourceRelation(): void
    {
        $resource = new Resources();

        $this->semester->addResource($resource);
        $this->assertCount(1, $this->semester->getResources());
        $this->assertTrue($this->semester->getResources()->contains($resource));
        $this->assertEquals($this->semester, $resource->getIdSemesters());

        $this->semester->removeResource($resource);
        $this->assertCount(0, $this->semester->getResources());
        $this->assertFalse($this->semester->getResources()->contains($resource));
        $this->assertNull($resource->getIdSemesters());
    }
}