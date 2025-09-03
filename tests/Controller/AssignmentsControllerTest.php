<?php

namespace App\Tests\Controller;

use App\Controller\AssignmentsController;
use App\Entity\Assignments;
use App\Entity\SubResources;
use App\Entity\Users;
use App\Entity\CourseTypes;
use App\Repository\AssignmentsRepository;
use App\Repository\SubResourcesRepository;
use App\Repository\UsersRepository;
use App\Repository\CourseTypesRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\InputBag;

class AssignmentsControllerTest extends TestCase
{
    /** @var MockObject&EntityManagerInterface */
    private MockObject $entityManager;
    private AssignmentsController $controller;

    /** @var MockObject&AssignmentsRepository */
    private MockObject $assignmentsRepository;

    /** @var MockObject&SubResourcesRepository */
    private MockObject $subResourcesRepository;

    /** @var MockObject&UsersRepository */
    private MockObject $usersRepository;

    /** @var MockObject&CourseTypesRepository */
    private MockObject $courseTypesRepository;

    private Container $container;

    protected function setUp(): void
    {
        $this->assignmentsRepository = $this->getMockBuilder(AssignmentsRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->subResourcesRepository = $this->getMockBuilder(SubResourcesRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->usersRepository = $this->getMockBuilder(UsersRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->courseTypesRepository = $this->getMockBuilder(CourseTypesRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->entityManager = $this->getMockBuilder(EntityManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->entityManager->method('getRepository')
            ->willReturnMap([
                [Assignments::class, $this->assignmentsRepository],
                [SubResources::class, $this->subResourcesRepository],
                [Users::class, $this->usersRepository],
                [CourseTypes::class, $this->courseTypesRepository]
            ]);

        $this->controller = new AssignmentsController();

        $this->container = new Container();
        $this->container->set('doctrine', $this->entityManager);

        $this->controller->setContainer($this->container);
    }

    public function testListAssignments(): void
    {

        $request = new Request([
            'id_user' => '1',
            'id_sub_resource' => '2',
            'id_course_type' => '3',
            'id_semester' => '4'
        ]);


        $assignment1 = new Assignments();
        $this->setEntityId($assignment1, 1);
        $assignment1->setAllocatedHours(20.5);
        $assignment1->setAssignmentDate(new \DateTime('2023-01-15'));
        $assignment1->setAnnotation('Test annotation');

        $subResource1 = new SubResources();
        $this->setEntityId($subResource1, 1);
        $assignment1->setIdSubResources($subResource1);

        $user1 = new Users();
        $this->setEntityId($user1, 1);
        $assignment1->setIdUsers($user1);

        $courseType1 = new CourseTypes();
        $this->setEntityId($courseType1, 1);
        $assignment1->setIdCourseTypes($courseType1);

        $assignment2 = new Assignments();
        $this->setEntityId($assignment2, 2);
        $assignment2->setAllocatedHours(15.0);
        $assignment2->setAssignmentDate(new \DateTime('2023-01-15'));
        $assignment2->setAnnotation('Test annotation');

        $subResource2 = new SubResources();
        $this->setEntityId($subResource2, 1);
        $assignment2->setIdSubResources($subResource2);

        $user2 = new Users();
        $this->setEntityId($user2, 1);
        $assignment2->setIdUsers($user2);

        $courseType2 = new CourseTypes();
        $this->setEntityId($courseType2, 1);
        $assignment2->setIdCourseTypes($courseType2);

        $assignments = [$assignment1, $assignment2];


        $this->assignmentsRepository->expects($this->once())
            ->method('findByFilters')
            ->with(2, 1, 3, 4)
            ->willReturn($assignments);


        $response = $this->controller->list($request, $this->assignmentsRepository);


        $this->assertInstanceOf(Response::class, $response);

        $content = $response->getContent();
        $this->assertIsString($content);

        // Fix #1: Check if content is not false before decoding
        $contentArray = json_decode($content, true);
        $this->assertIsArray($contentArray);
        $this->assertCount(2, $contentArray);
        $this->assertEquals(1, $contentArray[0]['id']);
        $this->assertEquals(20.5, $contentArray[0]['allocated_hours']);

    }

    public function testShowAssignment(): void
    {
        $assignment = new Assignments();
        $this->setEntityId($assignment, 1);
        $assignment->setAllocatedHours(20.5);
        $assignment->setAssignmentDate(new \DateTime('2023-01-15'));
        $assignment->setAnnotation('Test annotation');

        $subResource = new SubResources();
        $this->setEntityId($subResource, 1);
        $assignment->setIdSubResources($subResource);

        $user = new Users();
        $this->setEntityId($user, 1);
        $assignment->setIdUsers($user);

        $courseType = new CourseTypes();
        $this->setEntityId($courseType, 1);
        $assignment->setIdCourseTypes($courseType);

        // Utiliser ReflectionAPI pour simuler les notifications puisque setNotifications n'existe pas
        $notifications = new ArrayCollection([]);
        $reflection = new \ReflectionClass($assignment);
        $property = $reflection->getProperty('notifications');
        $property->setAccessible(true);
        $property->setValue($assignment, $notifications);

        $response = $this->controller->show($assignment);

        $this->assertInstanceOf(Response::class, $response);

        $content = $response->getContent();
        $this->assertIsString($content);


        $contentArray = json_decode($content, true);

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertEquals(1, $contentArray['id']);
        $this->assertEquals(20.5, $contentArray['allocated_hours']);
        $this->assertEquals('2023-01-15', $contentArray['assignment_date']);
        $this->assertEquals('Test annotation', $contentArray['annotation']);
        $this->assertEquals(1, $contentArray['id_sub_resources']);
        $this->assertEquals(1, $contentArray['id_users']);
        $this->assertEquals(1, $contentArray['id_course_type']);
        $this->assertEquals([], $contentArray['notifications']);

    }

    public function testCreateAssignmentSuccess(): void
    {

        $subResource = $this->createMock(SubResources::class);
        $subResource->method('getId')->willReturn(1);

        $user = $this->createMock(Users::class);
        $user->method('getId')->willReturn(1);

        $courseType = $this->createMock(CourseTypes::class);
        $courseType->method('getId')->willReturn(1);

        $this->subResourcesRepository->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($subResource);

        $this->usersRepository->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($user);

        $this->courseTypesRepository->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($courseType);


        $data = [
            'allocated_hours' => 20.5,
            'assignment_date' => '2023-01-15',
            'annotation' => 'Test annotation',
            'id_sub_resources' => 1,
            'id_users' => 1,
            'id_course_type' => 1
        ];

        $jsonContent = json_encode($data);
        if ($jsonContent === false) {
            $this->fail('Failed to encode JSON data');
        }
        $request = new Request([], [], [], [], [], [], $jsonContent);


        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(Assignments::class));

        $this->entityManager->expects($this->once())
            ->method('flush');


        $response = $this->controller->create(
            $request,
            $this->entityManager,
            $this->subResourcesRepository,
            $this->usersRepository,
            $this->courseTypesRepository
        );


        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(Response::HTTP_CREATED, $response->getStatusCode());

        $content = $response->getContent();
        $this->assertIsString($content);

        $contentArray = json_decode($content, true);
        $this->assertArrayHasKey('id', $contentArray);
        $this->assertArrayHasKey('allocated_hours', $contentArray);

    }

    public function testCreateAssignmentInvalidJson(): void
    {
        $request = new Request([], [], [], [], [], [], 'invalid json');

        $response = $this->controller->create(
            $request,
            $this->entityManager,
            $this->subResourcesRepository,
            $this->usersRepository,
            $this->courseTypesRepository
        );

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());

        $content = $response->getContent();
        $this->assertIsString($content);

        $contentArray = json_decode($content, true);
        $this->assertEquals('Invalid JSON data', $contentArray['error']);

    }

    public function testCreateAssignmentInvalidSubResource(): void
    {
        $data = [
            'allocated_hours' => 20.5,
            'id_sub_resources' => 999 // Non-existent ID
        ];

        $jsonContent = json_encode($data);
        if ($jsonContent === false) {
            $this->fail('Failed to encode JSON data');
        }
        $request = new Request([], [], [], [], [], [], $jsonContent);

        $this->subResourcesRepository->expects($this->once())
            ->method('find')
            ->with(999)
            ->willReturn(null);

        $response = $this->controller->create(
            $request,
            $this->entityManager,
            $this->subResourcesRepository,
            $this->usersRepository,
            $this->courseTypesRepository
        );

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());

        $content = $response->getContent();

        // Fix #5: Check if content is not false before decoding
        if ($content !== false) {
            $contentArray = json_decode($content, true);
            $this->assertEquals('Invalid sub resource ID', $contentArray['error']);
        } else {
            $this->fail('Response content is false');
        }
    }

    public function testUpdateAssignmentSuccess(): void
    {
        $assignment = new Assignments();


        $subResource = $this->createMock(SubResources::class);
        $user = $this->createMock(Users::class);
        $courseType = $this->createMock(CourseTypes::class);

        $this->subResourcesRepository->expects($this->once())
            ->method('find')
            ->with(2)
            ->willReturn($subResource);

        $this->usersRepository->expects($this->once())
            ->method('find')
            ->with(2)
            ->willReturn($user);

        $this->courseTypesRepository->expects($this->once())
            ->method('find')
            ->with(2)
            ->willReturn($courseType);


        $data = [
            'allocated_hours' => 25.5,
            'assignment_date' => '2023-02-15',
            'annotation' => 'Updated annotation',
            'id_sub_resources' => 2,
            'id_users' => 2,
            'id_course_type' => 2
        ];

        $jsonContent = json_encode($data);
        if ($jsonContent === false) {
            $this->fail('Failed to encode JSON data');
        }
        $request = new Request([], [], [], [], [], [], $jsonContent);


        // No need to set expectations on real objects


        $this->entityManager->expects($this->once())
            ->method('flush');

        // Execute controller method
        $response = $this->controller->update(
            $request,
            $assignment,
            $this->entityManager,
            $this->subResourcesRepository,
            $this->usersRepository,
            $this->courseTypesRepository
        );

        // Assert response
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        $content = $response->getContent();

        // Fix: Check if content is not false before decoding
        if ($content !== false) {
            $contentArray = json_decode($content, true);
            $this->assertEquals('Assignment updated successfully', $contentArray['message']);
        } else {
            $this->fail('Response content is false');
        }
    }

    public function testUpdateAssignmentInvalidJson(): void
    {
        $assignment = new Assignments();
        $request = new Request([], [], [], [], [], [], 'invalid json');

        $response = $this->controller->update(
            $request,
            $assignment,
            $this->entityManager,
            $this->subResourcesRepository,
            $this->usersRepository,
            $this->courseTypesRepository
        );

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());

        $content = $response->getContent();

        // Fix: Check if content is not false before decoding
        if ($content !== false) {
            $contentArray = json_decode($content, true);
            $this->assertEquals('Invalid JSON data', $contentArray['error']);
        } else {
            $this->fail('Response content is false');
        }
    }

    public function testDeleteAssignmentSuccess(): void
    {
        $assignment = new Assignments();

        $this->entityManager->expects($this->once())
            ->method('remove')
            ->with($assignment);

        $this->entityManager->expects($this->once())
            ->method('flush');

        $response = $this->controller->delete($assignment, $this->entityManager);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(Response::HTTP_NO_CONTENT, $response->getStatusCode());

        $content = $response->getContent();

        // Fix: Check if content is not false before decoding
        if ($content !== false) {
            $contentArray = json_decode($content, true);
            $this->assertEquals('Assignment deleted successfully', $contentArray['message']);
        } else {
            $this->fail('Response content is false');
        }
    }


    private function setEntityId(object $entity, int $id): void
    {
        $reflection = new \ReflectionClass($entity);
        $property = $reflection->getProperty('id');
        $property->setAccessible(true);
        $property->setValue($entity, $id);
    }
}