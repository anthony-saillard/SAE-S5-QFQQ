<?php

namespace App\Repository;

use App\Entity\SubResources;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SubResources>
 */
class SubResourcesRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SubResources::class);
    }

    /**
     *  Find sub-resources based on filters.
     *
     * @param int|null $resourceId
     * @param int|null $userId
     * @return array<SubResources> Returns an array of SubResources objects
     */
    public function findByFilters(?int $resourceId, ?int $userId): array
    {
        $qb = $this->createQueryBuilder('sr')
            ->leftJoin('sr.id_resources', 'r')
            ->leftJoin('sr.id_users', 'u');

        if ($resourceId) {
            $qb->andWhere('r.id = :resourceId')
                ->setParameter('resourceId', $resourceId);
        }

        if ($userId) {
            $qb->andWhere('u.id = :userId')
                ->setParameter('userId', $userId);
        }

        return $qb->getQuery()->getResult();
    }
}
