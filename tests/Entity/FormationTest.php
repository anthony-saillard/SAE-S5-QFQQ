<?php

namespace App\Tests\Entity;

use App\Entity\Formation;
use App\Entity\Groups;
use App\Entity\SchoolYear;
use App\Entity\PedagogicalInterruptions;
use App\Entity\Semesters;
use PHPUnit\Framework\TestCase;

class FormationTest extends TestCase
{
    private Formation $formation;

    protected function setUp(): void
    {
        $this->formation = new Formation();
    }

    public function testInitialState(): void
    {
        $this->assertNull($this->formation->getId());
        $this->assertNull($this->formation->getLabel());
        $this->assertNull($this->formation->getOrderNumber());
        $this->assertNull($this->formation->getIdSchoolYear());
        $this->assertCount(0, $this->formation->getPedagogicalInterruptions());
        $this->assertCount(0, $this->formation->getSemesters());
        $this->assertCount(0, $this->formation->getGroups());
    }

    public function testSetAndGetLabel(): void
    {
        $label = 'Computer Science';

        $this->formation->setLabel($label);
        $this->assertEquals($label, $this->formation->getLabel());

        $this->formation->setLabel(null);
        $this->assertNull($this->formation->getLabel());
    }

    public function testSetAndGetOrderNumber(): void
    {
        $this->formation->setOrderNumber(3);
        $this->assertEquals(3, $this->formation->getOrderNumber());

        $this->formation->setOrderNumber(null);
        $this->assertNull($this->formation->getOrderNumber());
    }

    public function testSetAndGetSchoolYear(): void
    {
        $schoolYear = new SchoolYear();
        $this->formation->setIdSchoolYear($schoolYear);

        $this->assertSame($schoolYear, $this->formation->getIdSchoolYear());

        $this->formation->setIdSchoolYear(null);
        $this->assertNull($this->formation->getIdSchoolYear());
    }

    public function testAddAndRemovePedagogicalInterruption(): void
    {
        $interruption = new PedagogicalInterruptions();

        $this->formation->addPedagogicalInterruption($interruption);
        $this->assertCount(1, $this->formation->getPedagogicalInterruptions());
        $this->assertSame($this->formation, $interruption->getIdFormation());

        $this->formation->removePedagogicalInterruption($interruption);
        $this->assertCount(0, $this->formation->getPedagogicalInterruptions());
        $this->assertNull($interruption->getIdFormation());
    }

    public function testAddAndRemoveSemester(): void
    {
        $semester = new Semesters();

        $this->formation->addSemester($semester);
        $this->assertCount(1, $this->formation->getSemesters());
        $this->assertSame($this->formation, $semester->getIdFormation());

        $this->formation->removeSemester($semester);
        $this->assertCount(0, $this->formation->getSemesters());
        $this->assertNull($semester->getIdFormation());
    }

    public function testAddAndRemoveGroup(): void
    {
        $group = new Groups();

        $this->formation->addGroup($group);
        $this->assertCount(1, $this->formation->getGroups());
        $this->assertSame($this->formation, $group->getIdFormation());

        $this->formation->removeGroup($group);
        $this->assertCount(0, $this->formation->getGroups());
        $this->assertNull($group->getIdFormation());
    }
}
