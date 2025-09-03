<?php

namespace App\Controller;

use App\Entity\Users;
use App\Entity\OAuth2AccessToken;
use App\Entity\OAuth2RefreshToken;
use App\Repository\UsersRepository;
use App\Repository\OAuth2AccessTokenRepository;
use App\Repository\OAuth2RefreshTokenRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Uid\Uuid;

#[Route('/api', name: 'api_')]
class SecurityController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private UserPasswordHasherInterface $passwordHasher;
    private ValidatorInterface $validator;

    public function __construct(
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
        ValidatorInterface $validator
    ) {
        $this->entityManager = $entityManager;
        $this->passwordHasher = $passwordHasher;
        $this->validator = $validator;
    }

    private function generateAccessToken(Users $user): OAuth2AccessToken
    {
        $accessToken = new OAuth2AccessToken();
        $accessToken->setUser($user);
        $accessToken->setToken(Uuid::v4()->toRfc4122());
        $accessToken->setExpiresAt(new \DateTimeImmutable('+1 hour'));

        $this->entityManager->persist($accessToken);
        $this->entityManager->flush();

        return $accessToken;
    }

    private function generateRefreshToken(Users $user): OAuth2RefreshToken
    {
        $refreshToken = new OAuth2RefreshToken();
        $refreshToken->setUser($user);
        $refreshToken->setToken(Uuid::v4()->toRfc4122());
        $refreshToken->setExpiresAt(new \DateTimeImmutable('+30 days'));

        $this->entityManager->persist($refreshToken);
        $this->entityManager->flush();

        return $refreshToken;
    }

    #[Route('/register', name: 'register', methods: ['POST'])]
    public function register(Request $request, UsersRepository $repository): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            if (!is_array($data)) {
                return $this->json([
                    'error' => 'Le format des données envoyées est invalide.'
                ], Response::HTTP_BAD_REQUEST);
            }

            if (!isset($data['login']) || !isset($data['password']) ||
                trim($data['login']) === '' || trim($data['password']) === '') {
                return $this->json([
                    'error' => 'Les champs login et password sont obligatoires et ne peuvent pas être vides'
                ], Response::HTTP_BAD_REQUEST);
            }

            if (!is_string($data['login']) || !is_string($data['password'])) {
                return $this->json([
                    'error' => 'Les champs login et password doivent être des chaînes de caractères.'
                ], Response::HTTP_BAD_REQUEST);
            }

            $existingUser = $repository->findOneBy(['login' => $data['login']]);

            if ($existingUser) {
                return $this->json([
                    'error' => 'Un utilisateur avec ce login existe déjà'
                ], Response::HTTP_CONFLICT);
            }

            $user = new Users();
            $user->setLogin($data['login']);
            $user->setPassword($this->passwordHasher->hashPassword($user, $data['password']));

            if (isset($data['first_name']) && is_string($data['first_name'])) {
                $user->setFirstName($data['first_name']);
            }

            if (isset($data['last_name']) && is_string($data['last_name'])) {
                $user->setLastName($data['last_name']);
            }

            if (isset($data['email']) && is_string($data['email'])) {
                $user->setEmail($data['email']);
            }

            if (isset($data['role']) && is_string($data['role'])) {
                $user->setRole($data['role']);
            } else {
                $user->setRole('ROLE_USER');
            }

            $user->setDisable(false);

            $errors = $this->validator->validate($user);
            if (count($errors) > 0) {
                $errorMessages = [];
                foreach ($errors as $error) {
                    $errorMessages[] = $error->getMessage();
                }
                return $this->json([
                    'error' => 'Données invalides',
                    'details' => $errorMessages
                ], Response::HTTP_BAD_REQUEST);
            }

            $this->entityManager->persist($user);
            $this->entityManager->flush();

            $accessToken = $this->generateAccessToken($user);
            $refreshToken = $this->generateRefreshToken($user);

            $expiresAt = $accessToken->getExpiresAt();
            if ($expiresAt === null) {
                throw new \RuntimeException('Expiration date is not set.');
            }

            return $this->json([
                'message' => 'Inscription réussie',
                'access_token' => $accessToken->getToken(),
                'refresh_token' => $refreshToken->getToken(),
                'token_type' => 'Bearer',
                'expires_in' => $expiresAt->getTimestamp() - time(),
                'user' => [
                    'id' => $user->getId(),
                    'login' => $user->getLogin(),
                    'email' => $user->getEmail(),
                    'firstName' => $user->getFirstName(),
                    'lastName' => $user->getLastName(),
                    'role' => $user->getRole()
                ]
            ], Response::HTTP_CREATED);

        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Une erreur est survenue lors de l\'inscription',
                'details' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/login', name: 'login', methods: ['POST'])]
    public function login(Request $request, UsersRepository $repository): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!is_array($data)) {
            return $this->json([
                'error' => 'Le format des données envoyées est invalide.'
            ], Response::HTTP_BAD_REQUEST);
        }

        if (!isset($data['login'], $data['password'])) {
            return $this->json(['error' => 'Missing credentials'], Response::HTTP_BAD_REQUEST);
        }

        if (!is_string($data['login']) || !is_string($data['password'])) {
            return $this->json([
                'error' => 'Les champs login et password doivent être des chaînes de caractères.'
            ], Response::HTTP_BAD_REQUEST);
        }

        $user = $repository->findOneBy(['login' => $data['login']]);

        if (!$user) {
            return $this->json(['error' => 'Invalid login'], Response::HTTP_UNAUTHORIZED);
        }

        if ($user->isDisable()) {
            return $this->json(['error' => 'Invalid login'], Response::HTTP_UNAUTHORIZED);
        }

        $hashedPassword = $user->getPassword();

        if (!is_string($hashedPassword)) {
            return $this->json(['error' => 'Le mot de passe de l\'utilisateur est invalide.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        if (!password_verify($data['password'], $hashedPassword)) {
            return $this->json(['error' => 'Invalid credentials'], Response::HTTP_UNAUTHORIZED);
        }

        $accessToken = $this->generateAccessToken($user);
        $refreshToken = $this->generateRefreshToken($user);

        $expiresAt = $accessToken->getExpiresAt();
        if ($expiresAt === null) {
            throw new \RuntimeException('Expiration date is not set.');
        }

        return $this->json([
            'access_token' => $accessToken->getToken(),
            'refresh_token' => $refreshToken->getToken(),
            'token_type' => 'Bearer',
            'expires_in' => $expiresAt->getTimestamp() - time(),
            'user' => [
                'id' => $user->getId(),
                'login' => $user->getLogin(),
                'role' => $user->getRole(),
            ]
        ]);
    }

    #[Route('/refresh-token', name: 'refresh_token', methods: ['POST'])]
    public function refreshToken(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!is_array($data)) {
            return $this->json([
                'error' => 'Le format des données envoyées est invalide.'
            ], Response::HTTP_BAD_REQUEST);
        }

        if (!isset($data['refresh_token'])) {
            return $this->json(['error' => 'Refresh token is required'], Response::HTTP_BAD_REQUEST);
        }

        $refreshToken = $this->entityManager->getRepository(OAuth2RefreshToken::class)
            ->findOneBy(['token' => $data['refresh_token']]);

        if (!$refreshToken || $refreshToken->isExpired()) {
            return $this->json(['error' => 'Invalid refresh token'], Response::HTTP_UNAUTHORIZED);
        }

        $user = $refreshToken->getUser();

        if (!$user) {
            return $this->json([
                'error' => 'Utilisateur introuvable pour ce refresh token.'
            ], Response::HTTP_UNAUTHORIZED);
        }

        $newAccessToken = $this->generateAccessToken($user);

        $expiresAt = $newAccessToken->getExpiresAt();
        if ($expiresAt === null) {
            throw new \RuntimeException('Expiration date is not set.');
        }

        return $this->json([
            'access_token' => $newAccessToken->getToken(),
            'token_type' => 'Bearer',
            'expires_in' => $expiresAt->getTimestamp() - time(),
        ]);
    }

    #[Route('/me', name: 'me', methods: ['GET'])]
    public function me(#[CurrentUser] ?Users $user): JsonResponse
    {
        if (null === $user) {
            return $this->json([
                'error' => 'Non authentifié'
            ], Response::HTTP_UNAUTHORIZED);
        }

        return $this->json([
            'user' => [
                'id' => $user->getId(),
                'login' => $user->getLogin(),
                'email' => $user->getEmail(),
                'role' => $user->getRole(),
                'firstName' => $user->getFirstName(),
                'lastName' => $user->getLastName()
            ]
        ],Response::HTTP_OK);
    }

    #[Route('/logout', name: 'logout', methods: ['POST'])]
    public function logout(Request $request, OAuth2AccessTokenRepository $accessTokenRepository): JsonResponse
    {
        $token = $request->headers->get('Authorization');
        if ($token) {
            $token = str_replace('Bearer ', '', $token);

            $accessToken = $accessTokenRepository->findOneBy(['token' => $token]);

            if ($accessToken) {
                $this->entityManager->remove($accessToken);
                $this->entityManager->flush();
            }
        }

        return $this->json(['message' => 'Logged out successfully']);
    }
}