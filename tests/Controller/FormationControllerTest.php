<?php

namespace App\Tests\Controller;

use App\Controller\FormationController;
use App\Entity\Formation;
use App\Entity\SchoolYear;
use App\Repository\FormationRepository;
use App\Repository\SchoolYearRepository;
use App\Service\SchoolYearService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use RuntimeException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\SerializerInterface;

class FormationControllerTest extends TestCase
{
    private FormationController $controller;
    /** @var MockObject&EntityManagerInterface */
    private MockObject $entityManager;
    /** @var MockObject&FormationRepository */
    private MockObject $repository;
    private ContainerInterface $container;
    private SerializerInterface $serializer;
    /** @var MockObject&SchoolYearService */
    private MockObject $schoolYearService;
    private SchoolYearRepository $schoolYearRepository;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->repository = $this->createMock(FormationRepository::class);
        $this->container = $this->createMock(ContainerInterface::class);
        $this->serializer = $this->createMock(SerializerInterface::class);
        $this->schoolYearRepository = $this->createMock(SchoolYearRepository::class);
        $this->schoolYearService = $this->createMock(SchoolYearService::class);

        // Mock serializer
        $this->serializer->method('serialize')->willReturnCallback(fn ($data) => json_encode($data));

        // Mock container
        $this->container->method('has')->willReturn(true);
        $this->container->method('get')->with('serializer')->willReturn($this->serializer);

        $this->controller = new FormationController($this->schoolYearService);
        $this->controller->setContainer($this->container);
    }

    public function testIndex(): void
    {
        $schoolYear = new SchoolYear();
        $schoolYear->setId(1);
        $schoolYear->setLabel("2024-2025");
        $schoolYear->setCurrentSchoolYear(true);

        $formation = new Formation();
        $formation->setLabel('Test Formation');
        $formation->setOrderNumber(1);
        $formation->setIdSchoolYear($schoolYear);

        $request = new Request();

        $this->schoolYearService
            ->method('getCurrentSchoolYear')
            ->willReturn($schoolYear);

        $this->repository
            ->expects($this->once())
            ->method('findBy')
            ->with(['id_school_year' => $schoolYear])
            ->willReturn([$formation]);

        $response = $this->controller->index($request, $this->repository, $this->schoolYearRepository);

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertIsString($response->getContent());
        $content = json_decode($response->getContent(), true);
        $this->assertCount(1, $content);
        $this->assertEquals('Test Formation', $content[0]['label']);
    }

    public function testShow(): void
    {
        $formation = new Formation();
        $formation->setLabel('Test Show');
        $formation->setOrderNumber(2);

        $response = $this->controller->show($formation);

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertIsString($response->getContent());
        $content = json_decode($response->getContent(), true);
        $this->assertEquals('Test Show', $content['label']);
    }

    public function testCreate(): void
    {
        $schoolYearMock = $this->createMock(SchoolYear::class);
        $this->schoolYearService
            ->method('getCurrentSchoolYear')
            ->willReturn($schoolYearMock);

        $data = ['label' => 'New Formation', 'order_number' => 3];

        $json = json_encode($data);
        if ($json === false) {
            throw new RuntimeException('Failed to encode data as JSON');
        }

        $request = new Request([], [], [], [], [], ['CONTENT_TYPE' => 'application/json'], $json);

        $response = $this->controller->create($request, $this->entityManager, $this->schoolYearRepository, $this->schoolYearService);
        $this->assertIsString($response->getContent());
        $content = json_decode($response->getContent(), true);

        $this->assertEquals(Response::HTTP_CREATED, $response->getStatusCode());
        $this->assertEquals('New Formation', $content['label']);
    }

    public function testCreateInvalidData(): void
    {
        $request = new Request([], [], [], [], [], ['CONTENT_TYPE' => 'application/json'], 'invalid json');

        $response = $this->controller->create($request, $this->entityManager, $this->schoolYearRepository, $this->schoolYearService);

        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertIsString($response->getContent());
        $content = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('error', $content);
    }

    public function testUpdate(): void
    {
        $formation = new Formation();
        $formation->setLabel('Old Label');

        $updateData = ['label' => 'Updated Label'];

        $json = json_encode($updateData);
        if ($json === false) {
            throw new RuntimeException('Failed to encode data as JSON');
        }

        $request = new Request([], [], [], [], [], ['CONTENT_TYPE' => 'application/json'], $json);

        $this->entityManager->expects($this->once())->method('flush');

        $response = $this->controller->update($request, $formation, $this->entityManager);

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertIsString($response->getContent());
        $content = json_decode($response->getContent(), true);
        $this->assertEquals('Formation updated successfully', $content['message']);
    }

    public function testDelete(): void
    {
        $formation = new Formation();

        $this->entityManager->expects($this->once())->method('remove')->with($formation);
        $this->entityManager->expects($this->once())->method('flush');

        $response = $this->controller->delete($formation, $this->entityManager);

        $this->assertEquals(Response::HTTP_NO_CONTENT, $response->getStatusCode());
    }

    public function testHours(): void
    {
        $formation = new Formation();
        $formation->setLabel('Test Formation');
        $formationId = 42;

        $reflectionProperty = new ReflectionProperty(Formation::class, 'id');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($formation, $formationId);

        $mockData = [
            [
                'sub_resource_id' => 1,
                'sub_resource_name' => 'Mathematics',
                'course_type_id' => 101,
                'course_type_name' => 'Lecture',
                'total_hours' => 30.5
            ],
            [
                'sub_resource_id' => 1,
                'sub_resource_name' => 'Mathematics',
                'course_type_id' => 102,
                'course_type_name' => 'Practice',
                'total_hours' => 15.0
            ],
            [
                'sub_resource_id' => 2,
                'sub_resource_name' => 'Computer Science',
                'course_type_id' => 101,
                'course_type_name' => 'Lecture',
                'total_hours' => 25.0
            ]
        ];

        $this->repository
            ->expects($this->once())
            ->method('getHoursByGroups')
            ->with($formationId)
            ->willReturn($mockData);

        $response = $this->controller->hours($formation, $this->repository);

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        $this->assertIsString($response->getContent());
        $content = json_decode($response->getContent(), true);
        $this->assertIsArray($content);
        $this->assertEquals($formationId, $content['id']);
        $this->assertEquals('Test Formation', $content['label']);

        $this->assertArrayHasKey('sub_resources', $content);
        $this->assertCount(2, $content['sub_resources']);

        $mathSubResource = $content['sub_resources'][0];
        $this->assertEquals(1, $mathSubResource['id']);
        $this->assertEquals('Mathematics', $mathSubResource['name']);
        $this->assertCount(2, $mathSubResource['course_types_hours']);

        $this->assertEquals(101, $mathSubResource['course_types_hours'][0]['course_type_id']);
        $this->assertEquals('Lecture', $mathSubResource['course_types_hours'][0]['course_type_name']);
        $this->assertEquals(30.5, $mathSubResource['course_types_hours'][0]['total_hours']);

        $csSubResource = $content['sub_resources'][1];
        $this->assertEquals(2, $csSubResource['id']);
        $this->assertEquals('Computer Science', $csSubResource['name']);
        $this->assertCount(1, $csSubResource['course_types_hours']);
    }

    public function testHoursWithNullFormationId(): void
    {
        $formation = new Formation();
        $formation->setLabel('Test Formation Without ID');

        $response = $this->controller->hours($formation, $this->repository);

        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());

        $this->assertIsString($response->getContent());
        $content = json_decode($response->getContent(), true);
        $this->assertIsArray($content);
        $this->assertArrayHasKey('error', $content);
        $this->assertEquals('Formation ID is null', $content['error']);
    }

    public function testHoursWithNoSubResources(): void
    {
        $formation = new Formation();
        $formation->setLabel('Test Formation');
        $formationId = 42;

        $reflectionProperty = new \ReflectionProperty(Formation::class, 'id');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($formation, $formationId);

        $this->repository
            ->expects($this->once())
            ->method('getHoursByGroups')
            ->with($formationId)
            ->willReturn([]);

        $response = $this->controller->hours($formation, $this->repository);

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        $this->assertIsString($response->getContent());
        $content = json_decode($response->getContent(), true);
        $this->assertEquals($formationId, $content['id']);
        $this->assertEquals('Test Formation', $content['label']);
        $this->assertArrayHasKey('sub_resources', $content);
        $this->assertEmpty($content['sub_resources']);
    }

    public function testHoursWithNullSubResourceId(): void
    {
        $formation = new Formation();
        $formation->setLabel('Test Formation');
        $formationId = 42;

        $reflectionProperty = new \ReflectionProperty(Formation::class, 'id');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($formation, $formationId);

        $mockData = [
            [
                'sub_resource_id' => null,
                'sub_resource_name' => 'Invalid Resource',
                'course_type_id' => 101,
                'course_type_name' => 'Lecture',
                'total_hours' => 30.0
            ],
            [
                'sub_resource_id' => 1,
                'sub_resource_name' => 'Valid Resource',
                'course_type_id' => 101,
                'course_type_name' => 'Lecture',
                'total_hours' => 25.0
            ]
        ];

        $this->repository
            ->expects($this->once())
            ->method('getHoursByGroups')
            ->with($formationId)
            ->willReturn($mockData);

        $response = $this->controller->hours($formation, $this->repository);

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        $this->assertIsString($response->getContent());
        $content = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('sub_resources', $content);
        $this->assertCount(1, $content['sub_resources']);
        $this->assertEquals(1, $content['sub_resources'][0]['id']);
        $this->assertEquals('Valid Resource', $content['sub_resources'][0]['name']);
    }
}
