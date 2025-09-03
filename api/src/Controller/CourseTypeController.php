<?php

namespace App\Controller;

use App\Entity\CourseTypes;
use App\Repository\CourseTypesRepository;
use App\Repository\SchoolYearRepository;
use App\Service\SchoolYearService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/api/course-types', name: 'api_course_type_')]
class CourseTypeController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly CourseTypesRepository $courseTypesRepository,
        private readonly SchoolYearService $schoolYearService
    ) {
    }

    #[Route('', name: 'index', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $schoolYear = $this->schoolYearService->getCurrentSchoolYear();

        if ($schoolYear instanceof JsonResponse) {
            return $schoolYear;
        }

        $courseTypes = $this->courseTypesRepository->findBy(['id_school_year' => $schoolYear]);

        $data = array_map(fn(CourseTypes $courseType) => [
            'id' => $courseType->getId(),
            'name' => $courseType->getName(),
            'hourly_rate' => $courseType->getHourlyRate(),
            'school_year_id' => $courseType->getIdSchoolYear()?->getId(),
            'groups' => $courseType->getGroups()->map(fn($group) => $group->getId())->toArray(),
        ], $courseTypes);

        return $this->json($data, Response::HTTP_OK);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(CourseTypes $courseType): JsonResponse
    {
        return $this->json([
            'id' => $courseType->getId(),
            'name' => $courseType->getName(),
            'hourly_rate' => $courseType->getHourlyRate(),
            'school_year_id' => $courseType->getIdSchoolYear()?->getId(),
            'groups' => $courseType->getGroups()->map(fn($group) => $group->getId())->toArray(),
        ]);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request, SchoolYearRepository $schoolYearRepository): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!is_array($data)) {
            return $this->json(['error' => 'Invalid JSON data'], Response::HTTP_BAD_REQUEST);
        }

        if (!isset($data['name']) || !is_string($data['name']) ||
            !isset($data['hourly_rate']) || !is_numeric($data['hourly_rate'])) {
            return $this->json([
                'error' => 'Name (string) and hourly rate (number)'
            ], Response::HTTP_BAD_REQUEST);
        }

        $schoolYear = $this->schoolYearService->getCurrentSchoolYear();

        if ($schoolYear instanceof JsonResponse) {
            return $schoolYear;
        }


        $courseType = new CourseTypes();
        $courseType->setName($data['name']);
        $courseType->setHourlyRate((float) $data['hourly_rate']);
        $courseType->setIdSchoolYear($schoolYear);

        try {
            $this->entityManager->persist($courseType);
            $this->entityManager->flush();

            return $this->json([
                'id' => $courseType->getId(),
                'name' => $courseType->getName(),
                'hourly_rate' => $courseType->getHourlyRate(),
                'id_school_year' => $courseType->getIdSchoolYear()?->getId(),
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return $this->json(
                ['error' => 'Failed to create course type', 'message' => $e->getMessage()],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    #[Route('/{id}', name: 'update', methods: ['PUT'])]
    public function update(Request $request, CourseTypes $courseType): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!is_array($data) || empty($data)) {
            return $this->json(['error' => 'Invalid or empty data provided'], Response::HTTP_BAD_REQUEST);
        }

        if (isset($data['name'])) {
            if (!is_string($data['name'])) {
                return $this->json(['error' => 'Name must be a string'], Response::HTTP_BAD_REQUEST);
            }
            $courseType->setName($data['name']);
        }

        if (isset($data['hourly_rate'])) {
            if (!is_numeric($data['hourly_rate'])) {
                return $this->json(['error' => 'Hourly rate must be a number'], Response::HTTP_BAD_REQUEST);
            }
            $courseType->setHourlyRate((float) $data['hourly_rate']);
        }

        try {
            $this->entityManager->flush();

            return $this->json([
                'id' => $courseType->getId(),
                'name' => $courseType->getName(),
                'hourly_rate' => $courseType->getHourlyRate(),
            ]);
        } catch (\Exception $e) {
            return $this->json(
                ['error' => 'Failed to update course type', 'message' => $e->getMessage()],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(CourseTypes $courseType): JsonResponse
    {
        try {
            $this->entityManager->remove($courseType);
            $this->entityManager->flush();

            return $this->json(['message' => 'Course type deleted successfully'], Response::HTTP_OK);
        } catch (\Exception $e) {
            return $this->json(
                ['error' => 'Failed to delete course type', 'message' => $e->getMessage()],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}