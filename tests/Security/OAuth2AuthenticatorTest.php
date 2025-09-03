<?php

namespace App\Tests\Security;

use App\Entity\OAuth2AccessToken;
use App\Entity\Users;
use App\Repository\OAuth2AccessTokenRepository;
use App\Security\OAuth2Authenticator;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;

class OAuth2AuthenticatorTest extends TestCase
{
    /** @var MockObject&EntityManagerInterface  */
    private MockObject $entityManager;

    /** @var MockObject&OAuth2AccessTokenRepository */
    private MockObject $tokenRepository;
    private OAuth2Authenticator $authenticator;

    protected function setUp(): void
    {
        $this->entityManager = $this->getMockBuilder(EntityManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->tokenRepository = $this->getMockBuilder(OAuth2AccessTokenRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->entityManager->method('getRepository')
            ->with(OAuth2AccessToken::class)
            ->willReturn($this->tokenRepository);

        $this->authenticator = new OAuth2Authenticator($this->entityManager);
    }

    public function testSupports(): void
    {
        $this->assertFalse($this->authenticator->supports(new Request()));

        $request = new Request();
        $request->headers->set('Authorization', 'Bearer token123');
        $this->assertTrue($this->authenticator->supports($request));
    }

    public function testAuthenticateWithValidToken(): void
    {
        $user = new Users();
        $user->setLogin('testuser');

        $accessToken = new OAuth2AccessToken();
        $accessToken->setToken('valid_token');
        $accessToken->setUser($user);
        $accessToken->setExpiresAt(new \DateTime('+1 hour'));

        $request = new Request();
        $request->headers->set('Authorization', 'Bearer valid_token');

        $this->tokenRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['token' => 'valid_token'])
            ->willReturn($accessToken);

        $passport = $this->authenticator->authenticate($request);

        /** @var UserBadge $userBadge */
        $userBadge = $passport->getBadge(UserBadge::class);
        $this->assertEquals('testuser', $userBadge->getUserIdentifier());
    }

    public function testAuthenticateWithMissingToken(): void
    {
        $this->expectException(CustomUserMessageAuthenticationException::class);
        $this->expectExceptionMessage('No API token found');

        $request = new Request();
        $this->authenticator->authenticate($request);
    }

    public function testAuthenticateWithInvalidToken(): void
    {
        $this->expectException(CustomUserMessageAuthenticationException::class);
        $this->expectExceptionMessage('Invalid token');

        $request = new Request();
        $request->headers->set('Authorization', 'Bearer invalid_token');

        $this->tokenRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['token' => 'invalid_token'])
            ->willReturn(null);

        $this->authenticator->authenticate($request);
    }

    public function testAuthenticateWithExpiredToken(): void
    {
        $this->expectException(CustomUserMessageAuthenticationException::class);
        $this->expectExceptionMessage('Token expired');

        $user = new Users();
        $user->setLogin('testuser');

        $accessToken = new OAuth2AccessToken();
        $accessToken->setToken('expired_token');
        $accessToken->setUser($user);
        $accessToken->setExpiresAt(new \DateTime('-1 hour'));

        $request = new Request();
        $request->headers->set('Authorization', 'Bearer expired_token');

        $this->tokenRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['token' => 'expired_token'])
            ->willReturn($accessToken);

        $this->authenticator->authenticate($request);
    }

    public function testAuthenticateWithTokenWithoutUser(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Aucun utilisateur associé à ce jeton d\'accès.');

        $accessToken = new OAuth2AccessToken();
        $accessToken->setToken('token_without_user');
        $accessToken->setExpiresAt(new \DateTime('+1 hour'));

        $request = new Request();
        $request->headers->set('Authorization', 'Bearer token_without_user');

        $this->tokenRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['token' => 'token_without_user'])
            ->willReturn($accessToken);

        $this->authenticator->authenticate($request);
    }

    public function testOnAuthenticationSuccess(): void
    {
        $request = new Request();
        $token = $this->createMock(TokenInterface::class);

        $response = $this->authenticator->onAuthenticationSuccess($request, $token, 'main');
        $this->assertNull($response);
    }

    public function testOnAuthenticationFailure(): void
    {
        $request = new Request();
        $exception = new AuthenticationException('An authentication exception occurred.');

        $response = $this->authenticator->onAuthenticationFailure($request, $exception);

        $this->assertNotNull($response, 'Response should not be null');
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(401, $response->getStatusCode());

        $content = $response->getContent();
        $this->assertIsString($content, 'Response content should be a string');

        $contentArray = json_decode($content, true);
        $this->assertNotNull($contentArray, 'Content should be valid JSON');
        $this->assertEquals('An authentication exception occurred.', $contentArray['message']);
    }
}