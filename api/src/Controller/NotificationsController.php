<?php

namespace App\Controller;

use App\Entity\Notifications;
use App\Repository\NotificationsRepository;
use App\Repository\AnnotationsRepository;
use App\Repository\ResourcesRepository;
use App\Repository\SubResourcesRepository;
use App\Repository\AssignmentsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/notifications', name: 'api_notifications_')]
class NotificationsController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(NotificationsRepository $repository): JsonResponse
    {
        $notifications = $repository->findAll();

        $data = array_map(fn($notification) => [
            'id' => $notification->getId(),
            'modification_date' => $notification->getModificationDate()?->format('Y-m-d'),
            'status' => $notification->getStatus(),
            'id_annotations' => $notification->getIdAnnotations()?->getId(),
            'id_ressources' => $notification->getIdRessources()?->getId(),
            'id_sub_resources' => $notification->getIdSubResources()?->getId(),
            'id_assignments' => $notification->getIdAssignments()?->getId(),
        ], $notifications);

        return $this->json($data, Response::HTTP_OK);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Notifications $notification): JsonResponse
    {
        $data = [
            'id' => $notification->getId(),
            'modification_date' => $notification->getModificationDate()?->format('Y-m-d'),
            'status' => $notification->getStatus(),
            'id_annotations' => $notification->getIdAnnotations()?->getId(),
            'id_ressources' => $notification->getIdRessources()?->getId(),
            'id_sub_resources' => $notification->getIdSubResources()?->getId(),
            'id_assignments' => $notification->getIdAssignments()?->getId(),
        ];

        return $this->json($data, Response::HTTP_OK);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(
        Request $request,
        EntityManagerInterface $em,
        AnnotationsRepository $annotationsRepository,
        ResourcesRepository $resourcesRepository,
        SubResourcesRepository $subResourcesRepository,
        AssignmentsRepository $assignmentsRepository
    ): JsonResponse {
        /** @var array{status?: int|null, id_annotations?: int|null, id_ressources?: int|null, id_sub_resources?: int|null, id_assignments?: int|null}|null $data */
        $data = json_decode($request->getContent(), true);

        if ($data === null) {
            return $this->json(['error' => 'Invalid JSON data'], Response::HTTP_BAD_REQUEST);
        }

        $notification = new Notifications();
        $notification->setModificationDate(new \DateTime());
        $notification->setStatus($data['status'] ?? null);

        if (isset($data['id_annotations'])) {
            $annotations = $annotationsRepository->find((int) $data['id_annotations']);
            if ($annotations) {
                $notification->setIdAnnotations($annotations);
            }
        }

        if (isset($data['id_ressources'])) {
            $resources = $resourcesRepository->find((int) $data['id_ressources']);
            if ($resources) {
                $notification->setIdRessources($resources);
            }
        }

        if (isset($data['id_sub_resources'])) {
            $subResources = $subResourcesRepository->find((int) $data['id_sub_resources']);
            if ($subResources) {
                $notification->setIdSubResources($subResources);
            }
        }

        if (isset($data['id_assignments'])) {
            $assignments = $assignmentsRepository->find((int) $data['id_assignments']);
            if ($assignments) {
                $notification->setIdAssignments($assignments);
            }
        }

        $em->persist($notification);
        $em->flush();

        return $this->json([
            'message' => 'Notification created successfully',
            'data' => [
                'id' => $notification->getId(),
                'modification_date' => $notification->getModificationDate()?->format('Y-m-d'),
                'status' => $notification->getStatus(),
                'id_annotations' => $notification->getIdAnnotations()?->getId(),
                'id_ressources' => $notification->getIdRessources()?->getId(),
                'id_sub_resources' => $notification->getIdSubResources()?->getId(),
                'id_assignments' => $notification->getIdAssignments()?->getId(),
            ]
        ], Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'update', methods: ['PUT'])]
    public function update(
        Request $request,
        Notifications $notification,
        EntityManagerInterface $em,
        AnnotationsRepository $annotationsRepository,
        ResourcesRepository $resourcesRepository,
        SubResourcesRepository $subResourcesRepository,
        AssignmentsRepository $assignmentsRepository
    ): JsonResponse {
        /** @var array{status?: int|null, id_annotations?: int|null, id_ressources?: int|null, id_sub_resources?: int|null, id_assignments?: int|null}|null $data */
        $data = json_decode($request->getContent(), true);

        if ($data === null) {
            return $this->json(['error' => 'Invalid JSON data'], Response::HTTP_BAD_REQUEST);
        }

        if (isset($data['status'])) {
            $notification->setStatus((int) $data['status']);
        }

        $notification->setModificationDate(new \DateTime());

        if (isset($data['id_annotations'])) {
            $annotations = $annotationsRepository->find((int) $data['id_annotations']);
            $notification->setIdAnnotations($annotations);
        }

        if (isset($data['id_ressources'])) {
            $resources = $resourcesRepository->find((int) $data['id_ressources']);
            $notification->setIdRessources($resources);
        }

        if (isset($data['id_sub_resources'])) {
            $subResources = $subResourcesRepository->find((int) $data['id_sub_resources']);
            $notification->setIdSubResources($subResources);
        }

        if (isset($data['id_assignments'])) {
            $assignments = $assignmentsRepository->find((int) $data['id_assignments']);
            $notification->setIdAssignments($assignments);
        }

        $em->flush();

        return $this->json(['message' => 'Notification updated successfully'], Response::HTTP_OK);
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(Notifications $notification, EntityManagerInterface $em): JsonResponse
    {
        $em->remove($notification);
        $em->flush();

        return $this->json(['message' => 'Notification deleted successfully'], Response::HTTP_NO_CONTENT);
    }
}