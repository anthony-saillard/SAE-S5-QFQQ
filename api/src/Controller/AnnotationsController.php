<?php

namespace App\Controller;

use App\Entity\Annotations;
use App\Repository\AnnotationsRepository;
use App\Repository\ResourcesRepository;
use App\Repository\UsersRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints\Date;
use function Symfony\Component\Clock\now;

#[Route('/api/annotations', name: 'api_annotations_')]
class AnnotationsController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(Request $request, AnnotationsRepository $repository): JsonResponse
    {
        $resourcesId = $request->query->get('id_resources');
        $userId = $request->query->get('id_user');

        $annotations = $repository->findByFilters(
            $resourcesId ? (int) $resourcesId : null,
            $userId ? (int) $userId : null
        );

        $data = array_map(fn($annotation) => [
            'id' => $annotation->getId(),
            'description' => $annotation->getDescription(),
            'id_resources' => $annotation->getIdResources()?->getId(),
            'id_user' => $annotation->getIdUser()?->getId(),
            'created_at' => $annotation->getCreatedAt()?->format('Y-m-d H:i:s'),
            'notifications' => $annotation->getNotifications()->map(fn($notification) => $notification->getId())->toArray(),
        ], $annotations);

        return $this->json($data, Response::HTTP_OK);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(int $id, AnnotationsRepository $repository): JsonResponse
    {
        $annotation = $repository->find($id);

        if (!$annotation) {
            return $this->json(['message' => 'Annotation not found'], Response::HTTP_NOT_FOUND);
        }

        $data = [
            'id' => $annotation->getId(),
            'description' => $annotation->getDescription(),
            'id_resources' => $annotation->getIdResources()?->getId(),
            'id_user' => $annotation->getIdUser()?->getId(),
            'created_at' => $annotation->getCreatedAt()?->format('Y-m-d H:i:s'),
            'notifications' => $annotation->getNotifications()->map(fn($notification) => $notification->getId())->toArray(),
        ];

        return $this->json($data, Response::HTTP_OK);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(
        Request $request,
        EntityManagerInterface $em,
        ResourcesRepository $resourcesRepository,
        UsersRepository $usersRepository
    ): JsonResponse {
        /** @var array{description?: string|null, id_resources?: int|null, id_user?: int|null}|null $data */
        $data = json_decode($request->getContent(), true);

        if ($data === null || !isset($data['description'], $data['id_resources'])) {
            return $this->json(['error' => 'Description and id_resources are required'], Response::HTTP_BAD_REQUEST);
        }

        $annotation = new Annotations();
        $annotation->setDescription($data['description']);

        $resource = $resourcesRepository->find($data['id_resources']);
        if (!$resource) {
            return $this->json(['error' => 'Invalid id_resources'], Response::HTTP_BAD_REQUEST);
        }
        $annotation->setIdResources($resource);

        if (isset($data['id_user'])) {
            $user = $usersRepository->find($data['id_user']);
            if ($user) {
                $annotation->setIdUser($user);
            } else {
                return $this->json(['error' => 'Invalid id_user'], Response::HTTP_BAD_REQUEST);
            }
        }

        $annotation->setCreatedAt(new \DateTime());

        $em->persist($annotation);
        $em->flush();

        return $this->json([
            'message' => 'Annotation created successfully',
            'id' => $annotation->getId(),
            'description' => $annotation->getDescription(),
            'id_resources' => $annotation->getIdResources()?->getId(),
            'id_user' => $annotation->getIdUser()?->getId(),
            'created_at' => $annotation->getCreatedAt()?->format('Y-m-d H:i:s'),
            'notifications' => $annotation->getNotifications()->map(fn($notification) => $notification->getId())->toArray(),
        ], Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'update', methods: ['PUT'])]
    public function update(
        int $id,
        Request $request,
        EntityManagerInterface $em,
        AnnotationsRepository $repository,
        ResourcesRepository $resourcesRepository,
        UsersRepository $usersRepository
    ): JsonResponse {
        $annotation = $repository->find($id);

        if (!$annotation) {
            return $this->json(['error' => 'Annotation not found'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);
        if ($data === null) {
            return $this->json(['error' => 'Invalid JSON'], Response::HTTP_BAD_REQUEST);
        }

        if (isset($data['description'])) {
            $annotation->setDescription($data['description']);
        }

        if (isset($data['id_resources'])) {
            $resource = $resourcesRepository->find($data['id_resources']);
            if ($resource) {
                $annotation->setIdResources($resource);
            } else {
                return $this->json(['error' => 'Invalid id_resources'], Response::HTTP_BAD_REQUEST);
            }
        }

        if (isset($data['id_user'])) {
            $user = $usersRepository->find($data['id_user']);
            if ($user) {
                $annotation->setIdUser($user);
            } else {
                return $this->json(['error' => 'Invalid id_user'], Response::HTTP_BAD_REQUEST);
            }
        }

        $em->flush();

        return $this->json([
            'message' => 'Annotation updated successfully',
            'id' => $annotation->getId(),
            'description' => $annotation->getDescription(),
            'id_resources' => $annotation->getIdResources()?->getId(),
            'id_user' => $annotation->getIdUser()?->getId(),
            'created_at' => $annotation->getCreatedAt()?->format('Y-m-d H:i:s'),
        ]);
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(int $id, EntityManagerInterface $em, AnnotationsRepository $repository): JsonResponse
    {
        $annotation = $repository->find($id);

        if (!$annotation) {
            return $this->json(['error' => 'Annotation not found'], Response::HTTP_NOT_FOUND);
        }

        $em->remove($annotation);
        $em->flush();

        return $this->json(['message' => 'Annotation deleted successfully'], Response::HTTP_NO_CONTENT);
    }
}