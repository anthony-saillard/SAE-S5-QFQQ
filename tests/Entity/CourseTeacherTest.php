<?php

namespace App\Tests\Entity;

use App\Entity\CourseTeacher;
use App\Entity\Groups;
use App\Entity\SubResources;
use App\Entity\Users;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class CourseTeacherTest extends KernelTestCase
{
    private CourseTeacher $courseTeacher;

    protected function setUp(): void
    {
        parent::setUp();
        $this->courseTeacher = new CourseTeacher();
    }

    public function testCourseTeacherCreation(): void
    {
        $this->assertNull($this->courseTeacher->getId());
        $this->assertNull($this->courseTeacher->getIdSubResource());
        $this->assertNull($this->courseTeacher->getIdUser());
        $this->assertNull($this->courseTeacher->getIdGroups());
    }

    public function testFluentInterfaces(): void
    {
        $courseTeacher = $this->courseTeacher
            ->setIdSubResource(new SubResources())
            ->setIdUser(new Users())
            ->setIdGroups(new Groups());

        $this->assertInstanceOf(CourseTeacher::class, $courseTeacher);
        $this->assertInstanceOf(SubResources::class, $courseTeacher->getIdSubResource());
        $this->assertInstanceOf(Users::class, $courseTeacher->getIdUser());
        $this->assertInstanceOf(Groups::class, $courseTeacher->getIdGroups());
    }

    public function testSubResourceRelation(): void
    {
        $this->assertNull($this->courseTeacher->getIdSubResource());

        $subResource = new SubResources();
        $this->courseTeacher->setIdSubResource($subResource);
        $this->assertSame($subResource, $this->courseTeacher->getIdSubResource());

        $this->courseTeacher->setIdSubResource(null);
        $this->assertNull($this->courseTeacher->getIdSubResource());
    }

    public function testUserRelation(): void
    {
        $this->assertNull($this->courseTeacher->getIdUser());

        $user = new Users();
        $this->courseTeacher->setIdUser($user);
        $this->assertSame($user, $this->courseTeacher->getIdUser());

        $this->courseTeacher->setIdUser(null);
        $this->assertNull($this->courseTeacher->getIdUser());
    }

    public function testGroupsRelation(): void
    {
        $this->assertNull($this->courseTeacher->getIdGroups());

        $group = new Groups();
        $this->courseTeacher->setIdGroups($group);
        $this->assertSame($group, $this->courseTeacher->getIdGroups());

        $this->courseTeacher->setIdGroups(null);
        $this->assertNull($this->courseTeacher->getIdGroups());
    }

    public function testBidirectionalRelations(): void
    {
        $subResource = new SubResources();
        $this->courseTeacher->setIdSubResource($subResource);
        $subResource->addCourseTeacher($this->courseTeacher);

        $this->assertTrue($subResource->getCourseTeachers()->contains($this->courseTeacher));
        $this->assertSame($subResource, $this->courseTeacher->getIdSubResource());

        $user = new Users();
        $this->courseTeacher->setIdUser($user);
        $user->addCourseTeacher($this->courseTeacher);

        $this->assertTrue($user->getCourseTeachers()->contains($this->courseTeacher));
        $this->assertSame($user, $this->courseTeacher->getIdUser());

        $group = new Groups();
        $this->courseTeacher->setIdGroups($group);
        $group->addCourseTeacher($this->courseTeacher);

        $this->assertTrue($group->getCourseTeachers()->contains($this->courseTeacher));
        $this->assertSame($group, $this->courseTeacher->getIdGroups());
    }
}