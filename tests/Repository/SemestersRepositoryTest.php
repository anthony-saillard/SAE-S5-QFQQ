<?php

namespace App\Tests\Repository;

use App\Entity\Formation;
use App\Entity\SchoolYear;
use App\Entity\Semesters;
use App\Repository\SemestersRepository;
use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;
use Symfony\Bridge\Doctrine\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class SemestersRepositoryTest extends KernelTestCase
{
    private ?EntityManagerInterface $entityManager;
    private SemestersRepository $repository;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();

        /** @var ManagerRegistry $doctrine */
        $doctrine = $kernel->getContainer()->get('doctrine');

        /** @var EntityManagerInterface $entityManager */
        $entityManager = $doctrine->getManager();
        $this->entityManager = $entityManager;

        $this->entityManager->beginTransaction();

        /** @var SemestersRepository repository */
        $repository = $this->entityManager->getRepository(Semesters::class);
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

        $this->entityManager->flush();

        $result = $this->repository->findByFilters(
            $formation->getId(),
            $schoolYear->getId()
        );

        $this->assertCount(1, $result);
        $this->assertSame($semester, $result[0]);
    }

    public function testFindByFiltersWithFormationOnly(): void
    {
        if ($this->entityManager === null) {
            throw new RuntimeException('Failed to initialize the entity manager.');
        }

        $formation = new Formation();
        $this->entityManager->persist($formation);

        $semester = new Semesters();
        $semester->setIdFormation($formation);
        $this->entityManager->persist($semester);

        $this->entityManager->flush();

        $result = $this->repository->findByFilters(
            $formation->getId(),
            null
        );

        $this->assertCount(1, $result);
        $this->assertSame($semester, $result[0]);
    }

    public function testFindByFiltersWithSchoolYearOnly(): void
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

        $this->entityManager->flush();

        $result = $this->repository->findByFilters(
            null,
            $schoolYear->getId()
        );

        $this->assertCount(1, $result);
        $this->assertSame($semester, $result[0]);
    }

    public function testFindByFiltersWithNoParameters(): void
    {
        if ($this->entityManager === null) {
            throw new RuntimeException('Failed to initialize the entity manager.');
        }

        $semester = new Semesters();
        $this->entityManager->persist($semester);
        $this->entityManager->flush();

        $result = $this->repository->findByFilters(null, null);

        $this->assertNotEmpty($result);
        $this->assertContains($semester, $result);
    }

    public function testFindByFiltersWithNoMatches(): void
    {
        $result = $this->repository->findByFilters(999, 999);
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
