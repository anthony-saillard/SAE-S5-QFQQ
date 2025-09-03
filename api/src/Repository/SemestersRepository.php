<?php

namespace App\Repository;

use App\Entity\Semesters;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Semesters>
 */
class SemestersRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Semesters::class);
    }


    /**
     *  Find semesters based on filters.
     *
     * @param int|null $formationId
     * @param int|null $schoolYearId
     * @return array<Semesters> Returns an array of Semesters objects
     */
    public function findByFilters(?int $formationId, ?int $schoolYearId): array
    {
        $qb = $this->createQueryBuilder('s')
            ->leftJoin('s.id_formation', 'f')
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
