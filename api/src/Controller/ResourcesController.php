<?php

namespace App\Controller;

use App\Entity\Resources;
use App\Repository\ResourcesRepository;
use App\Repository\SemestersRepository;
use App\Repository\UsersRepository;
use App\Service\SchoolYearService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/resources', name: 'api_resources_')]
class ResourcesController extends AbstractController
{
    public function __construct(
        private readonly SchoolYearService $schoolYearService
    ) {
    }

    #[Route('', name: 'index', methods: ['GET'])]
    public function list(
        Request $request,
        ResourcesRepository $resourcesRepository
    ): JsonResponse {
        $userId = $request->query->get('id_user');
        $semesterId = $request->query->get('id_semester');
        $formationId = $request->query->get('id_formation');
        $schoolYearId = $request->query->get('id_school_year');

        if ($schoolYearId === null) {
            $schoolYear = $this->schoolYearService->getCurrentSchoolYear();

            if ($schoolYear instanceof JsonResponse) {
                return $schoolYear;
            }
            $schoolYearId = $schoolYear->getId();
        }

        $resources = $resourcesRepository->findByFilters(
            $userId ? (int) $userId : null,
            $semesterId ? (int) $semesterId : null,
            $formationId ? (int) $formationId : null,
            $schoolYearId ? (int) $schoolYearId : null
        );

        $data = array_map(fn($resource) => [
            'id' => $resource->getId(),
            'identifier' => $resource->getIdentifier(),
            'name' => $resource->getName(),
            'description' => $resource->getDescription(),
            'id_semesters' => $resource->getIdSemesters()?->getId(),
            'id_formation' => $resource->getIdSemesters()?->getIdFormation()?->getId(),
            'id_school_year' => $resource->getIdSemesters()?->getIdFormation()?->getIdSchoolYear()?->getId(),
            'id_users' => $resource->getIdUsers()?->getId(),
            'total_hours' => $resource->getTotalHours(),
        ], $resources);

        return $this->json($data);
    }


    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Resources $resource): JsonResponse
    {
        $data = [
            'id' => $resource->getId(),
            'identifier' => $resource->getIdentifier(),
            'name' => $resource->getName(),
            'description' => $resource->getDescription(),
            'id_semesters' => $resource->getIdSemesters()?->getId(),
            'id_users' => $resource->getIdUsers()?->getId(),
            'total_hours' => $resource->getTotalHours(),
            'annotations' => $resource->getAnnotations()->map(fn($annotation) => $annotation->getId())->toArray(),
            'sub_resources' => $resource->getSubResources()->map(fn($subResource) => $subResource->getId())->toArray(),
            'notifications' => $resource->getNotifications()->map(fn($notification) => $notification->getId())->toArray(),
        ];

        return $this->json($data, Response::HTTP_OK);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(
        Request $request,
        EntityManagerInterface $em,
        SemestersRepository $semestersRepository,
        UsersRepository $usersRepository
    ): JsonResponse {
        /** @var array{identifier?: string|null, name?: string|null, description?: string|null, id_semesters?: int|null, id_users?: int|null, total_hours?: int|null}|null $data */
        $data = json_decode($request->getContent(), true);

        if ($data === null) {
            return $this->json(['error' => 'Invalid JSON data'], Response::HTTP_BAD_REQUEST);
        }

        if (empty($data['name'])) {
            return $this->json(['error' => 'The "name" field is required.'], Response::HTTP_BAD_REQUEST);
        }

        if (empty($data['identifier'])) {
            return $this->json(['error' => 'The "identifier" field is required.'], Response::HTTP_BAD_REQUEST);
        }

        $resource = new Resources();
        $resource->setIdentifier($data['identifier']);
        $resource->setName($data['name']);
        $resource->setDescription($data['description'] ?? null);

        if (isset($data['id_semesters'])) {
            $semester = $semestersRepository->find((int) $data['id_semesters']);
            if ($semester) {
                $resource->setIdSemesters($semester);
            } else {
                return $this->json(['error' => 'Invalid semester ID'], Response::HTTP_BAD_REQUEST);
            }
        }

        if (isset($data['id_users'])) {
            $user = $usersRepository->find((int) $data['id_users']);
            if ($user) {
                $resource->setIdUsers($user);
            } else {
                return $this->json(['error' => 'Invalid user ID'], Response::HTTP_BAD_REQUEST);
            }
        }
        if (isset($data['total_hours'])) {
            $resource->setTotalHours($data['total_hours']);
        }

        $em->persist($resource);
        $em->flush();

        return $this->json([
                'id' => $resource->getId(),
                'identifier' => $resource->getIdentifier(),
                'name' => $resource->getName(),
                'description' => $resource->getDescription(),
                'id_semesters' => $resource->getIdSemesters()?->getId(),
                'id_users' => $resource->getIdUsers()?->getId(),
                'total_hours' => $resource->getTotalHours(),
                'annotations' => [],
                'sub_resources' => [],
                'notifications' => []
        ], Response::HTTP_CREATED);
    }


    #[Route('/{id}', name: 'update', methods: ['PUT'])]
    public function update(
        Request $request,
        Resources $resource,
        EntityManagerInterface $em,
        SemestersRepository $semestersRepository,
        UsersRepository $usersRepository
    ): JsonResponse {
        /** @var array{identifier?: string|null, name?: string|null, description?: string|null, id_semesters?: int|null, id_users?: int|null, total_hours?: int|null}|null $data */
        $data = json_decode($request->getContent(), true);

        if ($data === null) {
            return $this->json(['error' => 'Invalid JSON data'], Response::HTTP_BAD_REQUEST);
        }

        if (isset($data['identifier'])) {
            $resource->setIdentifier($data['identifier']);
        }

        if (isset($data['name'])) {
            $resource->setName($data['name']);
        }

        if (isset($data['description'])) {
            $resource->setDescription($data['description']);
        }

        if (isset($data['id_semesters'])) {
            $semester = $semestersRepository->find((int) $data['id_semesters']);
            $resource->setIdSemesters($semester);
        }

        if (isset($data['id_users'])) {
            $user = $usersRepository->find((int) $data['id_users']);
            $resource->setIdUsers($user);

            $subResource = $resource->getSubResources()->first();
            if ($subResource) {
                $subResource->setIdUsers($user);
                $em->persist($subResource);
            }
        }
        if (isset($data['total_hours'])) {
            $resource->setTotalHours($data['total_hours']);
        }

        $em->flush();

        return $this->json(['message' => 'Resource updated successfully'], Response::HTTP_OK);
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(Resources $resource, EntityManagerInterface $em): JsonResponse
    {
        $em->remove($resource);
        $em->flush();

        return $this->json(['message' => 'Resource deleted successfully'], Response::HTTP_NO_CONTENT);
    }
}