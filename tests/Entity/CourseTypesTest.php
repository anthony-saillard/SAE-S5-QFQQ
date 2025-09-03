<?php

namespace App\Tests\Entity;

use App\Entity\Assignments;
use App\Entity\CourseTypes;
use App\Entity\SchoolYear;
use App\Entity\Groups;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Doctrine\Common\Collections\Collection;

class CourseTypesTest extends KernelTestCase
{
    private CourseTypes $courseType;

    protected function setUp(): void
    {
        parent::setUp();
        $this->courseType = new CourseTypes();
    }

    public function testCourseTypeCreation(): void
    {
        $this->assertNull($this->courseType->getId());
        $this->assertNull($this->courseType->getName());
        $this->assertNull($this->courseType->getHourlyRate());
        $this->assertNull($this->courseType->getIdSchoolYear());
    }

    public function testFluentInterfaces(): void
    {
        $courseType = $this->courseType
            ->setName('Test Course')
            ->setHourlyRate(45.5)
            ->setId(1);

        $this->assertInstanceOf(CourseTypes::class, $courseType);
        $this->assertEquals('Test Course', $courseType->getName());
        $this->assertEquals(45.5, $courseType->getHourlyRate());
        $this->assertEquals(1, $courseType->getId());
    }

    public function testNullableFields(): void
    {
        $this->assertNull($this->courseType->getName());
        $this->assertNull($this->courseType->getHourlyRate());
        $this->assertNull($this->courseType->getIdSchoolYear());

        $this->courseType->setName(null);
        $this->courseType->setHourlyRate(null);
        $this->courseType->setIdSchoolYear(null);

        $this->assertNull($this->courseType->getName());
        $this->assertNull($this->courseType->getHourlyRate());
        $this->assertNull($this->courseType->getIdSchoolYear());
    }

    public function testCollections(): void
    {
        $this->assertInstanceOf(Collection::class, $this->courseType->getGroups());
        $this->assertCount(0, $this->courseType->getGroups());

        $this->assertInstanceOf(Collection::class, $this->courseType->getAssignments());
        $this->assertCount(0, $this->courseType->getAssignments());
    }

    public function testSchoolYearRelation(): void
    {
        $schoolYear = new SchoolYear();

        $this->courseType->setIdSchoolYear($schoolYear);
        $this->assertSame($schoolYear, $this->courseType->getIdSchoolYear());

        $this->courseType->setIdSchoolYear(null);
        $this->assertNull($this->courseType->getIdSchoolYear());
    }

    public function testGroupsRelation(): void
    {
        $group = new Groups();

        $this->courseType->addGroup($group);
        $this->assertCount(1, $this->courseType->getGroups());
        $this->assertTrue($this->courseType->getGroups()->contains($group));
        $this->assertSame($this->courseType, $group->getIdCourseTypes());

        $this->courseType->removeGroup($group);
        $this->assertCount(0, $this->courseType->getGroups());
        $this->assertFalse($this->courseType->getGroups()->contains($group));
        $this->assertNull($group->getIdCourseTypes());
    }

    public function testAssignmentsRelation(): void
    {
        $assignment = new Assignments();

        $this->courseType->addAssignment($assignment);
        $this->assertCount(1, $this->courseType->getAssignments());
        $this->assertTrue($this->courseType->getAssignments()->contains($assignment));
        $this->assertSame($this->courseType, $assignment->getIdCourseTypes());

        $this->courseType->removeAssignment($assignment);
        $this->assertCount(0, $this->courseType->getAssignments());
        $this->assertFalse($this->courseType->getAssignments()->contains($assignment));
        $this->assertNull($assignment->getIdCourseTypes());
    }

    public function testHourlyRateHandling(): void
    {
        $this->courseType->setHourlyRate(42.50);
        $this->assertEquals(42.50, $this->courseType->getHourlyRate());

        $this->courseType->setHourlyRate(0.0);
        $this->assertEquals(0.0, $this->courseType->getHourlyRate());

        $this->courseType->setHourlyRate(null);
        $this->assertNull($this->courseType->getHourlyRate());
    }
}