<?php

namespace App\Tests\Controller;

use App\Controller\PedagogicalInterruptionsController;
use App\Entity\Formation;
use App\Entity\PedagogicalInterruptions;
use App\Entity\SchoolYear;
use App\Repository\FormationRepository;
use App\Repository\PedagogicalInterruptionsRepository;
use App\Repository\SchoolYearRepository;
use App\Service\SchoolYearService;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\SerializerInterface;

class PedagogicalInterruptionsControllerTest extends TestCase
{
    private PedagogicalInterruptionsController $controller;
    /** @var MockObject&EntityManagerInterface */
    private MockObject $entityManager;
    /** @var MockObject&PedagogicalInterruptionsRepository */
    private MockObject $repository;
    /** @var MockObject&FormationRepository */
    private MockObject $formationRepository;
    /** @var MockObject&SchoolYearRepository */
    private MockObject $schoolYearRepository;
    /** @var MockObject&SchoolYearService */
    private MockObject $schoolYearService;
    private ContainerInterface $container;
    private SerializerInterface $serializer;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->repository = $this->createMock(PedagogicalInterruptionsRepository::class);
        $this->formationRepository = $this->createMock(FormationRepository::class);
        $this->schoolYearRepository = $this->createMock(SchoolYearRepository::class);
        $this->schoolYearService = $this->createMock(SchoolYearService::class);
        $this->container = $this->createMock(ContainerInterface::class);
        $this->serializer = $this->createMock(SerializerInterface::class);

        $schoolYear = new SchoolYear();
        $schoolYear->setId(1);
        $schoolYear->setCurrentSchoolYear(true);

        $this->schoolYearService
            ->expects($this->any())
            ->method('getCurrentSchoolYear')
            ->willReturn($schoolYear);

        $this->serializer->expects($this->any())
            ->method('serialize')
            ->willReturnCallback(fn ($data) => json_encode($data));

        $this->container->expects($this->any())
            ->method('has')
            ->with('serializer')
            ->willReturn(true);

        $this->container->expects($this->any())
            ->method('get')
            ->with('serializer')
            ->willReturn($this->serializer);

        $this->controller = new PedagogicalInterruptionsController($this->schoolYearService);
        $this->controller->setContainer($this->container);
    }

    public function testIndex(): void
    {
        $schoolYear = new SchoolYear();
        $schoolYear->setId(1);
        $schoolYear->setCurrentSchoolYear(true);

        $this->schoolYearService
            ->expects($this->once())
            ->method('getCurrentSchoolYear')
            ->willReturn($schoolYear);

        $interruption = new PedagogicalInterruptions();
        $interruption->setId(1);
        $interruption->setName('Test Interruption');

        $formation = new Formation();
        $formation->setId(1);
        $formation->setIdSchoolYear($schoolYear);
        $interruption->setIdFormation($formation);

        $startDate = new \DateTime('2024-01-01');
        $endDate = new \DateTime('2024-01-15');
        $interruption->setStartDate($startDate);
        $interruption->setEndDate($endDate);

        $this->repository
            ->expects($this->once())
            ->method('findByFilters')
            ->with(null, 1)
            ->willReturn([$interruption]);

        $request = new Request();

        $response = $this->controller->index($request, $this->repository, $this->schoolYearRepository);

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertIsString($response->getContent());
        $content = json_decode($response->getContent(), true);
        $this->assertIsArray($content);
        $this->assertCount(1, $content);

        $this->assertEquals(1, $content[0]['id']);
        $this->assertEquals('Test Interruption', $content[0]['name']);
        $this->assertEquals($startDate->format('Y-m-d'), $content[0]['start_date']);
        $this->assertEquals($endDate->format('Y-m-d'), $content[0]['end_date']);
    }

    public function testShow(): void
    {
        $interruption = new PedagogicalInterruptions();
        $interruption->setName('Test Show');
        $interruption->setStartDate(new \DateTime('2024-02-01'));
        $interruption->setEndDate(new \DateTime('2024-02-15'));

        $response = $this->controller->show($interruption);

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertIsString($response->getContent());
        $content = json_decode($response->getContent(), true);
        $this->assertEquals('Test Show', $content['name']);
    }

    public function testCreate(): void
    {
        $data = [
            'name' => 'New Interruption',
            'start_date' => '2024-03-01',
            'end_date' => '2024-03-15',
            'formation_id' => 1,
        ];

        $json = json_encode($data);
        if ($json === false) {
            throw new RuntimeException('Failed to encode data as JSON');
        }

        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            $json
        );

        $formation = $this->createMock(Formation::class);
        $formation->method('getId')->willReturn(1);

        $this->formationRepository
            ->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($formation);

        $this->entityManager
            ->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(PedagogicalInterruptions::class));

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $response = $this->controller->create($request, $this->entityManager, $this->formationRepository);

        $this->assertEquals(Response::HTTP_CREATED, $response->getStatusCode());
        $this->assertIsString($response->getContent());
        $content = json_decode($response->getContent(), true);
        $this->assertEquals('New Interruption', $content['name']);
    }

    public function testCreateInvalidData(): void
    {
        $invalidData = [
            'start_date' => '2024-03-01',
            'end_date' => '2024-03-15'
        ];

        $json = json_encode($invalidData);
        if ($json === false) {
            throw new RuntimeException('Failed to encode data as JSON');
        }

        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            $json
        );

        $response = $this->controller->create($request, $this->entityManager, $this->formationRepository);

        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertIsString($response->getContent());
        $content = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('error', $content);
    }

    public function testUpdate(): void
    {
        $interruption = new PedagogicalInterruptions();
        $interruption->setName('Original Name');

        $updateData = [
            'name' => 'Updated Name',
            'start_date' => '2024-04-02'
        ];

        $json = json_encode($updateData);
        if ($json === false) {
            throw new RuntimeException('Failed to encode data as JSON');
        }

        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            $json
        );

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $response = $this->controller->update($request, $interruption, $this->entityManager);

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertIsString($response->getContent());
        $content = json_decode($response->getContent(), true);
        $this->assertEquals('Updated Name', $content['name']);
    }

    public function testDelete(): void
    {
        $interruption = new PedagogicalInterruptions();
        $interruption->setName('To Delete');

        $this->entityManager
            ->expects($this->once())
            ->method('remove')
            ->with($interruption);

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $response = $this->controller->delete($interruption, $this->entityManager);

        $this->assertEquals(Response::HTTP_NO_CONTENT, $response->getStatusCode());
    }
}