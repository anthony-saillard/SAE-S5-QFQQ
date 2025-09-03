<?php

namespace App\Tests\Controller;

use App\Controller\AnnotationsController;
use App\Entity\Annotations;
use App\Entity\Resources;
use App\Repository\AnnotationsRepository;
use App\Repository\ResourcesRepository;
use App\Repository\UsersRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class AnnotationsControllerTest extends TestCase
{
    /** @var MockObject&EntityManagerInterface */
    private MockObject $entityManager;
    private AnnotationsController $controller;
    /** @var MockObject&AnnotationsRepository */
    private AnnotationsRepository $annotationsRepository;
    /** @var MockObject&ResourcesRepository */
    private MockObject $resourcesRepository;
    /** @var MockObject&UsersRepository */
    private MockObject $usersRepository;
    private Container $container;

    protected function setUp(): void
    {
        $this->annotationsRepository = $this->createMock(AnnotationsRepository::class);
        $this->resourcesRepository = $this->createMock(ResourcesRepository::class);
        $this->usersRepository = $this->createMock(UsersRepository::class);

        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->entityManager->method('getRepository')->willReturn($this->annotationsRepository);

        $this->controller = new AnnotationsController();

        $this->container = new Container();
        $this->container->set('doctrine', $this->entityManager);

        $this->controller->setContainer($this->container);
    }

    public function testIndexAction(): void
    {
        $annotation = new Annotations();
        $annotation->setId(1);
        $annotation->setDescription('Test annotation');

        $request = new Request();

        $this->annotationsRepository->expects($this->once())
            ->method('findByFilters')
            ->with(null, null)
            ->willReturn([$annotation]);

        $response = $this->controller->index($request, $this->annotationsRepository);
        $this->assertIsString($response->getContent());
        $content = json_decode($response->getContent(), true);

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertCount(1, $content);
        $this->assertEquals(1, $content[0]['id']);
        $this->assertEquals('Test annotation', $content[0]['description']);
    }

    public function testShowAction(): void
    {
        $annotation = new Annotations();
        $annotation->setId(1);
        $annotation->setDescription('Test annotation');

        $this->annotationsRepository->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($annotation);

        $response = $this->controller->show(1, $this->annotationsRepository);
        $this->assertIsString($response->getContent());
        $content = json_decode($response->getContent(), true);

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertEquals(1, $content['id']);
        $this->assertEquals('Test annotation', $content['description']);
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
            'description' => 'New annotation',
            'id_resources' => 1
        ];

        $request = new Request([], [], [], [], [], [], assert(
            is_string($json = json_encode($data))) ? $json : ''
        );

        $response = $this->controller->create($request, $this->entityManager, $this->resourcesRepository, $this->usersRepository);
        $this->assertIsString($response->getContent());
        $content = json_decode($response->getContent(), true);

        $this->assertEquals(Response::HTTP_CREATED, $response->getStatusCode());
        $this->assertEquals('Annotation created successfully', $content['message']);
        $this->assertEquals('New annotation', $content['description']);
    }

    public function testCreateActionInvalidJson(): void
    {
        $request = new Request([], [], [], [], [], [], 'invalid json');

        $response = $this->controller->create($request, $this->entityManager, $this->resourcesRepository, $this->usersRepository);
        $this->assertIsString($response->getContent());
        $content = json_decode($response->getContent(), true);

        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertEquals('Description and id_resources are required', $content['error']);
    }

    public function testUpdateActionSuccess(): void
    {
        $annotation = new Annotations();
        $annotation->setId(1);
        $annotation->setDescription('Old description');

        $this->annotationsRepository->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($annotation);

        $resource = new Resources();
        $resource->setId(1);

        $this->resourcesRepository->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($resource);

        $data = [
            'description' => 'Updated description',
            'id_resources' => 1
        ];

        $request = new Request([], [], [], [], [], [], assert(
            is_string($json = json_encode($data))) ? $json : ''
        );

        $response = $this->controller->update(1, $request, $this->entityManager, $this->annotationsRepository, $this->resourcesRepository, $this->usersRepository);
        $this->assertIsString($response->getContent());
        $content = json_decode($response->getContent(), true);

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertEquals('Annotation updated successfully', $content['message']);
    }

    public function testUpdateActionInvalidJson(): void
    {
        $annotation = new Annotations();
        $annotation->setId(1);

        $this->annotationsRepository->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($annotation);

        $request = new Request([], [], [], [], [], [], 'invalid json');

        $response = $this->controller->update(1, $request, $this->entityManager, $this->annotationsRepository, $this->resourcesRepository, $this->usersRepository);
        $this->assertIsString($response->getContent());
        $content = json_decode($response->getContent(), true);

        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertEquals('Invalid JSON', $content['error']);
    }

    public function testDeleteActionSuccess(): void
    {
        $annotation = new Annotations();
        $annotation->setId(1);

        $this->annotationsRepository->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($annotation);

        $this->entityManager->expects($this->once())
            ->method('remove')
            ->with($annotation);

        $this->entityManager->expects($this->once())
            ->method('flush');

        $response = $this->controller->delete(1, $this->entityManager, $this->annotationsRepository);

        $this->assertEquals(Response::HTTP_NO_CONTENT, $response->getStatusCode());
    }
}
