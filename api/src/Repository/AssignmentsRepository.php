<?php

namespace App\Repository;

use App\Entity\Assignments;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Assignments>
 */
class AssignmentsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Assignments::class);
    }

    /**
     *  Find assignments based on filters.
     *
     * @param int|null $subResourceId
     * @param int|null $userId
     * @param int|null $courseTypeId
     * @param int|null $semesterId
     * @param string|null $dateStart
     * @param string|null $dateEnd
     * @return array<Assignments> Returns an array of Assignments objects
     */
    public function findByFilters(?int $subResourceId = null, ?int $userId = null, ?int $courseTypeId = null, ?int $semesterId = null, ?string $dateStart = null, ?string $dateEnd = null): array
    {
        $queryBuilder = $this->createQueryBuilder('a');

        if ($subResourceId !== null) {
            $queryBuilder->andWhere('a.id_sub_resources = :subResourceId')
                ->setParameter('subResourceId', $subResourceId);
        }

        if ($userId !== null) {
            $queryBuilder->andWhere('a.id_users = :userId')
                ->setParameter('userId', $userId);
        }

        if ($courseTypeId !== null) {
            $queryBuilder->andWhere('a.id_course_types = :courseTypeId')
                ->setParameter('courseTypeId', $courseTypeId);
        }

        if ($semesterId !== null) {
            $queryBuilder->join('a.id_sub_resources', 'sr')
                ->join('sr.id_resources', 'r')
                ->join('r.id_semesters', 's')
                ->andWhere('s.id = :semesterId')
                ->setParameter('semesterId', $semesterId);
        }

        if ($dateStart !== null && $dateEnd !== null) {
            $queryBuilder->andWhere('a.assignment_date BETWEEN :dateStart AND :dateEnd')
                ->setParameter('dateStart', $dateStart)
                ->setParameter('dateEnd', $dateEnd);
        }

        return $queryBuilder->getQuery()->getResult();
    }
}
