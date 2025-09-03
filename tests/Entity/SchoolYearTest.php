<?php

namespace App\Tests\Entity;

use App\Entity\SchoolYear;
use App\Entity\Formation;
use App\Entity\CourseTypes;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Doctrine\Common\Collections\Collection;

class SchoolYearTest extends KernelTestCase
{
    private SchoolYear $schoolYear;

    protected function setUp(): void
    {
        parent::setUp();
        $this->schoolYear = new SchoolYear();
    }

    public function testSchoolYearCreation(): void
    {
        $this->assertNull($this->schoolYear->getId());
        $this->assertNull($this->schoolYear->getLabel());
        $this->assertInstanceOf(Collection::class, $this->schoolYear->getFormations());
        $this->assertInstanceOf(Collection::class, $this->schoolYear->getCourseTypes());
    }

    public function testFluentInterfaces(): void
    {
        $schoolYear = $this->schoolYear
            ->setId(1)
            ->setLabel('2023-2024');

        $this->assertInstanceOf(SchoolYear::class, $schoolYear);
        $this->assertEquals(1, $schoolYear->getId());
        $this->assertEquals('2023-2024', $schoolYear->getLabel());
    }

    public function testNullableFields(): void
    {
        $this->assertNull($this->schoolYear->getLabel());

        $this->schoolYear->setLabel(null);
        $this->assertNull($this->schoolYear->getLabel());
    }

    public function testCollections(): void
    {
        $this->assertInstanceOf(Collection::class, $this->schoolYear->getFormations());
        $this->assertInstanceOf(Collection::class, $this->schoolYear->getCourseTypes());

        $this->assertCount(0, $this->schoolYear->getFormations());
        $this->assertCount(0, $this->schoolYear->getCourseTypes());
    }

    public function testFormationRelation(): void
    {
        $formation = new Formation();

        $this->schoolYear->addFormation($formation);
        $this->assertCount(1, $this->schoolYear->getFormations());
        $this->assertTrue($this->schoolYear->getFormations()->contains($formation));
        $this->assertEquals($this->schoolYear, $formation->getIdSchoolYear());

        $this->schoolYear->removeFormation($formation);
        $this->assertCount(0, $this->schoolYear->getFormations());
        $this->assertFalse($this->schoolYear->getFormations()->contains($formation));
        $this->assertNull($formation->getIdSchoolYear());
    }

    public function testCourseTypeRelation(): void
    {
        $courseType = new CourseTypes();

        $this->schoolYear->addCourseType($courseType);
        $this->assertCount(1, $this->schoolYear->getCourseTypes());
        $this->assertTrue($this->schoolYear->getCourseTypes()->contains($courseType));
        $this->assertEquals($this->schoolYear, $courseType->getIdSchoolYear());

        $this->schoolYear->removeCourseType($courseType);
        $this->assertCount(0, $this->schoolYear->getCourseTypes());
        $this->assertFalse($this->schoolYear->getCourseTypes()->contains($courseType));
        $this->assertNull($courseType->getIdSchoolYear());
    }
}