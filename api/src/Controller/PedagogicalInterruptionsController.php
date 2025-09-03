<?php

namespace App\Controller;

use App\Entity\Formation;
use App\Entity\PedagogicalInterruptions;
use App\Repository\FormationRepository;
use App\Repository\PedagogicalInterruptionsRepository;
use App\Repository\SchoolYearRepository;
use App\Service\SchoolYearService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/pedagogical-interruptions',name: 'api_pedagogical_interruptions')]
class PedagogicalInterruptionsController extends AbstractController
{
    public function __construct(
        private readonly SchoolYearService $schoolYearService
    ) {
    }

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(Request $request, PedagogicalInterruptionsRepository $repository,SchoolYearRepository $schoolYearRepository): JsonResponse
    {

        $formationId = $request->query->get('id_formation');
        $schoolYearId = $request->query->get('id_school_year');
        if ($schoolYearId === null) {
            $schoolYear = $this->schoolYearService->getCurrentSchoolYear();

            if ($schoolYear instanceof JsonResponse) {
                return $schoolYear;
            }
            $schoolYearId = $schoolYear->getId();
        }

        $interruptions = $repository->findByFilters(
            $formationId ? (int) $formationId : null,
            $schoolYearId ? (int) $schoolYearId : null
        );

        $data = array_map(fn($interruption) => [
            'id' => $interruption->getId(),
            'name' => $interruption->getName(),
            'start_date' => $interruption->getStartDate()?->format('Y-m-d'),
            'end_date' => $interruption->getEndDate()?->format('Y-m-d'),
            'formation_id' => $interruption->getIdFormation()?->getId(),
        ], $interruptions);

        return $this->json($data, Response::HTTP_OK);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(PedagogicalInterruptions $interruption): JsonResponse
    {
        $data = [
            'id' => $interruption->getId(),
            'name' => $interruption->getName(),
            'start_date' => $interruption->getStartDate()?->format('Y-m-d'),
            'end_date' => $interruption->getEndDate()?->format('Y-m-d'),
        ];

        return $this->json($data);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em, FormationRepository $formationRepository): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!is_array($data)) {
            return $this->json(['error' => 'Invalid JSON format'], Response::HTTP_BAD_REQUEST);
        }

        $name = isset($data['name']) && is_string($data['name']) ? $data['name'] : null;
        if ($name === null) {
            return $this->json(['error' => 'Name is required and must be a string'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $startDate = isset($data['start_date']) && is_string($data['start_date']) ? new \DateTime($data['start_date']) : null;
            $endDate = isset($data['end_date']) && is_string($data['end_date']) ? new \DateTime($data['end_date']) : null;
        } catch (\Exception $e) {
            return $this->json(['error' => 'Invalid date format'], Response::HTTP_BAD_REQUEST);
        }

        if ($endDate < $startDate) {
            return $this->json(['error' => 'End date cannot be earlier than start date'], Response::HTTP_BAD_REQUEST);
        }

        if (!isset($data['formation_id']) || !is_numeric($data['formation_id'])) {
            return $this->json(['error' => 'Formation ID is required and must be a valid number'], Response::HTTP_BAD_REQUEST);
        }

        $formation = $formationRepository->find($data['formation_id']);
        if (!$formation) {
            return $this->json(['error' => 'Invalid formation ID: not found'], Response::HTTP_NOT_FOUND);
        }

        $interruption = new PedagogicalInterruptions();
        $interruption->setName($name);
        $interruption->setIdFormation($formation);
        $interruption->setStartDate($startDate);
        $interruption->setEndDate($endDate);

        $em->persist($interruption);
        $em->flush();

        return $this->json([
            'id' => $interruption->getId(),
            'name' => $interruption->getName(),
            'start_date' => $interruption->getStartDate()?->format('Y-m-d'),
            'end_date' => $interruption->getEndDate()?->format('Y-m-d'),
            'formation_id' => $formation->getId(),
        ], Response::HTTP_CREATED);
    }



    #[Route('/{id}', name: 'update', methods: ['PUT'])]
    public function update(Request $request, PedagogicalInterruptions $interruption, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!is_array($data)) {
            return $this->json(['error' => 'Invalid JSON format'], Response::HTTP_BAD_REQUEST);
        }

        if (isset($data['name'])) {
            $name = is_string($data['name']) ? $data['name'] : null;
            if ($name === null) {
                return $this->json(['error' => 'Invalid name'], Response::HTTP_BAD_REQUEST);
            }
            $interruption->setName($name);
        }

        try {
            if (isset($data['start_date'])) {
                $startDate = is_string($data['start_date']) ? new \DateTime($data['start_date']) : null;
                $interruption->setStartDate($startDate);
            }

            if (isset($data['end_date'])) {
                $endDate = is_string($data['end_date']) ? new \DateTime($data['end_date']) : null;
                $interruption->setEndDate($endDate);
            }

            if (isset($data['formation_id'])) {
                $formationId = is_numeric($data['formation_id']) ? (int) $data['formation_id'] : null;
                if ($formationId === null) {
                    return $this->json(['error' => 'Invalid formation_id'], Response::HTTP_BAD_REQUEST);
                }
                $formation = $em->getReference(Formation::class, $formationId);
                $interruption->setIdFormation($formation);
            }
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        $em->flush();

        return $this->json([
            'id' => $interruption->getId(),
            'name' => $interruption->getName(),
            'start_date' => $interruption->getStartDate()?->format('Y-m-d'),
            'end_date' => $interruption->getEndDate()?->format('Y-m-d'),
            'formation_id' => $interruption->getIdFormation()?->getId()
        ]);
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(PedagogicalInterruptions $interruption, EntityManagerInterface $em): JsonResponse
    {
        $em->remove($interruption);
        $em->flush();

        return $this->json(['message' => 'Resource deleted successfully'], Response::HTTP_NO_CONTENT);
    }
}
