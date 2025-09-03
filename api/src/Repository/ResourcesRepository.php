<?php

namespace App\Repository;

use App\Entity\Resources;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Resources>
 */
class ResourcesRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Resources::class);
    }


    /**
     *  Find resources based on filters.
     *
     * @param int|null $userId
     * @param int|null $semesterId
     * @param int|null $formationId
     * @param int|null $schoolYearId
     * @return array<Resources> Returns an array of Resources objects
     */
    public function findByFilters(?int $userId, ?int $semesterId, ?int $formationId, ?int $schoolYearId): array
    {
        $qb = $this->createQueryBuilder('r')
            ->leftJoin('r.id_users', 'u')
            ->leftJoin('r.id_semesters', 's')
            ->leftJoin('s.id_formation', 'f')
            ->leftJoin('f.id_school_year', 'sy');

        if ($userId) {
            $qb->andWhere('u.id = :userId')
                ->setParameter('userId', $userId);
        }

        if ($semesterId) {
            $qb->andWhere('s.id = :semesterId')
                ->setParameter('semesterId', $semesterId);
        }

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
