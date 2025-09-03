<?php

namespace App\Repository;

use App\Entity\Groups;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Groups>
 */
class GroupsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Groups::class);
    }

    /**
     * Find groups by filters.
     *
     * @param int|null $formationId The formation ID to filter by.
     * @param int|null $schoolYearId The school year ID to filter by.
     * @return Groups[] The list of groups matching the filters.
     */
    public function findByFilters(?int $formationId, ?int $schoolYearId): array
    {
        $qb = $this->createQueryBuilder('g')
            ->leftJoin('g.id_formation', 'f')
            ->leftJoin('f.id_school_year', 'sy');

        if ($formationId) {
            $qb->andWhere('f.id = :formationId')
                ->setParameter('formationId', $formationId);
        }

        if ($schoolYearId) {
            $qb->andWhere('sy.id = :schoolYearId')
                ->setParameter('schoolYearId', $schoolYearId);
        }

        return $qb->getQuery()->getResult();
    }
}
