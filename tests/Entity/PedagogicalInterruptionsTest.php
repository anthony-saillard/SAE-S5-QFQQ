<?php

namespace App\Tests\Entity;

use App\Entity\PedagogicalInterruptions;
use PHPUnit\Framework\TestCase;

class PedagogicalInterruptionsTest extends TestCase
{
    private PedagogicalInterruptions $interruption;

    protected function setUp(): void
    {
        $this->interruption = new PedagogicalInterruptions();
    }

    public function testInitialState(): void
    {
        $this->assertNull($this->interruption->getId());
        $this->assertNull($this->interruption->getName());
        $this->assertNull($this->interruption->getStartDate());
        $this->assertNull($this->interruption->getEndDate());
    }

    public function testSetAndGetName(): void
    {
        $name = 'Christmas holidays';

        $this->interruption->setName($name);
        $this->assertEquals($name, $this->interruption->getName());

        $this->interruption->setName('');
        $this->assertEquals('', $this->interruption->getName());
    }

    public function testSetAndGetStartDate(): void {
        $date = new \DateTime('2024-12-20');

        $this->interruption->setStartDate($date);
        $this->assertEquals($date, $this->interruption->getStartDate());

        $this->interruption->setStartDate(null);
        $this->assertNull($this->interruption->getStartDate());
    }

    public function testSetAndGetEndDate(): void {
        $date = new \DateTime('2025-01-05');

        $this->interruption->setEndDate($date);
        $this->assertEquals($date, $this->interruption->getEndDate());

        $this->interruption->setEndDate(null);
        $this->assertNull($this->interruption->getEndDate());
    }

    public function testDateConsistency(): void {
        $startDate = new \DateTime('2024-12-20');
        $endDate = new \DateTime('2024-12-25');

        $this->interruption->setStartDate($startDate);
        $this->interruption->setEndDate($endDate);

        $this->assertTrue(
            $this->interruption->getStartDate() <= $this->interruption->getEndDate(),
            'End date should be after or equal to start date'
        );
    }

    public function testCompleteEntitySetup(): void {
        $name = 'Christmas holidays';
        $startDate = new \DateTime('2024-12-20');
        $endDate = new \DateTime('2025-01-05');

        $this->interruption
            ->setName($name)
            ->setStartDate($startDate)
            ->setEndDate($endDate);

        $this->assertEquals($name, $this->interruption->getName());
        $this->assertEquals($startDate, $this->interruption->getStartDate());
        $this->assertEquals($endDate, $this->interruption->getEndDate());
    }
}
