<?php

namespace App\Tests\Repository;

use App\Entity\Formation;
use App\Entity\Groups;
use App\Entity\SchoolYear;
use App\Repository\GroupsRepository;
use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;
use Symfony\Bridge\Doctrine\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class GroupsRepositoryTest extends KernelTestCase
{
    private ?EntityManagerInterface $entityManager;
    private GroupsRepository $repository;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();

        /** @var ManagerRegistry $doctrine */
        $doctrine = $kernel->getContainer()->get('doctrine');

        /** @var EntityManagerInterface $entityManager */
        $entityManager = $doctrine->getManager();
        $this->entityManager = $entityManager;

        $this->entityManager->beginTransaction();

        /** @var GroupsRepository repository */
        $repository = $this->entityManager->getRepository(Groups::class);
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
        $formation->setLabel("formation test");
        $formation->setIdSchoolYear($schoolYear);
        $this->entityManager->persist($formation);

        $group = new Groups();
        $group->setIdFormation($formation);
        $formation->addGroup($group);
        $this->entityManager->persist($group);

        $this->entityManager->flush();

        $result = $this->repository->findByFilters(
            $formation->getId(),
            $schoolYear->getId()
        );

        $this->assertCount(1, $result);
        $this->assertSame($group, $result[0]);
    }

    public function testFindByFiltersWithFormationOnly(): void
    {
        if ($this->entityManager === null) {
            throw new RuntimeException('Failed to initialize the entity manager.');
        }

        $schoolYear = new SchoolYear();
        $this->entityManager->persist($schoolYear);

        $formation = new Formation();
        $formation->setIdSchoolYear($schoolYear);
        $this->entityManager->persist($formation);

        $group = new Groups();
        $group->setIdFormation($formation);
        $this->entityManager->persist($group);

        $this->entityManager->flush();

        $result = $this->repository->findByFilters(
            $formation->getId(),
            null
        );

        $this->assertCount(1, $result);
        $this->assertSame($group, $result[0]);
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

        $group = new Groups();
        $group->setIdFormation($formation);
        $this->entityManager->persist($group);

        $this->entityManager->flush();

        $result = $this->repository->findByFilters(
            null,
            $schoolYear->getId()
        );

        $this->assertCount(1, $result);
        $this->assertSame($group, $result[0]);
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