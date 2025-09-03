<?php

namespace App\Repository;

use App\Entity\Annotations;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Annotations>
 */
class AnnotationsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Annotations::class);
    }

    /**
     * Find annotations based on filters.
     *
     * @param int|null $resourcesId
     * @param int|null $userId
     * @return array<Annotations> Returns an array of Annotations objects
     */
    public function findByFilters(?int $resourcesId, ?int $userId): array
    {
        $qb = $this->createQueryBuilder('a')
            ->leftJoin('a.id_resources', 'r')
            ->leftJoin('a.id_user', 'u');

        if ($resourcesId) {
            $qb->andWhere('r.id = :resourcesId')
                ->setParameter('resourcesId', $resourcesId);
        }

        if ($userId) {
            $qb->andWhere('u.id = :userId')
                ->setParameter('userId', $userId);
        }

        $qb->orderBy('a.created_at', 'DESC');

        return $qb->getQuery()->getResult();
    }
}
