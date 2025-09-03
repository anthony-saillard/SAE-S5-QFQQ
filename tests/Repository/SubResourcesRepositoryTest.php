<?php

namespace Repository;

use App\Entity\Resources;
use App\Entity\SubResources;
use App\Entity\Users;
use App\Repository\SubResourcesRepository;
use Doctrine\ORM\EntityManagerInterface;
use http\Exception\RuntimeException;
use Symfony\Bridge\Doctrine\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class SubResourcesRepositoryTest extends KernelTestCase
{
    private ?EntityManagerInterface $entityManager;
    private SubResourcesRepository $repository;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        /** @var ManagerRegistry $doctrine */
        $doctrine = $kernel->getContainer()->get('doctrine');

        /** @var EntityManagerInterface $entityManager */
        $entityManager = $doctrine->getManager();
        $this->entityManager = $entityManager;

        $this->entityManager->beginTransaction();

        /** @var SubResourcesRepository repository */
        $repository = $this->entityManager->getRepository(SubResources::class);
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

        $resource = new Resources();
        $this->entityManager->persist($resource);

        $subResource = new SubResources();
        $subResource->setIdResources($resource);
        $subResource->setIdUsers($user);
        $this->entityManager->persist($subResource);

        $this->entityManager->flush();

        $result = $this->repository->findByFilters(
            $resource->getId(),
            $user->getId()
        );

        $this->assertCount(1, $result);
        $this->assertSame($subResource, $result[0]);
    }

    public function testFindByFiltersWithPartialParameters(): void
    {
        if ($this->entityManager === null) {
            throw new RuntimeException('Failed to initialize the entity manager.');
        }

        $resource = new Resources();
        $this->entityManager->persist($resource);

        $subResource = new SubResources();
        $subResource->setIdResources($resource);
        $this->entityManager->persist($subResource);

        $this->entityManager->flush();

        $result = $this->repository->findByFilters(
            $resource->getId(),
            null
        );

        $this->assertCount(1, $result);
        $this->assertSame($subResource, $result[0]);
    }

    public function testFindByFiltersWithNoMatches(): void
    {
        $result = $this->repository->findByFilters(999, 999);
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
