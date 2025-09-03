<?php

namespace App\Repository;

use App\Entity\CourseTeacher;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CourseTeacher>
 */
class CourseTeacherRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CourseTeacher::class);
    }

    /**
     * Trouve les CourseTeacher en fonction des filtres optionnels
     *
     * @param int|null $idGroup ID du groupe (optionnel)
     * @param int|null $idSubResource ID de la sous-ressource (optionnel)
     * @param int|null $idUser ID de l'utilisateur (optionnel)
     * @param int|null $schoolYearId ID de l'annee (optionnel)
     * @return array<CourseTeacher>
     */
    public function findByFilters(?int $idGroup = null, ?int $idSubResource = null, ?int $idUser = null, ?int $schoolYearId = null): array
    {
        $qb = $this->createQueryBuilder('ct')
            ->leftJoin('ct.id_sub_resource','sb')
            ->leftJoin('sb.id_resources', 'r')
            ->leftJoin('r.id_semesters', 's')
            ->leftJoin('s.id_formation', 'f')
            ->leftJoin('f.id_school_year', 'sy');

        if ($idGroup !== null) {
            $qb->andWhere('ct.id_groups = :id_group')
                ->setParameter('id_group', $idGroup);
        }

        if ($idSubResource !== null) {
            $qb->andWhere('ct.id_sub_resource = :id_sub_resource')
                ->setParameter('id_sub_resource', $idSubResource);
        }

        if ($idUser !== null) {
            $qb->andWhere('ct.id_user = :id_user')
                ->setParameter('id_user', $idUser);
        }


        if ($schoolYearId !== null) {
            $qb->andWhere('sy.id = :schoolYearId')
                ->setParameter('schoolYearId', $schoolYearId);
        }

        return $qb->getQuery()->getResult();
    }
}
