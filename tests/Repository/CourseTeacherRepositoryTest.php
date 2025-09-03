<?php

namespace App\Tests\Repository;

use App\Entity\CourseTeacher;
use App\Entity\Formation;
use App\Entity\Groups;
use App\Entity\Resources;
use App\Entity\SchoolYear;
use App\Entity\Semesters;
use App\Entity\SubResources;
use App\Entity\Users;
use App\Repository\CourseTeacherRepository;
use App\Service\SchoolYearService;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use RuntimeException;
use Symfony\Bridge\Doctrine\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class
CourseTeacherRepositoryTest extends KernelTestCase
{
    private ?EntityManagerInterface $entityManager;
    private CourseTeacherRepository $repository;
    /** @var MockObject&SchoolYearService $schoolYearService */
    private MockObject $schoolYearService;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();

        /** @var ManagerRegistry $doctrine */
        $doctrine = $kernel->getContainer()->get('doctrine');

        /** @var EntityManagerInterface $entityManager */
        $entityManager = $doctrine->getManager();
        $this->entityManager = $entityManager;

        $this->entityManager->beginTransaction();

        /** @var CourseTeacherRepository repository */
        $repository = $this->entityManager->getRepository(CourseTeacher::class);
        $this->repository = $repository;

        $this->schoolYearService = $this->getMockBuilder(SchoolYearService::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testConstruct(): void
    {
        $this->assertInstanceOf(CourseTeacherRepository::class, $this->repository);
        $this->assertInstanceOf(ServiceEntityRepository::class, $this->repository);
    }

    public function testFindByFiltersWithAllParameters(): void
    {
        if ($this->entityManager === null) {
            throw new RuntimeException('Failed to initialize the entity manager.');
        }

        $groups = new Groups();
        $this->entityManager->persist($groups);

        $schoolYear = new SchoolYear();
        $this->entityManager->persist($schoolYear);
        $this->schoolYearService->method('getCurrentSchoolYear')->willReturn($schoolYear);

        $formation = new Formation();
        $formation->setIdSchoolYear($schoolYear);
        $this->entityManager->persist($formation);

        $semester = new Semesters();
        $semester->setIdFormation($formation);
        $this->entityManager->persist($semester);

        $resource = new Resources();
        $resource->setIdSemesters($semester);
        $this->entityManager->persist($resource);

        $subResource = new SubResources();
        $subResource->setIdResources($resource);
        $this->entityManager->persist($subResource);

        $user = new Users();
        $user->setLogin('user_login');
        $user->setPassword('user_password');
        $this->entityManager->persist($user);

        $courseTeacher = new CourseTeacher();
        $courseTeacher->setIdGroups($groups);
        $courseTeacher->setIdUser($user);
        $courseTeacher->setIdSubResource($subResource);
        $this->entityManager->persist($courseTeacher);

        $this->entityManager->flush();

        $result = $this->repository->findByFilters(
            $groups->getId(),
            $subResource->getId(),
            $user->getId(),
            $schoolYear->getId()
        );

        $this->assertCount(1, $result);
        $this->assertSame($courseTeacher, $result[0]);
    }

    public function testFindByFiltersWithGroupsOnly(): void
    {
        if ($this->entityManager === null) {
            throw new RuntimeException('Failed to initialize the entity manager.');
        }

        $groups = new Groups();
        $this->entityManager->persist($groups);

        $courseTeacher = new CourseTeacher();
        $courseTeacher->setIdGroups($groups);
        $this->entityManager->persist($courseTeacher);

        $this->entityManager->flush();

        $result = $this->repository->findByFilters(
            $groups->getId(),
            null,
            null,
            null
        );

        $this->assertCount(1, $result);
        $this->assertSame($courseTeacher, $result[0]);
    }

    public function testFindByFiltersWithSubResourceOnly(): void
    {
        if ($this->entityManager === null) {
            throw new RuntimeException('Failed to initialize the entity manager.');
        }

        $subResource = new SubResources();
        $this->entityManager->persist($subResource);

        $courseTeacher = new CourseTeacher();
        $courseTeacher->setIdSubResource($subResource);
        $this->entityManager->persist($courseTeacher);

        $this->entityManager->flush();

        $result = $this->repository->findByFilters(
            null,
            $subResource->getId(),
            null,
            null
        );

        $this->assertCount(1, $result);
        $this->assertSame($courseTeacher, $result[0]);
    }

    public function testFindByFiltersWithUserOnly(): void
    {
        if ($this->entityManager === null) {
            throw new RuntimeException('Failed to initialize the entity manager.');
        }

        $user = new Users();
        $user->setLogin('user_login');
        $user->setPassword('user_password');
        $this->entityManager->persist($user);

        $courseTeacher = new CourseTeacher();
        $courseTeacher->setIdUser($user);
        $this->entityManager->persist($courseTeacher);

        $this->entityManager->flush();

        $result = $this->repository->findByFilters(
            null,
            null,
            $user->getId(),
            null
        );

        $this->assertCount(1, $result);
        $this->assertSame($courseTeacher, $result[0]);
    }

    public function testFindByFiltersWithSchoolYearOnly(): void
    {
        if ($this->entityManager === null) {
            throw new RuntimeException('Failed to initialize the entity manager.');
        }

        $schoolYear = new SchoolYear();
        $this->entityManager->persist($schoolYear);
        $this->schoolYearService->method('getCurrentSchoolYear')->willReturn($schoolYear);

        $formation = new Formation();
        $formation->setIdSchoolYear($schoolYear);
        $this->entityManager->persist($formation);

        $semester = new Semesters();
        $semester->setIdFormation($formation);
        $this->entityManager->persist($semester);

        $resource = new Resources();
        $resource->setIdSemesters($semester);
        $this->entityManager->persist($resource);

        $subResource = new SubResources();
        $subResource->setIdResources($resource);
        $this->entityManager->persist($subResource);

        $courseTeacher = new CourseTeacher();
        $courseTeacher->setIdSubResource($subResource);
        $this->entityManager->persist($courseTeacher);

        $this->entityManager->flush();

        $result = $this->repository->findByFilters(
            null,
            null,
            null,
            $schoolYear->getId(),
        );

        $this->assertCount(1, $result);
        $this->assertSame($courseTeacher, $result[0]);
    }

    public function testFindByFiltersWithNoParameters(): void
    {
        if ($this->entityManager === null) {
            throw new RuntimeException('Failed to initialize the entity manager.');
        }

        $courseTeacher = new CourseTeacher();
        $this->entityManager->persist($courseTeacher);
        $this->entityManager->flush();

        $result = $this->repository->findByFilters(null, null);

        $this->assertNotEmpty($result);
        $this->assertContains($courseTeacher, $result);
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
