<?php

namespace App\Tests\Repository;

use App\Entity\Formation;
use App\Entity\Resources;
use App\Entity\SchoolYear;
use App\Entity\Semesters;
use App\Entity\Users;
use App\Repository\ResourcesRepository;
use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;
use Symfony\Bridge\Doctrine\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ResourcesRepositoryTest extends KernelTestCase
{
    private ?EntityManagerInterface $entityManager;
    private ResourcesRepository $repository;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        /** @var ManagerRegistry $doctrine */
        $doctrine = $kernel->getContainer()->get('doctrine');

        /** @var EntityManagerInterface $entityManager */
        $entityManager = $doctrine->getManager();
        $this->entityManager = $entityManager;

        $this->entityManager->beginTransaction();

        /** @var ResourcesRepository repository */
        $repository = $this->entityManager->getRepository(Resources::class);
        $this->repository = $repository;
    }

    public function testFindByFiltersWithAllParameters(): void
    {
        if ($this->entityManager === null) {
            throw new RuntimeException('Failed to initialize the entity manager.');
        }

        $schoolYear = new SchoolYear();
        $this->entityManager->persist($schoolYear);

        $formation = new Formation();
        $formation->setIdSchoolYear($schoolYear);
        $this->entityManager->persist($formation);

        $semester = new Semesters();
        $semester->setIdFormation($formation);
        $this->entityManager->persist($semester);

        $user = new Users();
        $user->setLogin('user_test');
        $user->setPassword('test');
        $this->entityManager->persist($user);

        $resource = new Resources();
        $resource->setIdUsers($user);
        $resource->setIdSemesters($semester);
        $this->entityManager->persist($resource);

        $this->entityManager->flush();

        $result = $this->repository->findByFilters(
            $user->getId(),
            $semester->getId(),
            $formation->getId(),
            $schoolYear->getId()
        );

        $this->assertCount(1, $result);
        $this->assertSame($resource, $result[0]);
    }

    public function testFindByFiltersWithPartialParameters(): void
    {
        if ($this->entityManager === null) {
            throw new RuntimeException('Failed to initialize the entity manager.');
        }

        $user = new Users();
        $user->setLogin('user_test');
        $user->setPassword('test');
        $this->entityManager->persist($user);

        $resource = new Resources();
        $resource->setIdUsers($user);
        $this->entityManager->persist($resource);

        $this->entityManager->flush();

        $result = $this->repository->findByFilters(
            $user->getId(),
            null,
            null,
            null
        );

        $this->assertCount(1, $result);
        $this->assertSame($resource, $result[0]);
    }

    public function testFindByFiltersWithNoMatches(): void
    {
        $result = $this->repository->findByFilters(999, 999, 999, 999);
        $this->assertEmpty($result);
    }

    protected function tearDown(): void
    {
        if (null !== $this->entityManager) {
            $this->entityManager->rollback();
            $this->entityManager->close();
            $this->entityManager = null;
        }

        parent::tearDown();
    }
}
