<?php

namespace App\Tests\Controller;

use App\Controller\CourseTeacherController;
use App\Entity\CourseTeacher;
use App\Entity\Groups;
use App\Entity\SchoolYear;
use App\Entity\SubResources;
use App\Entity\Users;
use App\Repository\CourseTeacherRepository;
use App\Repository\GroupsRepository;
use App\Repository\SubResourcesRepository;
use App\Repository\UsersRepository;
use App\Service\SchoolYearService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CourseTeacherControllerTest extends TestCase
{
    /** @var MockObject&EntityManagerInterface */
    private MockObject $entityManager;

    /** @var MockObject&CourseTeacherRepository */
    private MockObject $courseTeacherRepository;
    /** @var MockObject&SubResourcesRepository */
    private MockObject $subResourcesRepository;
    /** @var MockObject&UsersRepository */
    private MockObject $usersRepository;
    /** @var MockObject&GroupsRepository */
    private MockObject $groupsRepository;

    /** @var MockObject&SchoolYearService */
    private MockObject $schoolYearService;

    private CourseTeacherController $controller;
    private Container $container;

    protected function setUp(): void
    {
        $this->courseTeacherRepository = $this->getMockBuilder(CourseTeacherRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->entityManager = $this->getMockBuilder(EntityManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->schoolYearService = $this->getMockBuilder(SchoolYearService::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->subResourcesRepository = $this->createMock(SubResourcesRepository::class);
        $this->usersRepository = $this->createMock(UsersRepository::class);
        $this->groupsRepository = $this->createMock(GroupsRepository::class);

        $this->controller = new CourseTeacherController($this->schoolYearService);

        $this->container = new Container();
        $this->container->set('doctrine', $this->entityManager);

        $this->controller->setContainer($this->container);
    }

    public function testIndexSuccess(): void
    {
        $schoolYear = new SchoolYear();
        $schoolYear->setId(2024);

        $this->schoolYearService->expects($this->once())
            ->method('getCurrentSchoolYear')
            ->willReturn($schoolYear);

        $courseTeacher = new CourseTeacher();
        $subResource = new SubResources();
        $subResource->setId(1);
        $subResource->setName('Math');

        $user = new Users();
        $user->setId(1);
        $user->setFirstName('John');

        $group = new Groups();
        $group->setId(1);
        $group->setName('Group A');

        $courseTeacher->setIdSubResource($subResource);
        $courseTeacher->setIdUser($user);
        $courseTeacher->setIdGroups($group);

        $this->courseTeacherRepository->expects($this->once())
            ->method('findByFilters')
            ->with(null, null, null, 2024)
            ->willReturn([$courseTeacher]);

        $request = new Request();

        $response = $this->controller->index($request, $this->courseTeacherRepository);
        $this->assertInstanceOf(JsonResponse::class, $response);

        $content = $response->getContent();
        $this->assertIsString($content);

        $contentArray = json_decode($content, true);

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertCount(1, $contentArray);
        $this->assertEquals(1, $contentArray[0]['id_sub_resource']);
        $this->assertEquals('Math', $contentArray[0]['sub_resource_name']);
        $this->assertEquals(1, $contentArray[0]['id_user']);
        $this->assertEquals('John', $contentArray[0]['user_name']);
        $this->assertEquals(1, $contentArray[0]['id_group']);
        $this->assertEquals('Group A', $contentArray[0]['group_name']);
    }

    public function testIndexWithFilters(): void
    {
        $schoolYear = new SchoolYear();
        $schoolYear->setId(2024);

        $this->schoolYearService->expects($this->once())
            ->method('getCurrentSchoolYear')
            ->willReturn($schoolYear);

        $courseTeacher = new CourseTeacher();
        $subResource = new SubResources();
        $subResource->setId(1);
        $subResource->setName('Math');

        $user = new Users();
        $user->setId(1);
        $user->setFirstName('John');

        $group = new Groups();
        $group->setId(1);
        $group->setName('Group A');

        $courseTeacher->setIdSubResource($subResource);
        $courseTeacher->setIdUser($user);
        $courseTeacher->setIdGroups($group);

        $this->courseTeacherRepository->expects($this->once())
            ->method('findByFilters')
            ->with(1, 1, 1, 2024)
            ->willReturn([$courseTeacher]);

        $request = new Request(['id_group' => '1', 'id_sub_resource' => '1', 'id_user' => '1']);

        $response = $this->controller->index($request, $this->courseTeacherRepository);
        $this->assertInstanceOf(JsonResponse::class, $response);

        $content = $response->getContent();
        $this->assertIsString($content);

        $contentArray = json_decode($content, true);

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertCount(1, $contentArray);
    }

    public function testIndexWithSchoolYearError(): void
    {
        $this->schoolYearService->expects($this->once())
            ->method('getCurrentSchoolYear')
            ->willReturn(new JsonResponse(['error' => 'School year not found'], Response::HTTP_NOT_FOUND));

        $request = new Request();

        $response = $this->controller->index($request, $this->courseTeacherRepository);
        $this->assertInstanceOf(JsonResponse::class, $response);

        $content = $response->getContent();
        $this->assertIsString($content);

        $contentArray = json_decode($content, true);

        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
        $this->assertEquals('School year not found', $contentArray['error']);
    }

    public function testShowSuccess(): void
    {
        $courseTeacher = new CourseTeacher();
        $subResource = new SubResources();
        $subResource->setId(1);
        $subResource->setName('Math');

        $user = new Users();
        $user->setId(1);
        $user->setFirstName('John');

        $group = new Groups();
        $group->setId(1);
        $group->setName('Group A');

        $courseTeacher->setIdSubResource($subResource);
        $courseTeacher->setIdUser($user);
        $courseTeacher->setIdGroups($group);

        $response = $this->controller->show($courseTeacher);
        $this->assertInstanceOf(JsonResponse::class, $response);

        $content = $response->getContent();
        $this->assertIsString($content);

        $contentArray = json_decode($content, true);

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertEquals(1, $contentArray['id_sub_resource']);
        $this->assertEquals('Math', $contentArray['sub_resource_name']);
        $this->assertEquals(1, $contentArray['id_user']);
        $this->assertEquals('John', $contentArray['user_name']);
        $this->assertEquals(1, $contentArray['id_group']);
        $this->assertEquals('Group A', $contentArray['group_name']);
    }

    public function testCreateSuccess(): void
    {
        $subResource = new SubResources();
        $subResource->setId(1);

        $user = new Users();
        $user->setId(1);

        $group = new Groups();
        $group->setId(1);

        $this->subResourcesRepository->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($subResource);

        $this->usersRepository->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($user);

        $this->groupsRepository->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($group);

        $this->entityManager->expects($this->exactly(3))
            ->method('getRepository')
            ->willReturnMap([
                [SubResources::class, $this->subResourcesRepository],
                [Users::class, $this->usersRepository],
                [Groups::class, $this->groupsRepository]
            ]);

        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($this->callback(function ($courseTeacher) use ($subResource, $user, $group) {
                return $courseTeacher instanceof CourseTeacher
                    && $courseTeacher->getIdSubResource() === $subResource
                    && $courseTeacher->getIdUser() === $user
                    && $courseTeacher->getIdGroups() === $group;
            }));

        $this->entityManager->expects($this->once())
            ->method('flush');

        $requestData = [
            'id_sub_resource' => 1,
            'id_user' => 1,
            'id_group' => 1
        ];

        $request = new Request([], [], [], [], [], [], assert(
            is_string($json = json_encode($requestData))) ? $json : ''
        );

        $response = $this->controller->create($request, $this->entityManager);
        $this->assertInstanceOf(JsonResponse::class, $response);

        $content = $response->getContent();
        $this->assertIsString($content);

        $contentArray = json_decode($content, true);

        $this->assertEquals(Response::HTTP_CREATED, $response->getStatusCode());
    }

    public function testCreateInvalidJson(): void
    {
        $request = new Request([], [], [], [], [], [], 'invalid json');

        $response = $this->controller->create($request, $this->entityManager);
        $this->assertInstanceOf(JsonResponse::class, $response);

        $content = $response->getContent();
        $this->assertIsString($content);

        $contentArray = json_decode($content, true);

        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertEquals('Invalid JSON data', $contentArray['error']);
    }

    public function testCreateSubResourceNotFound(): void
    {
        $this->subResourcesRepository->expects($this->once())
            ->method('find')
            ->with(999)
            ->willReturn(null);

        $this->entityManager->expects($this->once())
            ->method('getRepository')
            ->with(SubResources::class)
            ->willReturn($this->subResourcesRepository);

        $requestData = [
            'id_sub_resource' => 999
        ];

        $request = new Request([], [], [], [], [], [], assert(
            is_string($json = json_encode($requestData))) ? $json : ''
        );

        $response = $this->controller->create($request, $this->entityManager);
        $this->assertInstanceOf(JsonResponse::class, $response);

        $content = $response->getContent();
        $this->assertIsString($content);

        $contentArray = json_decode($content, true);

        $this->assertEquals(404, $response->getStatusCode());
        $this->assertEquals('SubResource not found', $contentArray['error']);
    }

    public function testCreateUserNotFound(): void
    {
        $subResource = new SubResources();
        $subResource->setId(1);

        $this->subResourcesRepository->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($subResource);

        $this->usersRepository->expects($this->once())
            ->method('find')
            ->with(999)
            ->willReturn(null);

        $this->entityManager->expects($this->exactly(2))
            ->method('getRepository')
            ->willReturnMap([
                [SubResources::class, $this->subResourcesRepository],
                [Users::class, $this->usersRepository],
            ]);

        $requestData = [
            'id_sub_resource' => 1,
            'id_user' => 999
        ];

        $request = new Request([], [], [], [], [], [], assert(
            is_string($json = json_encode($requestData))) ? $json : ''
        );

        $response = $this->controller->create($request, $this->entityManager);
        $this->assertInstanceOf(JsonResponse::class, $response);

        $content = $response->getContent();
        $this->assertIsString($content);

        $contentArray = json_decode($content, true);

        $this->assertEquals(404, $response->getStatusCode());
        $this->assertEquals('User not found', $contentArray['error']);
    }

    public function testCreateGroupNotFound(): void
    {
        $subResource = new SubResources();
        $subResource->setId(1);

        $user = new Users();
        $user->setId(1);

        $this->subResourcesRepository->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($subResource);

        $this->usersRepository->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($user);

        $this->groupsRepository->expects($this->once())
            ->method('find')
            ->with(999)
            ->willReturn(null);

        $this->entityManager->expects($this->exactly(3))
            ->method('getRepository')
            ->willReturnMap([
                [SubResources::class, $this->subResourcesRepository],
                [Users::class, $this->usersRepository],
                [Groups::class, $this->groupsRepository]
            ]);

        $requestData = [
            'id_sub_resource' => 1,
            'id_user' => 1,
            'id_group' => 999
        ];

        $request = new Request([], [], [], [], [], [], assert(
            is_string($json = json_encode($requestData))) ? $json : ''
        );

        $response = $this->controller->create($request, $this->entityManager);
        $this->assertInstanceOf(JsonResponse::class, $response);

        $content = $response->getContent();
        $this->assertIsString($content);

        $contentArray = json_decode($content, true);

        $this->assertEquals(404, $response->getStatusCode());
        $this->assertEquals('Group not found', $contentArray['error']);
    }

    public function testUpdateSuccess(): void
    {
        $courseTeacher = new CourseTeacher();

        $subResource = new SubResources();
        $subResource->setId(1);

        $user = new Users();
        $user->setId(1);

        $group = new Groups();
        $group->setId(1);

        $this->subResourcesRepository->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($subResource);

        $this->usersRepository->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($user);

        $this->groupsRepository->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($group);

        $this->entityManager->expects($this->exactly(3))
            ->method('getRepository')
            ->willReturnMap([
                [SubResources::class, $this->subResourcesRepository],
                [Users::class, $this->usersRepository],
                [Groups::class, $this->groupsRepository]
            ]);

        $this->entityManager->expects($this->once())
            ->method('flush');

        $requestData = [
            'id_sub_resource' => 1,
            'id_user' => 1,
            'id_group' => 1
        ];

        $request = new Request([], [], [], [], [], [], assert(
            is_string($json = json_encode($requestData))) ? $json : ''
        );

        $response = $this->controller->update($request, $courseTeacher, $this->entityManager);
        $this->assertInstanceOf(JsonResponse::class, $response);

        $content = $response->getContent();
        $this->assertIsString($content);

        $contentArray = json_decode($content, true);

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertEquals('CourseTeacher updated successfully', $contentArray['message']);
    }

    public function testUpdateInvalidJson(): void
    {
        $courseTeacher = new CourseTeacher();

        $request = new Request([], [], [], [], [], [], 'invalid json');

        $response = $this->controller->update($request, $courseTeacher, $this->entityManager);
        $this->assertInstanceOf(JsonResponse::class, $response);

        $content = $response->getContent();
        $this->assertIsString($content);

        $contentArray = json_decode($content, true);

        $this->assertEquals(400, $response->getStatusCode());
        $this->assertEquals('Invalid JSON data', $contentArray['error']);
    }

    public function testUpdateSubResourceNotFound(): void
    {
        $courseTeacher = new CourseTeacher();

        $this->subResourcesRepository->expects($this->once())
            ->method('find')
            ->with(999)
            ->willReturn(null);

        $this->entityManager->expects($this->once())
            ->method('getRepository')
            ->with(SubResources::class)
            ->willReturn($this->subResourcesRepository);

        $requestData = [
            'id_sub_resource' => 999
        ];

        $request = new Request([], [], [], [], [], [], assert(
            is_string($json = json_encode($requestData))) ? $json : ''
        );

        $response = $this->controller->update($request, $courseTeacher, $this->entityManager);
        $this->assertInstanceOf(JsonResponse::class, $response);

        $content = $response->getContent();
        $this->assertIsString($content);

        $contentArray = json_decode($content, true);

        $this->assertEquals(404, $response->getStatusCode());
        $this->assertEquals('SubResource not found', $contentArray['error']);
    }

    public function testDeleteSuccess(): void
    {
        $courseTeacher = new CourseTeacher();

        $this->entityManager->expects($this->once())
            ->method('remove')
            ->with($courseTeacher);

        $this->entityManager->expects($this->once())
            ->method('flush');

        $response = $this->controller->delete($courseTeacher, $this->entityManager);
        $this->assertInstanceOf(JsonResponse::class, $response);

        $content = $response->getContent();
        $this->assertIsString($content);

        $contentArray = json_decode($content, true);

        $this->assertEquals(Response::HTTP_NO_CONTENT, $response->getStatusCode());
        $this->assertEquals('CourseTeacher deleted successfully', $contentArray['message']);
    }
}