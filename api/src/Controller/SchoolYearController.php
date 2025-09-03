<?php

namespace App\Controller;

use App\Entity\CourseTypes;
use App\Entity\Formation;
use App\Entity\Groups;
use App\Entity\PedagogicalInterruptions;
use App\Entity\Resources;
use App\Entity\SchoolYear;
use App\Entity\Semesters;
use App\Entity\SubResources;
use App\Repository\SchoolYearRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/school-years', name: 'api_school_years_')]
class SchoolYearController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly SchoolYearRepository $schoolYearRepository,
        private readonly ValidatorInterface $validator
    ) {}

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $schoolYears = $this->schoolYearRepository->findAll();
        $data = array_map(fn($schoolYear) => [
            'id' => $schoolYear->getId(),
            'label' => $schoolYear->getLabel(),
            'current_school_year' => $schoolYear->isCurrentSchoolYear(),
        ], $schoolYears);

        return $this->json($data, Response::HTTP_OK);
    }

    #[Route('/current', name: 'current', methods: ['GET'])]
    public function getCurrentSchoolYear(): JsonResponse
    {
        $currentSchoolYear = $this->schoolYearRepository->findOneBy(['current_school_year' => true]);

        if (!$currentSchoolYear) {
            return $this->json([
                'status' => 'error',
                'message' => 'No current school year found',
            ], Response::HTTP_NO_CONTENT);
        }
        $data = [
            'id' => $currentSchoolYear->getId(),
            'label' => $currentSchoolYear->getLabel(),
            'current_school_year' => $currentSchoolYear->isCurrentSchoolYear(),
        ];
        return $this->json($data, Response::HTTP_OK);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $schoolYear = $this->schoolYearRepository->find($id);
        if (!$schoolYear) {
            return $this->json(['message' => 'School year not found',
            ], Response::HTTP_NO_CONTENT);
        }
        $data = [
            'id' => $schoolYear->getId(),
            'label' => $schoolYear->getLabel(),
            'current_school_year' => $schoolYear->isCurrentSchoolYear(),
        ];
        return $this->json($data, Response::HTTP_OK);
    }

    #[Route('/duplicate', name: 'duplicate', methods: ['POST'])]
    public function duplicate(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['label']) || !isset($data['sourceYearId']) || !isset($data['duplicationOptions'])) {
            return $this->json([
                'status' => 'error',
                'message' => 'Données manquantes: label, sourceYearId et duplicationOptions sont requis',
            ], Response::HTTP_BAD_REQUEST);
        }

        $sourceYear = $this->schoolYearRepository->find($data['sourceYearId']);
        if (!$sourceYear) {
            return $this->json([
                'status' => 'error',
                'message' => 'Année source non trouvée',
            ], Response::HTTP_NOT_FOUND);
        }

        $newSchoolYear = new SchoolYear();
        $newSchoolYear->setLabel($data['label']);
        $newSchoolYear->setCurrentSchoolYear(false);

        $this->entityManager->persist($newSchoolYear);
        $this->entityManager->flush();

        $options = $data['duplicationOptions'];

        if (isset($options['formations']) && $options['formations'] === true) {
            $this->duplicateFormations($sourceYear, $newSchoolYear, $options);
        }

        if (isset($options['ressources']) && $options['ressources'] === true) {
            $this->duplicateResources($sourceYear, $newSchoolYear, $options);
        }

        if (isset($options['groupes']) && $options['groupes'] === true) {
            $this->duplicateGroups($sourceYear, $newSchoolYear);
        }

        $this->entityManager->refresh($newSchoolYear);

        $responseData = [
            'id' => $newSchoolYear->getId(),
            'label' => $newSchoolYear->getLabel(),
            'current_school_year' => $newSchoolYear->isCurrentSchoolYear(),
        ];

        return $this->json([
            'status' => 'success',
            'data' => $responseData,
            'message' => 'Année scolaire dupliquée avec succès',
        ], Response::HTTP_CREATED);
    }

    /**
     * Duplique les groupes liés à l'année scolaire source vers l'année scolaire cible
     */
    private function duplicateGroups(SchoolYear $sourceYear, SchoolYear $targetYear): void
    {
        $groupMappings = [];
        $parentRelations = [];
        $processedSourceGroups = [];

        $courseTypeMappings = [];
        foreach ($sourceYear->getCourseTypes() as $sourceCourseType) {
            $newCourseType = $this->entityManager->getRepository(CourseTypes::class)->findOneBy([
                'name' => $sourceCourseType->getName(),
                'id_school_year' => $targetYear
            ]);

            if (!$newCourseType) {
                $newCourseType = new CourseTypes();
                $newCourseType->setName($sourceCourseType->getName());
                $newCourseType->setHourlyRate($sourceCourseType->getHourlyRate());
                $newCourseType->setIdSchoolYear($targetYear);

                $this->entityManager->persist($newCourseType);
            }

            $courseTypeMappings[$sourceCourseType->getId()] = $newCourseType;
        }

        $formationMappings = [];
        foreach ($sourceYear->getFormations() as $sourceFormation) {
            $newFormation = $this->entityManager->getRepository(Formation::class)->findOneBy([
                'label' => $sourceFormation->getLabel(),
                'id_school_year' => $targetYear
            ]);

            if ($newFormation) {
                $formationMappings[$sourceFormation->getId()] = $newFormation;
            }
        }

        $this->entityManager->flush();

        $allSourceGroups = [];

        foreach ($sourceYear->getCourseTypes() as $sourceCourseType) {
            foreach ($sourceCourseType->getGroups() as $sourceGroup) {
                $groupKey = $sourceGroup->getId();
                $allSourceGroups[$groupKey] = [
                    'group' => $sourceGroup,
                    'courseType' => $sourceCourseType,
                    'formation' => null
                ];
            }
        }

        foreach ($sourceYear->getFormations() as $sourceFormation) {
            foreach ($sourceFormation->getGroups() as $sourceGroup) {
                $groupKey = $sourceGroup->getId();

                if (isset($allSourceGroups[$groupKey])) {
                    $allSourceGroups[$groupKey]['formation'] = $sourceFormation;
                } else {
                    $allSourceGroups[$groupKey] = [
                        'group' => $sourceGroup,
                        'courseType' => null,
                        'formation' => $sourceFormation
                    ];
                }
            }
        }

        foreach ($allSourceGroups as $groupData) {
            $sourceGroup = $groupData['group'];
            $sourceCourseType = $groupData['courseType'];
            $sourceFormation = $groupData['formation'];

            $newGroup = new Groups();
            $newGroup->setName($sourceGroup->getName());
            $newGroup->setDescription($sourceGroup->getDescription());
            $newGroup->setOrderNumber($sourceGroup->getOrderNumber());

            if ($sourceCourseType && isset($courseTypeMappings[$sourceCourseType->getId()])) {
                $newGroup->setIdCourseTypes($courseTypeMappings[$sourceCourseType->getId()]);
            }

            if ($sourceFormation && isset($formationMappings[$sourceFormation->getId()])) {
                $newGroup->setIdFormation($formationMappings[$sourceFormation->getId()]);
            }

            $this->entityManager->persist($newGroup);
            $groupMappings[$sourceGroup->getId()] = $newGroup;

            if ($sourceGroup->getIdGroups()) {
                $parentRelations[] = [
                    'child' => $newGroup,
                    'parentId' => $sourceGroup->getIdGroups()->getId()
                ];
            }
        }

        $this->entityManager->flush();

        foreach ($parentRelations as $relation) {
            if (isset($groupMappings[$relation['parentId']])) {
                $relation['child']->setIdGroups($groupMappings[$relation['parentId']]);
            }
        }

        $this->entityManager->flush();
    }


    /**
     * Duplique les formations d'une année scolaire source vers une année scolaire cible
     *
     * @param SchoolYear $sourceYear L'année scolaire source
     * @param SchoolYear $targetYear L'année scolaire cible
     * @param array<string, bool|int|string> $options Options de duplication
     */
    private function duplicateFormations(SchoolYear $sourceYear, SchoolYear $targetYear, array $options): void
    {
        foreach ($sourceYear->getFormations() as $sourceFormation) {
            $newFormation = new Formation();
            $newFormation->setLabel($sourceFormation->getLabel());
            $newFormation->setOrderNumber($sourceFormation->getOrderNumber());
            $newFormation->setIdSchoolYear($targetYear);

            $this->entityManager->persist($newFormation);

            if (isset($options['semestres']) && $options['semestres'] === true) {
                $this->duplicateSemesters($sourceFormation, $newFormation, $options);
            }

            if (isset($options['periodesParticulieres']) && $options['periodesParticulieres'] === true) {
                $this->duplicatePeriodes($sourceFormation, $newFormation);
            }
        }

        $this->entityManager->flush();
    }

    /**
     * Duplique les semestres d'une formation source vers une formation cible
     *
     * @param Formation $sourceFormation La formation source
     * @param Formation $targetFormation La formation cible
     * @param array<string, bool|int|string> $options Options de duplication
     */
    private function duplicateSemesters(Formation $sourceFormation, Formation $targetFormation, array $options): void
    {
        foreach ($sourceFormation->getSemesters() as $sourceSemester) {
            $newSemester = new Semesters();
            $newSemester->setName($sourceSemester->getName());
            $newSemester->setStartDate($sourceSemester->getStartDate());
            $newSemester->setEndDate($sourceSemester->getEndDate());
            $newSemester->setOrderNumber($sourceSemester->getOrderNumber());
            $newSemester->setIdFormation($targetFormation);

            $this->entityManager->persist($newSemester);

            if (isset($options['ressources']) && $options['ressources'] === true) {
                $this->duplicateResourcesForSemester($sourceSemester, $newSemester);
            }
        }
    }

    /**
     * Duplique les périodes particulières (interruptions pédagogiques) d'une formation
     */
    private function duplicatePeriodes(Formation $sourceFormation, Formation $targetFormation): void
    {
        foreach ($sourceFormation->getPedagogicalInterruptions() as $sourceInterruption) {
            $newInterruption = new PedagogicalInterruptions();
            $newInterruption->setName($sourceInterruption->getName());
            $newInterruption->setStartDate($sourceInterruption->getStartDate());
            $newInterruption->setEndDate($sourceInterruption->getEndDate());
            $newInterruption->setIdFormation($targetFormation);

            $this->entityManager->persist($newInterruption);
        }
    }

    /**
     * Duplique les ressources d'un semestre source vers un semestre cible
     */
    private function duplicateResourcesForSemester(Semesters $sourceSemester, Semesters $targetSemester): void
    {
        foreach ($sourceSemester->getResources() as $sourceResource) {
            $newResource = new Resources();
            $newResource->setIdentifier($sourceResource->getIdentifier());
            $newResource->setName($sourceResource->getName());
            $newResource->setDescription($sourceResource->getDescription());
            $newResource->setIdSemesters($targetSemester);

            if ($sourceResource->getIdUsers()) {
                $newResource->setIdUsers($sourceResource->getIdUsers());
            }

            $this->entityManager->persist($newResource);

            $this->duplicateSubResources($sourceResource, $newResource);
        }
    }

    /**
     * Duplique les sous-ressources d'une ressource source vers une ressource cible
     */
    private function duplicateSubResources(Resources $sourceResource, Resources $newResource): void
    {
        foreach ($sourceResource->getSubResources() as $sourceSubResource) {
            $newSubResource = new SubResources();
            $newSubResource->setName($sourceSubResource->getName());
            $newSubResource->setIdResources($newResource);

            if ($sourceSubResource->getIdUsers()) {
                $newSubResource->setIdUsers($sourceSubResource->getIdUsers());
            }

            $this->entityManager->persist($newSubResource);
        }
    }

    /**
     * Duplique les types de cours (ressources) directement liés à l'année scolaire
     *
     * @param SchoolYear $sourceYear L'année scolaire source
     * @param SchoolYear $targetYear L'année scolaire cible
     * @param array<string, bool|int|string> $options Options de duplication
     */
    private function duplicateResources(SchoolYear $sourceYear, SchoolYear $targetYear, array $options): void
    {
        foreach ($sourceYear->getCourseTypes() as $sourceCourseType) {
            $newCourseType = new CourseTypes();
            $newCourseType->setName($sourceCourseType->getName());
            $newCourseType->setHourlyRate($sourceCourseType->getHourlyRate());
            $newCourseType->setIdSchoolYear($targetYear);

            $this->entityManager->persist($newCourseType);
        }

        $this->entityManager->flush();
    }


    #[Route('/{id}/set-current', name: 'set_current', methods: ['PUT'])]
    public function setCurrent(int $id): JsonResponse
    {
        $schoolYear = $this->schoolYearRepository->find($id);

        if (!$schoolYear) {
            return $this->json([
                'status' => 'error',
                'message' => 'School year not found',
            ], Response::HTTP_NOT_FOUND);
        }

        $this->entityManager->createQueryBuilder()
            ->update(SchoolYear::class, 'sy')
            ->set('sy.current_school_year', ':false')
            ->setParameter('false', false)
            ->getQuery()
            ->execute();

        $schoolYear->setCurrentSchoolYear(true);
        $this->entityManager->persist($schoolYear);
        $this->entityManager->flush();

        $this->entityManager->refresh($schoolYear);

        $data = [
            'id' => $schoolYear->getId(),
            'label' => $schoolYear->getLabel(),
            'current_school_year' => $schoolYear->isCurrentSchoolYear(),
        ];

        return $this->json($data, Response::HTTP_OK);
    }


    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!is_array($data) || !isset($data['label']) || !is_string($data['label'])) {
            return $this->json([
                'status' => 'error',
                'message' => 'Invalid data: "label" must be a string',
            ], Response::HTTP_BAD_REQUEST);
        }

        $schoolYear = new SchoolYear();
        $schoolYear->setLabel($data['label']);
        $schoolYear->setCurrentSchoolYear($data['current_school_year'] ?? false);

        $errors = $this->validator->validate($schoolYear);
        if (count($errors) > 0) {
            return $this->json([
                'message' => 'Validation failed',
                'errors' => array_map(fn($violation) => $violation->getMessage(), iterator_to_array($errors)),
            ], Response::HTTP_BAD_REQUEST);
        }

        $this->entityManager->persist($schoolYear);
        $this->entityManager->flush();

        $data = [
            'id' => $schoolYear->getId(),
            'label' => $schoolYear->getLabel(),
            'current_school_year' => $schoolYear->isCurrentSchoolYear(),
        ];

        return $this->json($data, Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'update', methods: ['PUT'])]
    public function update(Request $request, int $id): JsonResponse
    {
        $schoolYear = $this->schoolYearRepository->find($id);

        if (!$schoolYear) {
            return $this->json([
                'status' => 'error',
                'message' => 'School year not found',
            ], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);
        if (!is_array($data) || (isset($data['label']) && !is_string($data['label']))) {
            return $this->json([
                'status' => 'error',
                'message' => 'Invalid data: "label" must be a string',
            ], Response::HTTP_BAD_REQUEST);
        }

        if (isset($data['label'])) {
            if (!is_string($data['label'])) {
                return $this->json([
                    'status' => 'error',
                    'message' => '"label" must be a string',
                ], Response::HTTP_BAD_REQUEST);
            }
            $schoolYear->setLabel($data['label']);
        }

        $errors = $this->validator->validate($schoolYear);
        if (count($errors) > 0) {
            return $this->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => array_map(fn($violation) => $violation->getMessage(), iterator_to_array($errors)),
            ], Response::HTTP_BAD_REQUEST);
        }

        $this->entityManager->flush();

        return $this->json([
            'status' => 'success',
            'data' => [
                'id' => $schoolYear->getId(),
                'label' => $schoolYear->getLabel(),
            ],
            'message' => 'School year updated successfully',
        ], Response::HTTP_OK);
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $schoolYear = $this->schoolYearRepository->find($id);

        if (!$schoolYear) {
            return $this->json([
                'status' => 'error',
                'message' => 'School year not found',
            ], Response::HTTP_NOT_FOUND);
        }

        try {
            $this->entityManager->remove($schoolYear);
            $this->entityManager->flush();

            return $this->json([
                'status' => 'success',
                'message' => 'School year deleted successfully',
            ], Response::HTTP_NO_CONTENT);
        } catch (\Exception $e) {
            return $this->json([
                'status' => 'error',
                'message' => 'Cannot delete school year due to existing references',
            ], Response::HTTP_BAD_REQUEST);
        }
    }
}