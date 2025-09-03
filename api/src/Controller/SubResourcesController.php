<?php

namespace App\Controller;

use App\Entity\SubResources;
use App\Repository\SubResourcesRepository;
use App\Repository\ResourcesRepository;
use App\Repository\UsersRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/sub-resources', name: 'sub_resources_')]
class SubResourcesController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(Request $request, SubResourcesRepository $subResourcesRepository): JsonResponse
    {
        $resourceId = $request->query->get('id_resource');
        $userId = $request->query->get('id_user');

        $subResources = $subResourcesRepository->findByFilters(
            $resourceId ? (int) $resourceId : null,
            $userId ? (int) $userId : null
        );

        $data = array_map(fn($subResource) => [
            'id' => $subResource->getId(),
            'name' => $subResource->getName(),
            'id_resources' => $subResource->getIdResources()?->getId(),
            'id_users' => $subResource->getIdUsers()?->getId(),
            'status' => $subResource->getStatus(),
        ], $subResources);

        return $this->json($data);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(int $id, SubResourcesRepository $subResourcesRepository): JsonResponse
    {
        $subResource = $subResourcesRepository->find($id);

        if (!$subResource) {
            return $this->json(['message' => 'SubResource not found'], 404);
        }

        $data = [
            'id' => $subResource->getId(),
            'name' => $subResource->getName(),
            'id_resources' => $subResource->getIdResources()?->getId(),
            'id_users' => $subResource->getIdUsers()?->getId(),
            'status' => $subResource->getStatus(),
        ];

        return $this->json($data);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(
        Request $request,
        EntityManagerInterface $em,
        ResourcesRepository $resourcesRepository,
        UsersRepository $usersRepository
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        if ($data === null || !isset($data['name'], $data['id_resources'])) {
            return $this->json(['error' => 'Name and id_resources are required'], Response::HTTP_BAD_REQUEST);
        }

        $subResource = new SubResources();
        $subResource->setName($data['name']);

        $resource = $resourcesRepository->find($data['id_resources']);
        if (!$resource) {
            return $this->json(['error' => 'Invalid id_resources'], Response::HTTP_BAD_REQUEST);
        }
        $subResource->setIdResources($resource);

        if (isset($data['id_users'])) {
            $user = $usersRepository->find($data['id_users']);
            if ($user) {
                $subResource->setIdUsers($user);
            }
        }

        if (isset($data['status'])) {
            try {
                $status = trim($data['status']);

                if (!in_array($status, ["NOT_STARTED", "IN_PROGRESS", "COMPLETED"])) {
                    throw new \InvalidArgumentException('Invalid status value');
                }
                $subResource->setStatus($status);
            } catch (\InvalidArgumentException $e) {
                return $this->json(['error' => 'Invalid status. Must be one of: "NOT_STARTED", "IN_PROGRESS", "COMPLETED"'], Response::HTTP_BAD_REQUEST);
            }
        } else {
            $subResource->setStatus('NOT_STARTED');
        }

        $em->persist($subResource);
        $em->flush();

        return $this->json([
            'message' => 'SubResource created successfully',
            'id' => $subResource->getId(),
            'name' => $subResource->getName(),
            'id_resources' => $subResource->getIdResources()?->getId(),
            'id_users' => $subResource->getIdUsers()?->getId(),
            'status' => $subResource->getStatus(),
        ], Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'update', methods: ['PUT'])]
    public function update(
        int $id,
        Request $request,
        EntityManagerInterface $em,
        SubResourcesRepository $subResourcesRepository,
        UsersRepository $usersRepository
    ): JsonResponse {
        $subResource = $subResourcesRepository->find($id);

        if (!$subResource) {
            return $this->json(['error' => 'SubResource not found'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);
        if ($data === null) {
            return $this->json(['error' => 'Invalid JSON'], Response::HTTP_BAD_REQUEST);
        }

        if (isset($data['name'])) {
            $subResource->setName($data['name']);
        }

        if (isset($data['id_users'])) {
            $user = $usersRepository->find($data['id_users']);
            if ($user) {
                $subResource->setIdUsers($user);
            }
        }

        if (isset($data['status'])) {
            try {
                $status = trim($data['status']);

                if (!in_array($status, ["NOT_STARTED", "IN_PROGRESS", "COMPLETED"])) {
                    throw new \InvalidArgumentException('Invalid status value');
                }
                $subResource->setStatus($status);
            } catch (\InvalidArgumentException $e) {
                return $this->json(['error' => 'Invalid status. Must be one of: "NOT_STARTED", "IN_PROGRESS", "COMPLETED"'], Response::HTTP_BAD_REQUEST);
            }
        }

        $em->flush();

        return $this->json(['message' => 'SubResource updated successfully']);
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(int $id, EntityManagerInterface $em, SubResourcesRepository $subResourcesRepository): JsonResponse
    {
        $subResource = $subResourcesRepository->find($id);

        if (!$subResource) {
            return $this->json(['error' => 'SubResource not found'], Response::HTTP_NOT_FOUND);
        }

        $em->remove($subResource);
        $em->flush();

        return $this->json(['message' => 'SubResource deleted successfully']);
    }
}