<?php

namespace App\Controller;

use App\Entity\Formation;
use App\Entity\SchoolYear;
use App\Repository\FormationRepository;
use App\Repository\SchoolYearRepository;
use App\Service\SchoolYearService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/formations', name: 'api_formations_')]
class FormationController extends AbstractController
{

    public function __construct(
        private readonly SchoolYearService $schoolYearService
    ) {
    }

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(Request $request, FormationRepository $repository, SchoolYearRepository $schoolYearRepository): JsonResponse
    {
        $schoolYear = $this->schoolYearService->getCurrentSchoolYear();

        if ($schoolYear instanceof JsonResponse) {
            return $schoolYear;
        }

        $formations = $repository->findBy(['id_school_year' => $schoolYear]);

        $data = array_map(fn($formation) => [
            'id' => $formation->getId(),
            'label' => $formation->getLabel(),
            'order_number' => $formation->getOrderNumber(),
            'id_school_year' => $formation->getIdSchoolYear()?->getId(),
            'pedagogical_interruptions' => $formation->getPedagogicalInterruptions()->map(fn($pi) => $pi->getId())->toArray(),
            'semesters' => $formation->getSemesters()->map(fn($semester) => $semester->getId())->toArray(),
            'groups' => $formation->getGroups()->map(fn($group) => [
                'id' => $group->getId(),
                'name' => $group->getName(),
            ])->toArray(),
        ], $formations);

        return $this->json($data, Response::HTTP_OK);
    }


    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Formation $formation): JsonResponse
    {
        $data = [
            'id' => $formation->getId(),
            'label' => $formation->getLabel(),
            'order_number' => $formation->getOrderNumber(),
            'id_school_year' => $formation->getIdSchoolYear()?->getId(),
            'pedagogical_interruptions' => $formation->getPedagogicalInterruptions()->map(fn($pi) => $pi->getId())->toArray(),
            'semesters' => $formation->getSemesters()->map(fn($semester) => $semester->getId())->toArray(),
            'groups' => $formation->getGroups()->map(fn($group) => [
                'id' => $group->getId(),
                'name' => $group->getName(),
            ])->toArray(),
        ];

        return $this->json($data, Response::HTTP_OK);
    }


    #[Route('/hours/{id}', name: 'show_hours', methods: ['GET'])]
    public function hours(Formation $formation, FormationRepository $repository): JsonResponse
    {
        $formationId = $formation->getId();
        if ($formationId === null) {
            return $this->json(['error' => 'Formation ID is null'], Response::HTTP_BAD_REQUEST);
        }

        $hoursData = $repository->getHoursByGroups($formationId);

        $subResourcesData = [];
        foreach ($hoursData as $row) {
            $subResourceId = $row['sub_resource_id'];

            if ($subResourceId === null) {
                continue;
            }

            if (!isset($subResourcesData[$subResourceId])) {
                $subResourcesData[$subResourceId] = [
                    'id' => $subResourceId,
                    'name' => $row['sub_resource_name'],
                    'course_types_hours' => []
                ];
            }

            $subResourcesData[$subResourceId]['course_types_hours'][] = [
                'course_type_id' => $row['course_type_id'],
                'course_type_name' => $row['course_type_name'],
                'total_hours' => (float)($row['total_hours'] ?? 0)
            ];
        }

        $data = [
            'id' => $formationId,
            'label' => $formation->getLabel(),
            'sub_resources' => array_values($subResourcesData)
        ];

        return $this->json($data, Response::HTTP_OK);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em, SchoolYearRepository $repoSchoolYears, SchoolYearService $schoolYearService): JsonResponse
    {
        /** @var array{label?: string|null, order_number?: int|null, id_school_year?: int|null}|null $data */
        $data = json_decode($request->getContent(), true);

        if ($data === null) {
            return $this->json(['error' => 'Invalid JSON data'], 400);
        }

        $formation = new Formation();
        $formation->setLabel($data['label'] ?? null);
        $formation->setOrderNumber(isset($data['order_number']) ? (int) $data['order_number'] : null);

        if (isset($data['id_school_year'])) {
            $schoolYear = $repoSchoolYears->find($data['id_school_year']);

            if (!$schoolYear) {
                return $this->json(['error' => 'School year not found'], 404);
            }
        } else {
            $schoolYear = $schoolYearService->getCurrentSchoolYear();
        }

        if ($schoolYear instanceof JsonResponse) {
            return $schoolYear;
        }

        $formation->setIdSchoolYear($schoolYear);

        $em->persist($formation);
        $em->flush();

        return $this->json([
            'id' => $formation->getId(),
            'label' => $formation->getLabel(),
            'order_number' => $formation->getOrderNumber(),
            'id_school_year' => $formation->getIdSchoolYear()?->getId(),
        ], Response::HTTP_CREATED);
    }


    #[Route('/{id}', name: 'update', methods: ['PUT'])]
    public function update(Request $request, Formation $formation, EntityManagerInterface $em): JsonResponse
    {
        /** @var array{label?: string|null, order_number?: int|null, id_school_year?: int|null}|null $data */
        $data = json_decode($request->getContent(), true);

        if ($data === null) {
            return $this->json(['error' => 'Invalid JSON data'], 400);
        }

        if (isset($data['label'])) {
            $formation->setLabel($data['label']);
        }

        if (isset($data['order_number'])) {
            $formation->setOrderNumber((int) $data['order_number']);
        }

        if (isset($data['id_school_year'])) {
            $schoolYear = $em->getRepository(SchoolYear::class)->find((int) $data['id_school_year']);
            $formation->setIdSchoolYear($schoolYear);
        }

        $em->flush();

        return $this->json(['message' => 'Formation updated successfully'],Response::HTTP_OK);
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(Formation $formation, EntityManagerInterface $em): JsonResponse
    {
        $em->remove($formation);
        $em->flush();

        return $this->json(['message' => 'Formation deleted successfully'], Response::HTTP_NO_CONTENT);
    }
}