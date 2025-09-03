<?php

namespace App\Tests\Controller;

use App\Controller\SecurityController;
use App\Entity\Users;
use App\Repository\UsersRepository;
use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use League\OAuth2\Server\Repositories\UserRepositoryInterface;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\Request;

class SecurityControllerTest extends KernelTestCase
{
    private ?EntityManagerInterface $entityManager = null;
    private Container $container;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->container = self::getContainer();
        $this->entityManager = $this->container->get('doctrine.orm.entity_manager') instanceof EntityManagerInterface
            ? $this->container->get('doctrine.orm.entity_manager')
            : throw new \RuntimeException('EntityManager not found');

        $this->entityManager->beginTransaction();
    }

    public function testLoginSuccess(): void
    {
        if ($this->entityManager === null) {
            throw new RuntimeException('Failed to initialize test entity manager.');
        }

        $user = new Users();
        $user->setLogin("user");
        $hashedPassword = password_hash("password", PASSWORD_BCRYPT);
        $user->setPassword($hashedPassword);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $repo = $this->createMock(UsersRepository::class);
        $repo->expects(self::once())
            ->method("findOneBy")
            ->willReturn($user);

        $this->container->set(UserRepositoryInterface::class, $repo);
        /** @var SecurityController $controller */
        $controller = $this->container->get(SecurityController::class);

        $request = new Request([], [], [], [], [], [], assert(
                is_string($json = json_encode([
                'login' => 'user',
                'password' => 'password'
            ]))) ? $json : null
        );
        $response = $controller->login($request, $repo);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertIsString($response->getContent());
        $this->assertJson($response->getContent());
    }

    public function testLoginWithWrongPassword(): void
    {
        if ($this->entityManager === null) {
            throw new RuntimeException('Failed to initialize test entity manager.');
        }

        $user = new Users();
        $user->setLogin("user");
        $hashedPassword = password_hash("correct_password", PASSWORD_BCRYPT);
        $user->setPassword($hashedPassword);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $repo = $this->createMock(UsersRepository::class);
        $repo->expects(self::once())
            ->method("findOneBy")
            ->willReturn($user);

        $this->container->set(UserRepositoryInterface::class, $repo);
        /** @var SecurityController $controller */
        $controller = $this->container->get(SecurityController::class);

        $request = new Request([], [], [], [], [], [], assert(
            is_string($json = json_encode([
                'login' => 'user',
                'password' => 'wrong_password'
            ]))) ? $json : null
        );

        $response = $controller->login($request, $repo);

        $this->assertSame(401, $response->getStatusCode());
        $this->assertIsString($response->getContent());
        $this->assertJson($response->getContent());
        $responseData = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('error', $responseData);
        $this->assertStringContainsString('Invalid credentials', $responseData['error']);
    }

    public function testLoginWithNonExistentUser(): void
    {
        // Mock du repository pour retourner null (utilisateur non trouvé)
        $repo = $this->createMock(UsersRepository::class);
        $repo->expects(self::once())
            ->method("findOneBy")
            ->willReturn(null);

        $this->container->set(UserRepositoryInterface::class, $repo);
        /** @var SecurityController $controller */
        $controller = $this->container->get(SecurityController::class);

        $request = new Request([], [], [], [], [], [], assert(
            is_string($json = json_encode([
                'login' => 'non_existent_user',
                'password' => 'any_password'
            ]))) ? $json : null
        );

        $response = $controller->login($request, $repo);

        $this->assertSame(401, $response->getStatusCode());
        $this->assertIsString($response->getContent());
        $this->assertJson($response->getContent());
        $responseData = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('error', $responseData);
        $this->assertStringContainsString('Invalid login', $responseData['error']);
    }

    public function testLoginWithMalformedRequest(): void
    {
        $repo = $this->createMock(UsersRepository::class);
        $repo->expects(self::never())
            ->method("findOneBy");

        $this->container->set(UserRepositoryInterface::class, $repo);
        /** @var SecurityController $controller */
        $controller = $this->container->get(SecurityController::class);

        $request = new Request([], [], [], [], [], [], 'invalid json{');
        $response = $controller->login($request, $repo);

        $this->assertSame(400, $response->getStatusCode());
        $this->assertIsString($response->getContent());
        $this->assertJson($response->getContent());
        $responseData = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('error', $responseData);
    }

    public function testLoginWithMissingCredentials(): void
    {
        $repo = $this->createMock(UsersRepository::class);
        $repo->expects(self::never())
            ->method("findOneBy");

        $this->container->set(UserRepositoryInterface::class, $repo);
        /** @var SecurityController $controller */
        $controller = $this->container->get(SecurityController::class);

        $request = new Request([], [], [], [], [], [], assert(
            is_string($json = json_encode([
                'login' => 'user'
                // password manquant
            ]))) ? $json : null
        );

        $response = $controller->login($request, $repo);

        $this->assertSame(400, $response->getStatusCode());
        $this->assertIsString($response->getContent());
        $this->assertJson($response->getContent());
        $responseData = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('error', $responseData);
        $this->assertStringContainsString('Missing credentials', $responseData['error']);
    }

    protected function tearDown(): void
    {
        if ($this->entityManager !== null) {
            $this->entityManager->rollback();
            $this->entityManager->close();
            $this->entityManager = null;
        }

        parent::tearDown();
    }
}
