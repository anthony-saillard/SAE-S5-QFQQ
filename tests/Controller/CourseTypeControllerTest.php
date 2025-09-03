<?php

namespace App\Tests\Controller;

use App\Controller\CourseTypeController;
use App\Entity\CourseTypes;
use App\Entity\SchoolYear;
use App\Repository\CourseTypesRepository;
use App\Repository\SchoolYearRepository;
use App\Service\SchoolYearService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\DependencyInjection\Container;

class CourseTypeControllerTest extends TestCase
{
    /** @var MockObject&EntityManagerInterface */
    private MockObject $entityManager;
    private CourseTypeController $controller;
    /** @var MockObject&CourseTypesRepository */
    private MockObject $repository;
    /** @var MockObject&SchoolYearRepository */
    private MockObject $schoolYearRepository;
    /** @var MockObject&SchoolYearService */
    private MockObject $schoolYearService;
    private Container $container;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(CourseTypesRepository::class);
        $this->schoolYearRepository = $this->createMock(SchoolYearRepository::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->schoolYearService = $this->createMock(SchoolYearService::class);

        // Mock getRepository to return schoolYearRepository
        $this->entityManager->method('getRepository')
            ->with(SchoolYear::class)
            ->willReturn($this->schoolYearRepository);

        $this->controller = new CourseTypeController($this->entityManager, $this->repository, $this->schoolYearService);

        // Create and configure container
        $this->container = new Container();
        $this->container->set('entity_manager', $this->entityManager);
        $this->container->set('course_types_repository', $this->repository);

        // Set container to controller
        $this->controller->setContainer($this->container);
    }

    public function testListCourseTypes(): void
    {
        $schoolYear = new SchoolYear();
        $schoolYear->setId(1);

        $this->schoolYearService
            ->expects($this->once())
            ->method('getCurrentSchoolYear')
            ->willReturn($schoolYear);

        $courseType = new CourseTypes();
        $courseType->setId(1)
            ->setName('Mathematics')
            ->setHourlyRate(50.0);

        $this->repository
            ->expects($this->once())
            ->method('findBy')
            ->with(['id_school_year' => $schoolYear])
            ->willReturn([$courseType]);

        $request = new Request();
        $response = $this->controller->list($request);
        $this->assertIsString($response->getContent());
        $content = json_decode($response->getContent(), true);

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertCount(1, $content);
    }

    public function testShowCourseType(): void
    {
        $courseType = new CourseTypes();
        $courseType->setId(1)
            ->setName('Mathematics')
            ->setHourlyRate(50.0);

        $response = $this->controller->show($courseType);
        $this->assertIsString($response->getContent());
        $content = json_decode($response->getContent(), true);

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertEquals('Mathematics', $content['name']);
        $this->assertEquals(50.0, $content['hourly_rate']);
        $this->assertEquals([], $content['groups']);
    }

    public function testCreateCourseTypeSuccess(): void
    {
        // Create a mock SchoolYear
        $schoolYear = new SchoolYear();
        $schoolYear->setId(1);

        $this->schoolYearService
            ->expects($this->once())
            ->method('getCurrentSchoolYear')
            ->willReturn($schoolYear);

        $data = [
            'name' => 'Physics',
            'hourly_rate' => 60.0,
            'id_school_year' => 1
        ];

        $request = new Request([], [], [], [], [], [], assert(
            is_string($json = json_encode($data))) ? $json : ''
        );

        $response = $this->controller->create($request, $this->schoolYearRepository);
        $this->assertIsString($response->getContent());
        $content = json_decode($response->getContent(), true);

        $this->assertEquals(Response::HTTP_CREATED, $response->getStatusCode());
        $this->assertEquals('Physics', $content['name']);
        $this->assertEquals(60.0, $content['hourly_rate']);
    }

    public function testCreateCourseTypeInvalidJson(): void
    {
        $request = new Request([], [], [], [], [], [], 'invalid json');

        $response = $this->controller->create($request, $this->schoolYearRepository);
        $this->assertIsString($response->getContent());
        $content = json_decode($response->getContent(), true);

        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertEquals('Invalid JSON data', $content['error']);
    }

    public function testCreateCourseTypeMissingData(): void
    {
        $data = ['name' => 'Physics']; // Missing hourly_rate and id_school_year

        $request = new Request([], [], [], [], [], [], assert(
            is_string($json = json_encode($data))) ? $json : ''
        );

        $response = $this->controller->create($request, $this->schoolYearRepository);
        $this->assertIsString($response->getContent());
        $content = json_decode($response->getContent(), true);

        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertEquals('Name (string) and hourly rate (number)', $content['error']);
    }

    public function testUpdateCourseTypeSuccess(): void
    {
        $courseType = new CourseTypes();
        $courseType->setId(1)
            ->setName('Old Name')
            ->setHourlyRate(50.0);

        $data = [
            'name' => 'New Name',
            'hourly_rate' => 70.0
        ];

        $request = new Request([], [], [], [], [], [], assert(
            is_string($json = json_encode($data))) ? $json : ''
        );

        $response = $this->controller->update($request, $courseType);
        $this->assertIsString($response->getContent());
        $content = json_decode($response->getContent(), true);

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertEquals('New Name', $content['name']);
        $this->assertEquals(70.0, $content['hourly_rate']);
    }

    public function testUpdateCourseTypeInvalidData(): void
    {
        $courseType = new CourseTypes();
        $data = ['hourly_rate' => 'invalid']; // hourly_rate should be numeric

        $request = new Request([], [], [], [], [], [], assert(
            is_string($json = json_encode($data))) ? $json : ''
        );

        $response = $this->controller->update($request, $courseType);
        $this->assertIsString($response->getContent());
        $content = json_decode($response->getContent(), true);

        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertEquals('Hourly rate must be a number', $content['error']);
    }

    public function testDeleteCourseTypeSuccess(): void
    {
        $courseType = new CourseTypes();
        $courseType->setId(1);

        $this->entityManager->expects($this->once())
            ->method('remove')
            ->with($courseType);

        $this->entityManager->expects($this->once())
            ->method('flush');

        $response = $this->controller->delete($courseType);
        $this->assertIsString($response->getContent());
        $content = json_decode($response->getContent(), true);

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertEquals('Course type deleted successfully', $content['message']);
    }

    public function testDeleteCourseTypeError(): void
    {
        $courseType = new CourseTypes();
        $courseType->setId(1);

        $this->entityManager->method('remove')
            ->willThrowException(new \Exception('DB Error'));

        $response = $this->controller->delete($courseType);
        $this->assertIsString($response->getContent());
        $content = json_decode($response->getContent(), true);

        $this->assertEquals(Response::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());
        $this->assertEquals('Failed to delete course type', $content['error']);
        $this->assertEquals('DB Error', $content['message']);
    }
}