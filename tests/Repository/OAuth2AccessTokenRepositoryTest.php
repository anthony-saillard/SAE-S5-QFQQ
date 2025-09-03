<?php

namespace App\Tests\Repository;

use App\Repository\OAuth2AccessTokenRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class OAuth2AccessTokenRepositoryTest extends KernelTestCase
{
    private ?EntityManagerInterface $entityManager;
    private OAuth2AccessTokenRepository $repository;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();

        /** @var ManagerRegistry $doctrine */
        $doctrine = $kernel->getContainer()->get('doctrine');

        /** @var EntityManagerInterface $entityManager */
        $entityManager = $doctrine->getManager();
        $this->entityManager = $entityManager;

        $this->entityManager->beginTransaction();

        /** @var OAuth2AccessTokenRepository repository */
        $repository = static::getContainer()->get(OAuth2AccessTokenRepository::class);
        $this->repository = $repository;
    }

    public function testConstruct(): void
    {
        $this->assertInstanceOf(OAuth2AccessTokenRepository::class, $this->repository);
    }

    protected function tearDown(): void
    {
        if ($this->entityManager !== null) {
            $this->entityManager->rollback();
            $this->entityManager->close();
            $this->entityManager = null;
        }

        parent::tearDown();
    }
}
