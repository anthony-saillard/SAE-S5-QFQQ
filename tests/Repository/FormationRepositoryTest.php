<?php

namespace App\Tests\Repository;

use App\Entity\Formation;
use App\Repository\FormationRepository;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bridge\Doctrine\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class FormationRepositoryTest extends KernelTestCase
{
    private ?EntityManagerInterface $entityManager;
    private FormationRepository $repository;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();

        /** @var ManagerRegistry $doctrine */
        $doctrine = $kernel->getContainer()->get('doctrine');

        /** @var EntityManagerInterface $entityManager */
        $entityManager = $doctrine->getManager();
        $this->entityManager = $entityManager;

        $this->entityManager->beginTransaction();

        /** @var FormationRepository repository */
        $repository = $this->entityManager->getRepository(Formation::class);
        $this->repository = $repository;
    }

    public function testConstruct(): void
    {
        $this->assertInstanceOf(FormationRepository::class, $this->repository);
        $this->assertInstanceOf(ServiceEntityRepository::class, $this->repository);
    }

    public function testGetHoursByGroups(): void
    {
        $mockRepository = $this->getMockBuilder(FormationRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['createQueryBuilder'])
            ->getMock();

        $queryBuilder = $this->createMock(QueryBuilder::class);

        $query = $this->createMock(AbstractQuery::class);

        $expectedResult = [
            [
                'course_type_id' => 1,
                'course_type_name' => 'Lecture',
                'sub_resource_id' => 101,
                'sub_resource_name' => 'Mathematics',
                'sub_resource_hours' => 45.0,
                'total_hours' => 30.5
            ],
            [
                'course_type_id' => 2,
                'course_type_name' => 'Exercise',
                'sub_resource_id' => 101,
                'sub_resource_name' => 'Mathematics',
                'sub_resource_hours' => 45.0,
                'total_hours' => 15.0
            ]
        ];

        $mockRepository->expects($this->once())
            ->method('createQueryBuilder')
            ->with('f')
            ->willReturn($queryBuilder);

        $queryBuilder->expects($this->once())
            ->method('select')
            ->with(
                'ct.id as course_type_id',
                'ct.name as course_type_name',
                'sr.id as sub_resource_id',
                'sr.name as sub_resource_name',
                'r.total_hours as sub_resource_hours',
                'SUM(a.allocated_hours) as total_hours'
            )
            ->willReturnSelf();

        $expectedJoins = [
            ['f.semesters', 's', null, null],
            ['s.resources', 'r', null, null],
            ['r.subResources', 'sr', null, null],
            ['sr.assignments', 'a', null, null]
        ];

        $joinIndex = 0;
        $queryBuilder->expects($this->exactly(4))
            ->method('leftJoin')
            ->willReturnCallback(function($join, $alias) use (&$joinIndex, $expectedJoins, $queryBuilder) {
                $this->assertLessThan(count($expectedJoins), $joinIndex, 'Too many leftJoin calls');
                $this->assertEquals($expectedJoins[$joinIndex][0], $join, "Join {$joinIndex} should be {$expectedJoins[$joinIndex][0]}");
                $this->assertEquals($expectedJoins[$joinIndex][1], $alias, "Alias {$joinIndex} should be {$expectedJoins[$joinIndex][1]}");
                $joinIndex++;
                return $queryBuilder;
            });

        $queryBuilder->expects($this->once())
            ->method('innerJoin')
            ->with('a.id_course_types', 'ct')
            ->willReturnSelf();

        $queryBuilder->expects($this->once())
            ->method('where')
            ->with('f.id = :formationId')
            ->willReturnSelf();

        $queryBuilder->expects($this->once())
            ->method('setParameter')
            ->with('formationId', 42)
            ->willReturnSelf();

        $queryBuilder->expects($this->once())
            ->method('groupBy')
            ->with('ct.id', 'ct.name', 'sr.id', 'sr.name')
            ->willReturnSelf();

        $queryBuilder->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $query->expects($this->once())
            ->method('getResult')
            ->willReturn($expectedResult);

        $result = $mockRepository->getHoursByGroups(42);

        $this->assertEquals($expectedResult, $result);
        $this->assertCount(2, $result);
        $this->assertEquals(1, $result[0]['course_type_id']);
        $this->assertEquals('Lecture', $result[0]['course_type_name']);
        $this->assertEquals(101, $result[0]['sub_resource_id']);
        $this->assertEquals('Mathematics', $result[0]['sub_resource_name']);
        $this->assertEquals(30.5, $result[0]['total_hours']);
    }

    public function testGetHoursByGroupsWithEmptyResult(): void
    {
        $mockRepository = $this->getMockBuilder(FormationRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['createQueryBuilder'])
            ->getMock();

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(AbstractQuery::class);

        $mockRepository->expects($this->once())
            ->method('createQueryBuilder')
            ->willReturn($queryBuilder);

        $queryBuilder->expects($this->once())
            ->method('select')
            ->willReturnSelf();

        $queryBuilder->method('leftJoin')->willReturnSelf();
        $queryBuilder->method('innerJoin')->willReturnSelf();
        $queryBuilder->method('where')->willReturnSelf();
        $queryBuilder->method('setParameter')->willReturnSelf();
        $queryBuilder->method('groupBy')->willReturnSelf();

        $queryBuilder->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $query->expects($this->once())
            ->method('getResult')
            ->willReturn([]);

        $result = $mockRepository->getHoursByGroups(99);

        $this->assertEmpty($result);
    }

    public function testGetHoursByGroupsWithNullValues(): void
    {
        $mockRepository = $this->getMockBuilder(FormationRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['createQueryBuilder'])
            ->getMock();

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(AbstractQuery::class);

        $resultWithNulls = [
            [
                'course_type_id' => null,
                'course_type_name' => 'Unknown Type',
                'sub_resource_id' => 101,
                'sub_resource_name' => 'Mathematics',
                'sub_resource_hours' => null,
                'total_hours' => 20.0
            ],
            [
                'course_type_id' => 2,
                'course_type_name' => 'Exercise',
                'sub_resource_id' => null,
                'sub_resource_name' => null,
                'sub_resource_hours' => null,
                'total_hours' => null
            ]
        ];

        $mockRepository->expects($this->once())
            ->method('createQueryBuilder')
            ->willReturn($queryBuilder);

        $queryBuilder->method('select')->willReturnSelf();
        $queryBuilder->method('leftJoin')->willReturnSelf();
        $queryBuilder->method('innerJoin')->willReturnSelf();
        $queryBuilder->method('where')->willReturnSelf();
        $queryBuilder->method('setParameter')->willReturnSelf();
        $queryBuilder->method('groupBy')->willReturnSelf();
        $queryBuilder->method('getQuery')->willReturn($query);

        $query->expects($this->once())
            ->method('getResult')
            ->willReturn($resultWithNulls);

        $result = $mockRepository->getHoursByGroups(42);

        $this->assertCount(2, $result);
        $this->assertNull($result[0]['course_type_id']);
        $this->assertNull($result[1]['sub_resource_id']);
        $this->assertNull($result[1]['sub_resource_name']);
        $this->assertNull($result[1]['total_hours']);
    }

    public function testGetHoursByGroupsQueryStructure(): void
    {
        $formationId = 42;

        $mockRepository = $this->getMockBuilder(FormationRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['createQueryBuilder'])
            ->getMock();

        $realQueryBuilder = $this->createMock(QueryBuilder::class);
        $expr = $this->createMock(Expr::class);
        $query = $this->createMock(AbstractQuery::class);

        $mockRepository->expects($this->once())
            ->method('createQueryBuilder')
            ->with('f')
            ->willReturn($realQueryBuilder);

        $realQueryBuilder->expects($this->exactly(1))
            ->method('select')
            ->with(
                'ct.id as course_type_id',
                'ct.name as course_type_name',
                'sr.id as sub_resource_id',
                'sr.name as sub_resource_name',
                'r.total_hours as sub_resource_hours',
                'SUM(a.allocated_hours) as total_hours'
            )
            ->willReturnSelf();

        $joinExpectations = [
            ['f.semesters', 's'],
            ['s.resources', 'r'],
            ['r.subResources', 'sr'],
            ['sr.assignments', 'a']
        ];

        $realQueryBuilder->expects($this->exactly(4))
            ->method('leftJoin')
            ->willReturnCallback(function($join, $alias) use (&$joinExpectations, $realQueryBuilder) {
                $expected = array_shift($joinExpectations);
                $this->assertIsArray($expected, 'Expected join expectations to be an array.');
                $this->assertArrayHasKey(0, $expected, 'Missing key 0 in expected join expectations.');
                $this->assertArrayHasKey(1, $expected, 'Missing key 1 in expected join expectations.');

                $this->assertEquals($expected[0], $join);
                $this->assertEquals($expected[1], $alias);
                return $realQueryBuilder;
            });

        $realQueryBuilder->expects($this->once())
            ->method('innerJoin')
            ->with('a.id_course_types', 'ct')
            ->willReturnSelf();

        $realQueryBuilder->expects($this->once())
            ->method('where')
            ->with('f.id = :formationId')
            ->willReturnSelf();

        $realQueryBuilder->expects($this->once())
            ->method('setParameter')
            ->with('formationId', $formationId)
            ->willReturnSelf();

        $realQueryBuilder->expects($this->once())
            ->method('groupBy')
            ->with('ct.id', 'ct.name', 'sr.id', 'sr.name')
            ->willReturnSelf();

        $realQueryBuilder->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $query->expects($this->once())
            ->method('getResult')
            ->willReturn([]);

        $mockRepository->getHoursByGroups($formationId);
    }

    protected function tearDown(): void
    {
        if ($this->entityManager !== null) {
            $this->entityManager->rollback();
            $this->entityManager->close();
            $this->entityManager = null;
        }

        parent::tearDown();
    }
}
