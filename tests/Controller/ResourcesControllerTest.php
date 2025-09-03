<?php

namespace App\Tests\Controller;

use App\Controller\ResourcesController;
use App\Entity\Resources;
use App\Entity\Semesters;
use App\Repository\ResourcesRepository;
use App\Repository\SemestersRepository;
use App\Repository\UsersRepository;
use App\Service\SchoolYearService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ResourcesControllerTest extends TestCase
{
    /** @var MockObject&EntityManagerInterface */
    private MockObject $entityManager;
    private ResourcesController $controller;
    /** @var MockObject&ResourcesRepository */
    private MockObject $resourcesRepository;
    /** @var MockObject&SemestersRepository */
    private MockObject $semestersRepository;
    private UsersRepository $usersRepository;
    /** @var MockObject&SchoolYearService */
    private MockObject $schoolYearService;
    private Container $container;

    protected function setUp(): void
    {
        $this->resourcesRepository = $this->createMock(ResourcesRepository::class);
        $this->semestersRepository = $this->createMock(SemestersRepository::class);
        $this->usersRepository = $this->createMock(UsersRepository::class);

        $this->schoolYearService = $this->createMock(SchoolYearService::class);

        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->entityManager->method('getRepository')->willReturn($this->resourcesRepository);

        $this->controller = new ResourcesController($this->schoolYearService);

        $this->container = new Container();
        $this->container->set('doctrine', $this->entityManager);

        $this->controller->setContainer($this->container);
    }

    public function testListActionWithFilters(): void
    {
        $resource = new Resources();
        $resource->setId(1);
        $resource->setIdentifier('TEST001');
        $resource->setName('Test Resource');
        $resource->setDescription('Test Description');
        $resource->setTotalHours(20);

        $request = new Request([
            'id_user' => '1',
            'id_semester' => '2',
            'id_formation' => '3',
            'id_school_year' => '4'
        ]);

        $this->resourcesRepository->expects($this->once())
            ->method('findByFilters')
            ->with(1, 2, 3, 4)
            ->willReturn([$resource]);

        $response = $this->controller->list($request, $this->resourcesRepository);
        $this->assertIsString($response->getContent());
        $content = json_decode($response->getContent(), true);

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertCount(1, $content);
        $this->assertEquals(1, $content[0]['id']);
        $this->assertEquals('TEST001', $content[0]['identifier']);
        $this->assertEquals('Test Resource', $content[0]['name']);
        $this->assertEquals('Test Description', $content[0]['description']);
        $this->assertEquals(20, $content[0]['total_hours']);
    }

    public function testShowAction(): void
    {
        $resource = new Resources();
        $resource->setId(1);
        $resource->setIdentifier('TEST001');
        $resource->setName('Test Resource');
        $resource->setDescription('Test Description');

        $response = $this->controller->show($resource);
        $this->assertIsString($response->getContent());
        $content = json_decode($response->getContent(), true);

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertEquals(1, $content['id']);
        $this->assertEquals('TEST001', $content['identifier']);
        $this->assertEquals('Test Resource', $content['name']);
        $this->assertEquals('Test Description', $content['description']);
    }

    public function testCreateActionSuccess(): void
    {
        $semester = new Semesters();
        $semester->setId(1);

        $this->semestersRepository->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($semester);

        $data = [
            'identifier' => 'TEST001',
            'name' => 'New Resource',
            'description' => 'New Description',
            'id_semesters' => 1,
            'total_hours' => 30
        ];

        $request = new Request([], [], [], [], [], [], assert(
            is_string($json = json_encode($data))) ? $json : ''
        );

        $response = $this->controller->create(
            $request,
            $this->entityManager,
            $this->semestersRepository,
            $this->usersRepository
        );
        $this->assertIsString($response->getContent());
        $content = json_decode($response->getContent(), true);

        $this->assertEquals(Response::HTTP_CREATED, $response->getStatusCode());
        $this->assertEquals('TEST001', $content['identifier']);
        $this->assertEquals('New Resource', $content['name']);
        $this->assertEquals('New Description', $content['description']);
    }

    public function testCreateActionInvalidData(): void
    {
        $request = new Request([], [], [], [], [], [], assert(
            is_string($json = json_encode(['description' => 'Test']))) ? $json : ''
        );

        $response = $this->controller->create(
            $request,
            $this->entityManager,
            $this->semestersRepository,
            $this->usersRepository
        );
        $this->assertIsString($response->getContent());
        $content = json_decode($response->getContent(), true);

        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertEquals('The "name" field is required.', $content['error']);
    }

    public function testUpdateActionSuccess(): void
    {
        $resource = new Resources();
        $resource->setId(1);
        $resource->setIdentifier('OLD001');
        $resource->setName('Old Name');
        $resource->setTotalHours(20);

        $data = [
            'identifier' => 'NEW001',
            'name' => 'Updated Name',
            'description' => 'Updated Description',
            'total_hours' => 25
        ];

        $request = new Request([], [], [], [], [], [], assert(
            is_string($json = json_encode($data))) ? $json : ''
        );

        $response = $this->controller->update(
            $request,
            $resource,
            $this->entityManager,
            $this->semestersRepository,
            $this->usersRepository
        );
        $this->assertIsString($response->getContent());
        $content = json_decode($response->getContent(), true);

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertEquals('Resource updated successfully', $content['message']);
    }

    public function testDeleteActionSuccess(): void
    {
        $resource = new Resources();
        $resource->setId(1);

        $this->entityManager->expects($this->once())
            ->method('remove')
            ->with($resource);

        $response = $this->controller->delete($resource, $this->entityManager);
        $this->assertIsString($response->getContent());
        $content = json_decode($response->getContent(), true);

        $this->assertEquals(Response::HTTP_NO_CONTENT, $response->getStatusCode());
        $this->assertEquals('Resource deleted successfully', $content['message']);
    }
}
