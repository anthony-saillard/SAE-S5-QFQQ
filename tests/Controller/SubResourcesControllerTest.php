<?php

namespace App\Tests\Controller;

use App\Controller\SubResourcesController;
use App\Entity\Resources;
use App\Entity\SubResources;
use App\Repository\ResourcesRepository;
use App\Repository\SubResourcesRepository;
use App\Repository\UsersRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class SubResourcesControllerTest extends TestCase
{
    /** @var MockObject&EntityManagerInterface */
    private MockObject $entityManager;
    private SubResourcesController $controller;
    /** @var MockObject&SubResourcesRepository */
    private MockObject $subResourcesRepository;
    /** @var MockObject&ResourcesRepository */
    private MockObject $resourcesRepository;
    private UsersRepository $usersRepository;
    private Container $container;

    protected function setUp(): void
    {
        $this->subResourcesRepository = $this->getMockBuilder(SubResourcesRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->resourcesRepository = $this->getMockBuilder(ResourcesRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->usersRepository = $this->getMockBuilder(UsersRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->entityManager = $this->getMockBuilder(EntityManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->entityManager->method('getRepository')->willReturn($this->subResourcesRepository);

        $this->controller = new SubResourcesController();

        $this->container = new Container();
        $this->container->set('doctrine', $this->entityManager);

        $this->controller->setContainer($this->container);
    }

    public function testIndexActionWithFilters(): void
    {
        $subResource = new SubResources();
        $subResource->setId(1);
        $subResource->setName('Test SubResource');

        $request = new Request(['id_resource' => '1', 'id_user' => '2']);

        $this->subResourcesRepository->expects($this->once())
            ->method('findByFilters')
            ->with(1, 2)
            ->willReturn([$subResource]);

        $response = $this->controller->index($request, $this->subResourcesRepository);
        $this->assertInstanceOf(Response::class, $response);

        $content = $response->getContent();
        $this->assertIsString($content, 'Response should be a string');

        $contentArray = json_decode($content, true);

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertCount(1, $contentArray);
        $this->assertEquals(1, $contentArray[0]['id']);
        $this->assertEquals('Test SubResource', $contentArray[0]['name']);
    }

    public function testCreateActionSuccess(): void
    {
        $resource = new Resources();
        $resource->setId(1);

        $this->resourcesRepository->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($resource);

        $data = [
            'name' => 'New SubResource',
            'id_resources' => 1
        ];

        $request = new Request([], [], [], [], [], [], assert(
            is_string($json = json_encode($data))) ? $json : ''
        );

        $response = $this->controller->create(
            $request,
            $this->entityManager,
            $this->resourcesRepository,
            $this->usersRepository
        );
        $this->assertInstanceOf(Response::class, $response);

        $content = $response->getContent();
        $this->assertIsString($content, 'Response should be a string');

        $contentArray = json_decode($content, true);

        $this->assertEquals(Response::HTTP_CREATED, $response->getStatusCode());
        $this->assertEquals('SubResource created successfully', $contentArray['message']);
        $this->assertEquals('New SubResource', $contentArray['name']);
    }

    public function testCreateActionInvalidData(): void
    {
        $request = new Request([], [], [], [], [], [], assert(
            is_string($json = json_encode(['name' => 'Test']))) ? $json : ''
        );

        $response = $this->controller->create(
            $request,
            $this->entityManager,
            $this->resourcesRepository,
            $this->usersRepository
        );
        $this->assertInstanceOf(Response::class, $response);

        $content = $response->getContent();
        $this->assertIsString($content, 'Response should be a string');

        $contentArray = json_decode($content, true);

        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertEquals('Name and id_resources are required', $contentArray['error']);
    }

    public function testUpdateActionSuccess(): void
    {
        $subResource = new SubResources();
        $subResource->setId(1);
        $subResource->setName('Old Name');

        $this->subResourcesRepository->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($subResource);

        $data = [
            'name' => 'Updated Name'
        ];

        $request = new Request([], [], [], [], [], [], assert(
            is_string($json = json_encode($data))) ? $json : ''
        );

        $response = $this->controller->update(
            1,
            $request,
            $this->entityManager,
            $this->subResourcesRepository,
            $this->usersRepository
        );
        $this->assertInstanceOf(Response::class, $response);

        $content = $response->getContent();
        $this->assertIsString($content, 'Response should be a string');

        $contentArray = json_decode($content, true);

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertEquals('SubResource updated successfully', $contentArray['message']);
    }

    public function testUpdateActionNotFound(): void
    {
        $this->subResourcesRepository->expects($this->once())
            ->method('find')
            ->with(999)
            ->willReturn(null);

        $request = new Request([], [], [], [], [], [], assert(
            is_string($json = json_encode(['name' => 'Test']))) ? $json : ''
        );

        $response = $this->controller->update(
            999,
            $request,
            $this->entityManager,
            $this->subResourcesRepository,
            $this->usersRepository
        );
        $this->assertInstanceOf(Response::class, $response);

        $content = $response->getContent();
        $this->assertIsString($content, 'Response should be a string');

        $contentArray = json_decode($content, true);

        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
        $this->assertEquals('SubResource not found', $contentArray['error']);
    }

    public function testDeleteActionSuccess(): void
    {
        $subResource = new SubResources();
        $subResource->setId(1);

        $this->subResourcesRepository->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($subResource);

        $this->entityManager->expects($this->once())
            ->method('remove')
            ->with($subResource);

        $response = $this->controller->delete(
            1,
            $this->entityManager,
            $this->subResourcesRepository
        );
        $this->assertInstanceOf(Response::class, $response);

        $content = $response->getContent();
        $this->assertIsString($content, 'Response should be a string');

        $contentArray = json_decode($content, true);

        $this->assertEquals('SubResource deleted successfully', $contentArray['message']);
    }

    public function testDeleteActionNotFound(): void
    {
        $this->subResourcesRepository->expects($this->once())
            ->method('find')
            ->with(999)
            ->willReturn(null);

        $response = $this->controller->delete(
            999,
            $this->entityManager,
            $this->subResourcesRepository
        );
        $this->assertInstanceOf(Response::class, $response);

        $content = $response->getContent();
        $this->assertIsString($content, 'Response should be a string');

        $contentArray = json_decode($content, true);

        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
        $this->assertEquals('SubResource not found', $contentArray['error']);
    }
}
