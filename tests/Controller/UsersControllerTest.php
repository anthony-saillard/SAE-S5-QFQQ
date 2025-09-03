<?php

namespace App\Tests\Controller;

use App\Controller\UsersController;
use App\Entity\Users;
use App\Repository\UsersRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class UsersControllerTest extends TestCase
{
    /** @var MockObject&EntityManagerInterface */
    private MockObject $entityManager;
    private UsersController $controller;

    /** @var MockObject&UsersRepository */
    private MockObject $usersRepository;
    private Container $container;

    protected function setUp(): void
    {
        $this->usersRepository = $this->getMockBuilder(UsersRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->entityManager = $this->getMockBuilder(EntityManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->entityManager->method('getRepository')->willReturn($this->usersRepository);

        $this->controller = new UsersController($this->entityManager);

        $this->container = new Container();
        $this->container->set('doctrine', $this->entityManager);

        $this->controller->setContainer($this->container);
    }

    public function testGetUserByIdSuccess(): void
    {
        $user = new Users();
        $user->setId(1)
            ->setLogin('testuser')
            ->setLastName('Doe')
            ->setFirstName('John')
            ->setRole('ROLE_USER')
            ->setPhone('0123456789')
            ->setEmail('john@example.com');

        $this->usersRepository->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($user);

        $response = $this->controller->getUserById(1, $this->usersRepository);
        $this->assertInstanceOf(Response::class, $response);

        $content = $response->getContent();
        $this->assertIsString($content, 'Response should be a string');

        $contentArray = json_decode($content, true);

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertEquals('testuser', $contentArray['login']);
        $this->assertEquals('Doe', $contentArray['last_name']);
        $this->assertEquals('John', $contentArray['first_name']);
        $this->assertEquals('ROLE_USER', $contentArray['role']);
        $this->assertEquals('0123456789', $contentArray['phone']);
        $this->assertEquals('john@example.com', $contentArray['email']);
    }

    public function testGetUserByIdNotFound(): void
    {
        $this->usersRepository->expects($this->once())
            ->method('find')
            ->with(999)
            ->willReturn(null);

        $response = $this->controller->getUserById(999, $this->usersRepository);
        $this->assertInstanceOf(Response::class, $response);

        $content = $response->getContent();
        $this->assertIsString($content, 'Response should be a string');

        $contentArray = json_decode($content, true);

        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
        $this->assertEquals('User not found', $contentArray['error']);
    }

    public function testUpdateUserSuccess(): void
    {
        $user = new Users();
        $user->setId(1)
            ->setLogin('oldlogin')
            ->setLastName('OldName')
            ->setFirstName('OldFirst');

        $this->usersRepository->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($user);

        $data = [
            'login' => 'newlogin',
            'last_name' => 'NewName',
            'first_name' => 'NewFirst',
            'role' => 'ROLE_ADMIN',
            'phone' => '9876543210',
            'email' => 'new@example.com'
        ];

        $request = new Request([], [], [], [], [], [], assert(
            is_string($json = json_encode($data))) ? $json : ''
        );

        $response = $this->controller->updateUser($request, 1, $this->usersRepository);
        $this->assertInstanceOf(Response::class, $response);

        $content = $response->getContent();
        $this->assertIsString($content, 'Response should be a string');

        $contentArray = json_decode($content, true);

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertEquals('User updated successfully', $contentArray['message']);

        $this->assertEquals('newlogin', $user->getLogin());
        $this->assertEquals('NewName', $user->getLastName());
        $this->assertEquals('NewFirst', $user->getFirstName());
        $this->assertEquals('ROLE_ADMIN', $user->getRole());
        $this->assertEquals('9876543210', $user->getPhone());
        $this->assertEquals('new@example.com', $user->getEmail());
    }

    public function testUpdateUserNotFound(): void
    {
        $this->usersRepository->expects($this->once())
            ->method('find')
            ->with(999)
            ->willReturn(null);

        $request = new Request([], [], [], [], [], [], assert(
            is_string($json = json_encode(['login' => 'newlogin']))) ? $json : ''
        );

        $response = $this->controller->updateUser($request, 999, $this->usersRepository);
        $this->assertInstanceOf(Response::class, $response);

        $content = $response->getContent();
        $this->assertIsString($content, 'Response should be a string');

        $contentArray = json_decode($content, true);

        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
        $this->assertEquals('User not found', $contentArray['error']);
    }

    public function testUpdateUserInvalidData(): void
    {
        $user = new Users();
        $this->usersRepository->method('find')->willReturn($user);

        $request = new Request([], [], [], [], [], [], 'invalid json');

        $response = $this->controller->updateUser($request, 1, $this->usersRepository);
        $this->assertInstanceOf(Response::class, $response);

        $content = $response->getContent();
        $this->assertIsString($content, 'Response should be a string');

        $contentArray = json_decode($content, true);

        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertEquals('Le format des données envoyées est invalide.', $contentArray['error']);
    }

    public function testDeleteUserSuccess(): void
    {
        $user = new Users();
        $user->setId(1);
        $user->setLogin('testuser');

        $this->usersRepository->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($user);

        $this->entityManager->expects($this->once())
            ->method('flush');

        $response = $this->controller->deleteUser(1, $this->usersRepository);
        $this->assertInstanceOf(Response::class, $response);

        $content = $response->getContent();
        $this->assertIsString($content, 'Response should be a string');

        $contentArray = json_decode($content, true);

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertEquals('User deleted successfully', $contentArray['message']);
    }

    public function testDeleteUserNotFound(): void
    {
        $this->usersRepository->expects($this->once())
            ->method('find')
            ->with(999)
            ->willReturn(null);

        $response = $this->controller->deleteUser(999, $this->usersRepository);
        $this->assertInstanceOf(Response::class, $response);

        $content = $response->getContent();
        $this->assertIsString($content, 'Response should be a string');

        $contentArray = json_decode($content, true);

        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
        $this->assertEquals('User not found', $contentArray['error']);
    }

    public function testDeleteUserError(): void
    {
        $user = new Users();
        $user->setId(1);
        $user->setLogin('testuser');

        $this->usersRepository->method('find')->willReturn($user);
        $this->entityManager->method('flush')->willThrowException(new \Exception('DB Error'));

        $response = $this->controller->deleteUser(1, $this->usersRepository);
        $this->assertInstanceOf(Response::class, $response);

        $content = $response->getContent();
        $this->assertIsString($content, 'Response should be a string');

        $contentArray = json_decode($content, true);

        $this->assertEquals(Response::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());
        $this->assertEquals('Failed to delete user', $contentArray['error']);
        $this->assertEquals('DB Error', $contentArray['details']);
    }
}