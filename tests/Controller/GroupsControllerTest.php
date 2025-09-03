<?php

namespace App\Tests\Controller;

use App\Controller\GroupsController;
use App\Entity\Groups;
use App\Repository\GroupsRepository;
use App\Service\SchoolYearService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class GroupsControllerTest extends TestCase
{
    /** @var MockObject&EntityManagerInterface */
    private MockObject $entityManager;
    private GroupsController $controller;
    /** @var MockObject&GroupsRepository */
    private MockObject $groupsRepository;
    /** @var MockObject&SchoolYearService */
    private MockObject $schoolYearService;
    private Container $container;

    protected function setUp(): void
    {
        $this->groupsRepository = $this->createMock(GroupsRepository::class);
        $this->schoolYearService = $this->createMock(SchoolYearService::class);

        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->entityManager->method('getRepository')->willReturn($this->groupsRepository);

        $this->controller = new GroupsController($this->schoolYearService);

        $this->container = new Container();
        $this->container->set('doctrine', $this->entityManager);

        $this->controller->setContainer($this->container);
    }

    public function testIndexActionWithFilters(): void
    {
        $group = new Groups();
        $group->setId(1);
        $group->setName('Test Group');
        $group->setDescription('Test Description');
        $group->setOrderNumber(1);

        $request = new Request(['id_formation' => '1', 'id_school_year' => '2']);

        $this->groupsRepository->expects($this->once())
            ->method('findByFilters')
            ->with(1, 2)
            ->willReturn([$group]);

        $response = $this->controller->index($request, $this->groupsRepository);
        $this->assertIsString($response->getContent());
        $content = json_decode($response->getContent(), true);

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertCount(1, $content);
        $this->assertEquals(1, $content[0]['id']);
        $this->assertEquals('Test Group', $content[0]['name']);
        $this->assertEquals('Test Description', $content[0]['description']);
        $this->assertEquals(1, $content[0]['order_number']);
    }

    public function testShowAction(): void
    {
        $group = new Groups();
        $group->setId(1);
        $group->setName('Test Group');
        $group->setDescription('Test Description');
        $group->setOrderNumber(1);

        $response = $this->controller->show($group);
        $this->assertIsString($response->getContent());
        $content = json_decode($response->getContent(), true);

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertEquals(1, $content['id']);
        $this->assertEquals('Test Group', $content['name']);
        $this->assertEquals('Test Description', $content['description']);
        $this->assertEquals(1, $content['order_number']);
    }

    public function testCreateActionSuccess(): void
    {
        $data = [
            'name' => 'New Group',
            'description' => 'New Description',
            'order_number' => 1
        ];

        $request = new Request([], [], [], [], [], [], assert(
            is_string($json = json_encode($data))) ? $json : ''
        );

        $response = $this->controller->create($request, $this->entityManager);
        $this->assertIsString($response->getContent());
        $content = json_decode($response->getContent(), true);

        $this->assertEquals(Response::HTTP_CREATED, $response->getStatusCode());
        $this->assertEquals('New Group', $content['name']);
        $this->assertEquals('New Description', $content['description']);
        $this->assertEquals(1, $content['order_number']);
    }

    public function testCreateActionInvalidJson(): void
    {
        $request = new Request([], [], [], [], [], [], 'invalid json');

        $response = $this->controller->create($request, $this->entityManager);
        $this->assertIsString($response->getContent());
        $content = json_decode($response->getContent(), true);

        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertEquals('Invalid JSON data', $content['error']);
    }

    public function testCreateActionMissingRequiredField(): void
    {
        $request = new Request([], [], [], [], [], [], assert(
            is_string($json = json_encode(['description' => 'Test']))) ? $json : ''
        );

        $response = $this->controller->create($request, $this->entityManager);
        $this->assertIsString($response->getContent());
        $content = json_decode($response->getContent(), true);

        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertEquals('The "name" field is required', $content['error']);
    }

    public function testUpdateActionSuccess(): void
    {
        $group = new Groups();
        $group->setId(1);
        $group->setName('Old Name');

        $data = [
            'name' => 'Updated Name',
            'description' => 'Updated Description',
            'order_number' => 2
        ];

        $request = new Request([], [], [], [], [], [], assert(
            is_string($json = json_encode($data))) ? $json : ''
        );

        $response = $this->controller->update($request, $group, $this->entityManager);
        $this->assertIsString($response->getContent());
        $content = json_decode($response->getContent(), true);

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertEquals('Group updated successfully', $content['message']);
    }

    public function testUpdateActionInvalidJson(): void
    {
        $group = new Groups();
        $request = new Request([], [], [], [], [], [], 'invalid json');

        $response = $this->controller->update($request, $group, $this->entityManager);
        $this->assertIsString($response->getContent());
        $content = json_decode($response->getContent(), true);

        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertEquals('Invalid JSON data', $content['error']);
    }

    public function testDeleteActionSuccess(): void
    {
        $group = new Groups();
        $group->setId(1);

        $this->entityManager->expects($this->once())
            ->method('remove')
            ->with($group);

        $response = $this->controller->delete($group, $this->entityManager);

        $this->assertEquals(Response::HTTP_NO_CONTENT, $response->getStatusCode());
        $this->assertIsString($response->getContent());
        $content = json_decode($response->getContent(), true);
        $this->assertEquals('Group deleted successfully', $content['message']);
    }
}
