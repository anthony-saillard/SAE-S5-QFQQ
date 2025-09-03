<?php

namespace App\Tests\Repository;

use App\Entity\SchoolYear;
use App\Repository\SchoolYearRepository;
use App\Repository\UsersRepository;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class SchoolYearRepositoryTest extends KernelTestCase
{
    private ?EntityManagerInterface $entityManager;
    private SchoolYearRepository $repository;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();

        /** @var ManagerRegistry $doctrine */
        $doctrine = $kernel->getContainer()->get('doctrine');

        /** @var EntityManagerInterface $entityManager */
        $entityManager = $doctrine->getManager();
        $this->entityManager = $entityManager;

        $this->entityManager->beginTransaction();

        /** @var SchoolYearRepository repository */
        $repository = $entityManager->getRepository(SchoolYear::class);
        $this->repository = $repository;
    }

    public function testConstruct(): void
    {
        $this->assertInstanceOf(SchoolYearRepository::class, $this->repository);
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
