<?php

namespace App\Tests\Command;

use App\Command\CreateUserCommand;
use App\Entity\Users;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class CreateUserCommandTest extends TestCase
{
    /** @var MockObject&EntityManagerInterface */
    private MockObject $entityManager;
    /** @var MockObject&UserPasswordHasherInterface */
    private MockObject $passwordHasher;
    /** @var MockObject&ValidatorInterface */
    private MockObject $validator;
    private CommandTester $commandTester;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->passwordHasher = $this->createMock(UserPasswordHasherInterface::class);
        $this->validator = $this->createMock(ValidatorInterface::class);

        $application = new Application();
        $command = new CreateUserCommand(
            $this->entityManager,
            $this->passwordHasher,
            $this->validator
        );

        $application->add($command);

        $this->commandTester = new CommandTester($command);
    }

    public function testExecuteSuccessWithMinimalData(): void
    {
        $this->passwordHasher
            ->expects($this->once())
            ->method('hashPassword')
            ->willReturn('hashed_password');

        $this->validator
            ->expects($this->once())
            ->method('validate')
            ->willReturn(new ConstraintViolationList());

        $this->entityManager
            ->expects($this->once())
            ->method('persist')
            ->with($this->callback(function (Users $user) {
                return $user->getLogin() === 'testuser'
                    && $user->getPassword() === 'hashed_password'
                    && $user->getRole() === 'ROLE_USER';
            }));

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $this->commandTester->setInputs([
            'testuser',
            'password123',
            '',
            '',
            '',
            ''
        ]);

        $exitCode = $this->commandTester->execute([]);

        $this->assertEquals(0, $exitCode);
        $display = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Utilisateur créé avec succès !', $display);
    }

    public function testExecuteSuccessWithFullData(): void
    {
        $this->passwordHasher
            ->expects($this->once())
            ->method('hashPassword')
            ->willReturn('hashed_password');

        $this->validator
            ->expects($this->once())
            ->method('validate')
            ->willReturn(new ConstraintViolationList());

        $this->entityManager
            ->expects($this->once())
            ->method('persist')
            ->with($this->callback(function (Users $user) {
                return $user->getLogin() === 'testuser'
                    && $user->getPassword() === 'hashed_password'
                    && $user->getFirstName() === 'John'
                    && $user->getLastName() === 'Doe'
                    && $user->getEmail() === 'john@example.com'
                    && $user->getRole() === 'ROLE_ADMIN';
            }));

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $this->commandTester->setInputs([
            'testuser',
            'password123',
            'John',
            'Doe',
            'john@example.com',
            'ROLE_ADMIN'
        ]);

        $exitCode = $this->commandTester->execute([]);

        $this->assertEquals(0, $exitCode);
        $display = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Utilisateur créé avec succès !', $display);
    }

    public function testExecuteWithValidationErrors(): void
    {
        $violation = $this->createMock(ConstraintViolation::class);
        $violation->method('getMessage')
            ->willReturn('Le login est requis');

        $violationList = new ConstraintViolationList([$violation]);

        $this->validator
            ->expects($this->once())
            ->method('validate')
            ->willReturn($violationList);

        $this->passwordHasher
            ->expects($this->once())
            ->method('hashPassword')
            ->willReturn('hashed_password');

        $this->entityManager
            ->expects($this->never())
            ->method('persist');

        $this->entityManager
            ->expects($this->never())
            ->method('flush');

        $this->commandTester->setInputs([
            'testuser',
            'password123',
            '',
            '',
            '',
            ''
        ]);

        $exitCode = $this->commandTester->execute([]);

        $this->assertEquals(1, $exitCode);
        $display = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Erreur de validation : Le login est requis', $display);
    }

    public function testExecuteWithDatabaseError(): void
    {
        $this->passwordHasher
            ->expects($this->once())
            ->method('hashPassword')
            ->willReturn('hashed_password');

        $this->validator
            ->expects($this->once())
            ->method('validate')
            ->willReturn(new ConstraintViolationList());

        $this->entityManager
            ->method('flush')
            ->willThrowException(new \Exception('Database error'));

        $this->commandTester->setInputs([
            'testuser',
            'password123',
            '',
            '',
            '',
            ''
        ]);

        $this->commandTester->execute([]);

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Database error', $output);
    }
}