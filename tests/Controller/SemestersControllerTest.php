<?php

namespace App\Tests\Controller;

use App\Controller\SemestersController;
use App\Entity\Formation;
use App\Entity\Semesters;
use App\Repository\FormationRepository;
use App\Repository\SemestersRepository;
use App\Service\SchoolYearService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class SemestersControllerTest extends TestCase
{
    /** @var MockObject&EntityManagerInterface */
    private MockObject $entityManager;
    private SemestersController $controller;
    /** @var MockObject&FormationRepository */
    private MockObject $formationRepository;
    /** @var MockObject&SemestersRepository */
    private MockObject $semestersRepository;
    /** @var MockObject&SchoolYearService */
    private MockObject $schoolYearService;
    private Container $container;

    protected function setUp(): void
    {
        $this->semestersRepository = $this->createMock(SemestersRepository::class);
        $this->formationRepository = $this->createMock(FormationRepository::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->schoolYearService = $this->createMock(SchoolYearService::class);

        $this->controller = new SemestersController($this->entityManager, $this->schoolYearService);

        $this->container = new Container();
        $this->container->set('doctrine', $this->entityManager);

        $this->controller->setContainer($this->container);
    }

    public function testListActionWithFilters(): void
    {
        $semester = new Semesters();
        $semester->setId(1);
        $semester->setName('Test Semester');
        $semester->setOrderNumber(1);
        $semester->setStartDate(new \DateTime('2025-01-01'));
        $semester->setEndDate(new \DateTime('2025-06-30'));

        $request = new Request(['id_formation' => '1', 'id_school_year' => '1']);

        $this->semestersRepository->expects($this->once())
            ->method('findByFilters')
            ->with(1, 1)
            ->willReturn([$semester]);

        $response = $this->controller->list($request, $this->semestersRepository);
        $this->assertInstanceOf(Response::class, $response);

        $content = $response->getContent();
        $this->assertIsString($content, 'Response should be a string');

        $contentArray = json_decode($content, true);

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertCount(1, $contentArray);
        $this->assertEquals(1, $contentArray[0]['id']);
        $this->assertEquals('Test Semester', $contentArray[0]['name']);
        $this->assertEquals('2025-01-01', $contentArray[0]['start_date']);
        $this->assertEquals('2025-06-30', $contentArray[0]['end_date']);
        $this->assertEquals(1, $contentArray[0]['order_number']);
    }

    public function testShowAction(): void
    {
        $semester = new Semesters();
        $semester->setId(1);
        $semester->setName('Test Semester');
        $semester->setOrderNumber(1);
        $semester->setStartDate(new \DateTime('2025-01-01'));
        $semester->setEndDate(new \DateTime('2025-06-30'));

        $response = $this->controller->show($semester);
        $this->assertInstanceOf(Response::class, $response);

        $content = $response->getContent();
        $this->assertIsString($content, 'Response should be a string');

        $contentArray = json_decode($content, true);

        $this->assertEquals(1, $contentArray['id']);
        $this->assertEquals('Test Semester', $contentArray['name']);
        $this->assertEquals('2025-01-01', $contentArray['start_date']);
        $this->assertEquals('2025-06-30', $contentArray['end_date']);
        $this->assertEquals(1, $contentArray['order_number']);
    }

    public function testCreateActionSuccess(): void
    {
        $formation = new Formation();
        $formation->setId(1);

        $this->formationRepository->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($formation);

        $data = [
            'name' => 'New Semester',
            'start_date' => '2025-01-01',
            'end_date' => '2025-06-30',
            'order_number' => 1,
            'id_formation' => 1
        ];

        $request = new Request([], [], [], [], [], [], assert(
            is_string($json = json_encode($data))) ? $json : ''
        );

        $response = $this->controller->create($request, $this->formationRepository);
        $this->assertInstanceOf(Response::class, $response);

        $content = $response->getContent();
        $this->assertIsString($content, 'Response should be a string');

        $contentArray = json_decode($content, true);

        $this->assertEquals(Response::HTTP_CREATED, $response->getStatusCode());
        $this->assertEquals('New Semester', $contentArray['name']);
        $this->assertEquals('2025-01-01', $contentArray['start_date']);
        $this->assertEquals('2025-06-30', $contentArray['end_date']);
        $this->assertEquals(1, $contentArray['order_number']);
    }

    public function testCreateActionInvalidData(): void
    {
        $request = new Request([], [], [], [], [], [], assert(
            is_string($json = json_encode(['name' => 'Test']))) ? $json: ''
        );

        $response = $this->controller->create($request, $this->formationRepository);
        $this->assertInstanceOf(Response::class, $response);

        $content = $response->getContent();
        $this->assertIsString($content, 'Response should be a string');

        $contentArray = json_decode($content, true);

        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertEquals('Invalid input data', $contentArray['error']);
    }

    public function testUpdateActionSuccess(): void
    {
        $semester = new Semesters();
        $semester->setId(1);
        $semester->setName('Old Name');

        $data = [
            'name' => 'Updated Name',
            'start_date' => '2025-01-01',
            'end_date' => '2025-06-30',
            'order_number' => 2
        ];

        $request = new Request([], [], [], [], [], [], assert(
            is_string($json = json_encode($data))) ? $json : ''
        );

        $response = $this->controller->update($request, $semester);
        $this->assertInstanceOf(Response::class, $response);

        $content = $response->getContent();
        $this->assertIsString($content, 'Response should be a string');

        $contentArray = json_decode($content, true);

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertEquals('Updated Name', $contentArray['name']);
        $this->assertEquals('2025-01-01', $contentArray['start_date']);
        $this->assertEquals('2025-06-30', $contentArray['end_date']);
        $this->assertEquals(2, $contentArray['order_number']);
    }

    public function testUpdateActionInvalidData(): void
    {
        $semester = new Semesters();
        $request = new Request([], [], [], [], [], [], 'invalid json');

        $response = $this->controller->update($request, $semester);
        $this->assertInstanceOf(Response::class, $response);

        $content = $response->getContent();
        $this->assertIsString($content, 'Response should be a string');

        $contentArray = json_decode($content, true);

        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertEquals('Invalid or empty data provided', $contentArray['error']);
    }

    public function testDeleteActionSuccess(): void
    {
        $semester = new Semesters();
        $semester->setId(1);

        $this->entityManager->expects($this->once())
            ->method('remove')
            ->with($semester);

        $response = $this->controller->delete($semester);
        $this->assertInstanceOf(Response::class, $response);

        $content = $response->getContent();
        $this->assertIsString($content, 'Response should be a string');

        $contentArray = json_decode($content, true);

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertEquals('Semester deleted successfully', $contentArray['message']);
    }
}
