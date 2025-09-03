<?php

namespace App\Tests\Repository;

use App\Entity\Assignments;
use App\Entity\CourseTypes;
use App\Entity\SubResources;
use App\Entity\Users;
use App\Repository\AssignmentsRepository;
use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;
use Symfony\Bridge\Doctrine\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class AssignmentsRepositoryTest extends KernelTestCase
{
    private ?EntityManagerInterface $entityManager;
    private AssignmentsRepository $repository;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();

        /** @var ManagerRegistry $doctrine */
        $doctrine = $kernel->getContainer()->get('doctrine');

        /** @var EntityManagerInterface $entityManager */
        $entityManager = $doctrine->getManager();
        $this->entityManager = $entityManager;

        $this->entityManager->beginTransaction();

        /** @var AssignmentsRepository repository */
        $repository = $this->entityManager->getRepository(Assignments::class);
        $this->repository = $repository;
    }

    public function testFindByFiltersWithAllParameters(): void
    {
        if ($this->entityManager === null) {
            throw new RuntimeException('Failed to initialize the entity manager.');
        }

        $user = new Users();
        $user->setLogin("user_test");
        $user->setPassword("test");
        $this->entityManager->persist($user);

        $subResource = new SubResources();
        $this->entityManager->persist($subResource);

        $courseType = new courseTypes();
        $this->entityManager->persist($courseType);

        $assignment = new Assignments();
        $assignment->setIdUsers($user);
        $assignment->setIdSubResources($subResource);
        $assignment->setIdCourseTypes($courseType);
        $this->entityManager->persist($assignment);

        $this->entityManager->flush();

        $result = $this->repository->findByFilters(
            $subResource->getId(),
            $user->getId(),
            $courseType->getId()
        );

        $this->assertCount(1, $result);
        $this->assertSame($assignment, $result[0]);
    }

    public function testFindByFiltersWithSubResourceOnly(): void
    {
        if ($this->entityManager === null) {
            throw new RuntimeException('Failed to initialize the entity manager.');
        }

        $subResource = new SubResources();
        $this->entityManager->persist($subResource);

        $assignment = new Assignments();
        $assignment->setIdSubResources($subResource);
        $this->entityManager->persist($assignment);

        $this->entityManager->flush();

        $result = $this->repository->findByFilters(
            $subResource->getId(),
            null,
            null
        );

        $this->assertCount(1, $result);
        $this->assertSame($assignment, $result[0]);
    }

    public function testFindByFiltersWithUserOnly(): void
    {
        if ($this->entityManager === null) {
            throw new RuntimeException('Failed to initialize the entity manager.');
        }

        $user = new Users();
        $user->setLogin("user_test");
        $user->setPassword("test");
        $this->entityManager->persist($user);

        $assignment = new Assignments();
        $assignment->setIdUsers($user);
        $this->entityManager->persist($assignment);

        $this->entityManager->flush();

        $result = $this->repository->findByFilters(
            null,
            $user->getId(),
            null
        );

        $this->assertCount(1, $result);
        $this->assertSame($assignment, $result[0]);
    }

    public function testFindByFiltersWithCourseTypesOnly(): void
    {
        if ($this->entityManager === null) {
            throw new RuntimeException('Failed to initialize the entity manager.');
        }

        $courseType = new courseTypes();
        $this->entityManager->persist($courseType);

        $assignment = new Assignments();
        $assignment->setIdCourseTypes($courseType);
        $this->entityManager->persist($assignment);

        $this->entityManager->flush();

        $result = $this->repository->findByFilters(
            null,
            null,
            $courseType->getId()
        );

        $this->assertCount(1, $result);
        $this->assertSame($assignment, $result[0]);
    }

    public function testFindByFiltersWithNoMatches(): void
    {
        $result = $this->repository->findByFilters(999, 999, 999);
        $this->assertEmpty($result);
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