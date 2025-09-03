<?php

namespace App\Tests\Entity;

use App\Entity\Assignments;
use App\Entity\CourseTeacher;
use App\Entity\CourseTypes;
use App\Entity\Formation;
use App\Entity\Groups;
use Doctrine\Common\Collections\Collection;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class GroupsTest extends KernelTestCase
{
    private Groups $group;

    protected function setUp(): void
    {
        parent::setUp();
        $this->group = new Groups();
    }

    public function testGroupCreation(): void
    {
        $this->assertNull($this->group->getId());
        $this->assertNull($this->group->getName());
        $this->assertNull($this->group->getDescription());
        $this->assertNull($this->group->getOrderNumber());
    }

    public function testFluentInterfaces(): void
    {
        $group = $this->group
            ->setId(1)
            ->setName('Test Group')
            ->setDescription('Test Description')
            ->setOrderNumber(1);

        $this->assertInstanceOf(Groups::class, $group);
        $this->assertEquals('Test Group', $group->getName());
        $this->assertEquals('Test Description', $group->getDescription());
        $this->assertEquals(1, $group->getOrderNumber());
    }

    public function testRelations(): void
    {
        $parentGroup = new Groups();
        $this->group->setIdGroups($parentGroup);
        $this->assertEquals($parentGroup, $this->group->getIdGroups());

        $courseType = new CourseTypes();
        $this->group->setIdCourseTypes($courseType);
        $this->assertEquals($courseType, $this->group->getIdCourseTypes());

        $formation = new Formation();
        $this->group->setIdFormation($formation);
        $this->assertEquals($formation, $this->group->getIdFormation());
    }

    public function testCourseTeachersCollection(): void
    {
        $this->assertInstanceOf(Collection::class, $this->group->getCourseTeachers());
        $this->assertCount(0, $this->group->getCourseTeachers());

        $courseTeacher = new CourseTeacher();
        $this->group->addCourseTeacher($courseTeacher);

        $this->assertCount(1, $this->group->getCourseTeachers());
        $this->assertTrue($this->group->getCourseTeachers()->contains($courseTeacher));
        $this->assertEquals($this->group, $courseTeacher->getIdGroups());

        $this->group->removeCourseTeacher($courseTeacher);
        $this->assertCount(0, $this->group->getCourseTeachers());
        $this->assertNull($courseTeacher->getIdGroups());
    }
}