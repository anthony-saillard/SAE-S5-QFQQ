<?php

namespace App\Security;

use App\Entity\OAuth2AccessToken;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class OAuth2Authenticator extends AbstractAuthenticator
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function supports(Request $request): ?bool
    {
        return $request->headers->has('Authorization');
    }

    public function authenticate(Request $request): Passport
    {
        $authHeader = $request->headers->get('Authorization');
        if (null === $authHeader) {
            throw new CustomUserMessageAuthenticationException('No API token found');
        }

        $token = str_replace('Bearer ', '', $authHeader);

        $accessToken = $this->entityManager->getRepository(OAuth2AccessToken::class)
            ->findOneBy(['token' => $token]);

        if (!$accessToken) {
            throw new CustomUserMessageAuthenticationException('Invalid token');
        }

        if ($accessToken->isExpired()) {
            throw new CustomUserMessageAuthenticationException('Token expired');
        }

        $user = $accessToken->getUser();

        if (!$user) {
            throw new \LogicException('Aucun utilisateur associé à ce jeton d\'accès.');
        }

        return new SelfValidatingPassport(
            new UserBadge($user->getLogin() ?? '')
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $data = [
            'message' => strtr($exception->getMessageKey(), $exception->getMessageData())
        ];

        return new JsonResponse($data, Response::HTTP_UNAUTHORIZED);
    }
}