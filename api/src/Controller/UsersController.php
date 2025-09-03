<?php

namespace App\Controller;

use App\Entity\Users;
use App\Repository\UsersRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/api/users', name: 'api_user_')]
class UsersController extends AbstractController
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(UsersRepository $repository): JsonResponse
    {
        $users = $repository->findBy(['disable' => false]);

        $data = array_map(fn($user) => [
            'id' => $user->getId(),
            'login' => $user->getLogin(),
            'last_name' => $user->getLastName(),
            'first_name' => $user->getFirstName(),
            'role' => $user->getRole(),
            'phone' => $user->getPhone(),
            'email' => $user->getEmail(),
        ], $users);

        return $this->json($data, Response::HTTP_OK);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function getUserById(int $id, UsersRepository $repository): JsonResponse
    {
        $user = $repository->find($id);

        if (!$user) {
            return $this->json(['error' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        return $this->json([
            'id' => $user->getId(),
            'login' => $user->getLogin(),
            'last_name' => $user->getLastName(),
            'first_name' => $user->getFirstName(),
            'role' => $user->getRole(),
            'phone' => $user->getPhone(),
            'email' => $user->getEmail(),
        ], Response::HTTP_OK);
    }

    #[Route('/{id}', name: 'update', methods: ['PUT'])]
    public function updateUser(Request $request, int $id, UsersRepository $repository): JsonResponse
    {
        $user = $repository->find($id);

        if (!$user) {
            return $this->json(['error' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);

        if (!is_array($data)) {
            return $this->json([
                'error' => 'Le format des données envoyées est invalide.'
            ], Response::HTTP_BAD_REQUEST);
        }

        if (isset($data['login']) && is_string($data['login'])) {
            $user->setLogin($data['login']);
        }

        if (isset($data['password']) && is_string($data['password'])) {
            $user->setPassword(password_hash($data['password'], PASSWORD_BCRYPT));
        }

        if (isset($data['last_name']) && is_string($data['last_name'])) {
            $user->setLastName($data['last_name']);
        }

        if (isset($data['first_name']) && is_string($data['first_name'])) {
            $user->setFirstName($data['first_name']);
        }

        if (isset($data['role']) && is_string($data['role'])) {
            $user->setRole($data['role']);
        }

        if (isset($data['phone']) && is_string($data['phone'])) {
            $user->setPhone($data['phone']);
        }

        if (isset($data['email']) && is_string($data['email'])) {
            $user->setEmail($data['email']);
        }

        try {
            $this->entityManager->flush();

            return $this->json(['message' => 'User updated successfully'], Response::HTTP_OK);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Failed to update user', 'details' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function deleteUser(int $id, UsersRepository $repository): JsonResponse
    {
        $user = $repository->find($id);

        if (!$user) {
            return $this->json(['error' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        try {
            $randomString = substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 5);

            $user->setLogin(  $user->getLogin() . '_' . $randomString );
            $user->setDisable(true);
            $this->entityManager->flush();

            return $this->json(['message' => 'User deleted successfully'], Response::HTTP_OK);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Failed to delete user', 'details' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

}