<?php

namespace App\Controller;

use App\Entity\Semesters;
use App\Repository\FormationRepository;
use App\Repository\SemestersRepository;
use App\Service\SchoolYearService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/semesters', name: 'api_semesters_')]
class SemestersController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly SchoolYearService $schoolYearService
    ) {
    }

    #[Route('', name: 'index', methods: ['GET'])]
    public function list(
        Request $request,
        SemestersRepository $semestersRepository
    ): JsonResponse {
        $formationId = $request->query->get('id_formation');
        $schoolYearId = $request->query->get('id_school_year');

        if ($schoolYearId === null) {
            $schoolYear = $this->schoolYearService->getCurrentSchoolYear();

            if ($schoolYear instanceof JsonResponse) {
                return $schoolYear;
            }
            $schoolYearId = $schoolYear->getId();
        }

        $semesters = $semestersRepository->findByFilters(
            $formationId ? (int) $formationId : null,
            $schoolYearId ? (int) $schoolYearId : null
        );

        $data = array_map(fn(Semesters $semester) => [
            'id' => $semester->getId(),
            'name' => $semester->getName(),
            'start_date' => $semester->getStartDate()?->format('Y-m-d'),
            'end_date' => $semester->getEndDate()?->format('Y-m-d'),
            'order_number' => $semester->getOrderNumber(),
            'id_formation' => $semester->getIdFormation()?->getId(),
        ], $semesters);

        return $this->json($data, Response::HTTP_OK);
    }


    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Semesters $semester): JsonResponse
    {
        return $this->json([
            'id' => $semester->getId(),
            'name' => $semester->getName(),
            'start_date' => $semester->getStartDate()?->format('Y-m-d'),
            'end_date' => $semester->getEndDate()?->format('Y-m-d'),
            'order_number' => $semester->getOrderNumber(),
            'id_formation' => $semester->getIdFormation()?->getId(),
            'resources' => $semester->getResources()->map(fn($resource) => $resource->getId())->toArray(),
        ]);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request, FormationRepository $repoFormation): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!is_array($data)) {
            return $this->json(['error' => 'Invalid JSON data'], Response::HTTP_BAD_REQUEST);
        }

        if (!isset($data['name']) || !is_string($data['name']) ||
            !isset($data['order_number']) || !is_numeric($data['order_number']) ||
            !isset($data['id_formation']) || !is_numeric($data['id_formation'])) {
            return $this->json(['error' => 'Invalid input data'], Response::HTTP_BAD_REQUEST);
        }

        $formation = $repoFormation->find($data['id_formation']);

        if (!$formation) {
            return $this->json(['error' => 'Formation not found'], Response::HTTP_NOT_FOUND);
        }

        $semester = new Semesters();
        $semester->setName($data['name']);
        if (!empty($data['start_date'])) {
            $semester->setStartDate(new \DateTime($data['start_date']));
        }
        if (!empty($data['end_date'])) {
            $semester->setEndDate(new \DateTime($data['end_date']));
        }
        $semester->setOrderNumber((int) $data['order_number']);
        $semester->setIdFormation($formation);

        try {
            $this->entityManager->persist($semester);
            $this->entityManager->flush();

            return $this->json([
                'id' => $semester->getId(),
                'name' => $semester->getName(),
                'start_date' => $semester->getStartDate()?->format('Y-m-d'),
                'end_date' => $semester->getEndDate()?->format('Y-m-d'),
                'order_number' => $semester->getOrderNumber(),
                'id_formation' => $formation->getId(),
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Failed to create semester', 'message' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    #[Route('/{id}', name: 'update', methods: ['PUT'])]
    public function update(Request $request, Semesters $semester): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!is_array($data) || empty($data)) {
            return $this->json(['error' => 'Invalid or empty data provided'], Response::HTTP_BAD_REQUEST);
        }

        if (isset($data['name']) && is_string($data['name'])) {
            $semester->setName($data['name']);
        }
        if (isset($data['start_date'])) {
            $semester->setStartDate(new \DateTime($data['start_date']));
        }
        if (isset($data['end_date'])) {
            $semester->setEndDate(new \DateTime($data['end_date']));
        }
        if (isset($data['order_number']) && is_numeric($data['order_number'])) {
            $semester->setOrderNumber((int) $data['order_number']);
        }

        try {
            $this->entityManager->flush();

            return $this->json([
                'id' => $semester->getId(),
                'name' => $semester->getName(),
                'start_date' => $semester->getStartDate()?->format('Y-m-d'),
                'end_date' => $semester->getEndDate()?->format('Y-m-d'),
                'order_number' => $semester->getOrderNumber(),
            ]);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Failed to update semester', 'message' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(Semesters $semester): JsonResponse
    {
        try {
            $this->entityManager->remove($semester);
            $this->entityManager->flush();

            return $this->json(['message' => 'Semester deleted successfully'], Response::HTTP_OK);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Failed to delete semester', 'message' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
