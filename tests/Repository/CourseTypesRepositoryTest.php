<?php

namespace App\Tests\Repository;

use App\Entity\CourseTypes;
use App\Repository\CourseTypesRepository;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class CourseTypesRepositoryTest extends KernelTestCase
{
    private ?EntityManagerInterface $entityManager;
    private CourseTypesRepository $repository;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();

        /** @var ManagerRegistry $doctrine */
        $doctrine = $kernel->getContainer()->get('doctrine');

        /** @var EntityManagerInterface $entityManager */
        $entityManager = $doctrine->getManager();
        $this->entityManager = $entityManager;

        $this->entityManager->beginTransaction();

        /** @var CourseTypesRepository repository */
        $repository = $this->entityManager->getRepository(CourseTypes::class);
        $this->repository = $repository;
    }

    public function testConstruct(): void
    {
        $this->assertInstanceOf(CourseTypesRepository::class, $this->repository);
        $this->assertInstanceOf(ServiceEntityRepository::class, $this->repository);
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
