<?php

namespace App\Repository;

use App\Entity\PedagogicalInterruptions;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PedagogicalInterruptions>
 */
class PedagogicalInterruptionsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PedagogicalInterruptions::class);
    }

    /**
     *  Find pedagogicalInterruptions based on filters.
     *
     * @param int|null $formationId
     * @param int|null $schoolYearId
     * @return array<PedagogicalInterruptions> Returns an array of PedagogicalInterruptions objects
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
