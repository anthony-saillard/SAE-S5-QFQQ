<?php

namespace App\Repository;

use App\Entity\Formation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Formation>
 */
class FormationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Formation::class);
    }

    /**
     * @return array<array{
     *     course_type_id: int|null,
     *     course_type_name: string|null,
     *     sub_resource_id: int|null,
     *     sub_resource_name: string|null,
     *     total_hours: float|null
     * }>
     */
    public function getHoursByGroups(int $formationId): array
    {
        return $this->createQueryBuilder('f')
            ->select(
                'ct.id as course_type_id',
                'ct.name as course_type_name',
                'sr.id as sub_resource_id',
                'sr.name as sub_resource_name',
                'r.total_hours as sub_resource_hours',
                'SUM(a.allocated_hours) as total_hours'
            )
            ->leftJoin('f.semesters', 's')
            ->leftJoin('s.resources', 'r')
            ->leftJoin('r.subResources', 'sr')
            ->leftJoin('sr.assignments', 'a')
            ->innerJoin('a.id_course_types', 'ct')
            ->where('f.id = :formationId')
            ->setParameter('formationId', $formationId)
            ->groupBy('ct.id', 'ct.name', 'sr.id', 'sr.name', 'r.total_hours')
            ->getQuery()
            ->getResult();

    }
}
