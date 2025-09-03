<?php

namespace App\Tests\Controller;

use App\Controller\SchoolYearController;
use App\Entity\SchoolYear;
use App\Repository\SchoolYearRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\ConstraintViolationList;

class SchoolYearControllerTest extends TestCase
{
    private SchoolYearController $controller;
    /** @var MockObject&EntityManagerInterface */
    private MockObject $entityManager;
    /** @var MockObject&SchoolYearRepository */
    private MockObject $repository;
    /** @var MockObject&ValidatorInterface */
    private MockObject $validator;
    private SerializerInterface $serializer;
    private ContainerInterface $container;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->repository = $this->createMock(SchoolYearRepository::class);
        $this->validator = $this->createMock(ValidatorInterface::class);
        $this->serializer = $this->createMock(SerializerInterface::class);
        $this->container = $this->createMock(ContainerInterface::class);

        $this->serializer->expects($this->any())
            ->method('serialize')
            ->willReturnCallback(fn ($data) => json_encode($data));

        $this->container->expects($this->any())
            ->method('has')
            ->with('serializer')
            ->willReturn(true);

        $this->container->expects($this->any())
            ->method('get')
            ->with('serializer')
            ->willReturn($this->serializer);

        $this->controller = new SchoolYearController(
            $this->entityManager,
            $this->repository,
            $this->validator
        );
        $this->controller->setContainer($this->container);
    }

    public function testIndex(): void
    {
        $schoolYear = new SchoolYear();
        $schoolYear->setLabel('2023-2024');

        $this->repository
            ->expects($this->once())
            ->method('findAll')
            ->willReturn([$schoolYear]);

        $response = $this->controller->index();
        $this->assertIsString($response->getContent());
        $content = json_decode($response->getContent(), true);

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertEquals('2023-2024', $content[0]['label']);
        $this->assertEquals(null, $content[0]['current_school_year']);
    }

    public function testShow(): void
    {
        $schoolYear = new SchoolYear();
        $schoolYear->setLabel('2023-2024');

        $this->repository
            ->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($schoolYear);

        $response = $this->controller->show(1);

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertIsString($response->getContent());
        json_decode($response->getContent(), true);
    }

    public function testShowNotFound(): void
    {
        $this->repository
            ->expects($this->once())
            ->method('find')
            ->with(999)
            ->willReturn(null);

        $response = $this->controller->show(999);

        $this->assertEquals(Response::HTTP_NO_CONTENT, $response->getStatusCode());
        $this->assertIsString($response->getContent());
        $content = json_decode($response->getContent(), true);
        $this->assertEquals('School year not found', $content['message']);
    }

    public function testCreate(): void
    {
        $data = ['label' => '2024-2025'];
        $json = json_encode($data);
        if ($json === false) {
            throw new RuntimeException('Failed to encode data as JSON');
        }
        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            $json
        );

        $response = $this->controller->create($request);

        $this->assertIsString($response->getContent());
        $content = json_decode($response->getContent(), true);
        $this->assertEquals(Response::HTTP_CREATED, $response->getStatusCode());
        $this->assertEquals('2024-2025', $content['label']);
    }

    public function testCreateInvalidData(): void
    {
        $json = json_encode(['invalid' => 'data']);
        if ($json === false) {
            throw new RuntimeException('Failed to encode data as JSON');
        }
        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            $json
        );

        $response = $this->controller->create($request);

        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertIsString($response->getContent());
        $content = json_decode($response->getContent(), true);
        $this->assertEquals('error', $content['status']);
    }

    public function testUpdate(): void
    {
        $schoolYear = new SchoolYear();
        $schoolYear->setLabel('2023-2024');

        $this->repository
            ->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($schoolYear);

        $data = ['label' => '2023-2024 Updated'];
        $json = json_encode($data);
        if ($json === false) {
            throw new RuntimeException('Failed to encode data as JSON');
        }
        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            $json
        );

        $this->validator
            ->expects($this->once())
            ->method('validate')
            ->willReturn(new ConstraintViolationList());

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $response = $this->controller->update($request, 1);

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertIsString($response->getContent());
        $content = json_decode($response->getContent(), true);
        $this->assertEquals('success', $content['status']);
        $this->assertEquals('2023-2024 Updated', $content['data']['label']);
    }

    public function testDelete(): void
    {
        $schoolYear = new SchoolYear();
        $schoolYear->setLabel('To Delete');

        $this->repository
            ->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($schoolYear);

        $this->entityManager
            ->expects($this->once())
            ->method('remove')
            ->with($schoolYear);

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $response = $this->controller->delete(1);

        $this->assertEquals(Response::HTTP_NO_CONTENT, $response->getStatusCode());
    }

    public function testDuplicateSchoolYear(): void
    {
        $sourceYear = new SchoolYear();
        $sourceYear->setLabel('2023-2024');
        $sourceYear->setId(1);

        $this->repository
            ->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($sourceYear);

        $this->entityManager
            ->expects($this->once())
            ->method('persist')
            ->with($this->callback(function (SchoolYear $newYear) {
                return $newYear->getLabel() === '2024-2025' && !$newYear->isCurrentSchoolYear();
            }));

        $this->entityManager
            ->expects($this->exactly(1))
            ->method('flush');

        $data = [
            'label' => '2024-2025',
            'sourceYearId' => 1,
            'duplicationOptions' => []
        ];
        $json = json_encode($data);
        if ($json === false) {
            throw new RuntimeException('Failed to encode data as JSON');
        }

        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            $json
        );

        $response = $this->controller->duplicate($request);

        $this->assertEquals(Response::HTTP_CREATED, $response->getStatusCode());
        $this->assertIsString($response->getContent());
        $content = json_decode($response->getContent(), true);

        $this->assertEquals('success', $content['status']);
        $this->assertEquals('2024-2025', $content['data']['label']);
        $this->assertFalse($content['data']['current_school_year']);
    }

    public function testDuplicateSchoolYearWithMissingData(): void
    {
        $data = [
            'label' => '2024-2025'
        ];
        $json = json_encode($data);
        if ($json === false) {
            throw new RuntimeException('Failed to encode data as JSON');
        }

        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            $json
        );

        $response = $this->controller->duplicate($request);

        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertIsString($response->getContent());
        $content = json_decode($response->getContent(), true);

        $this->assertEquals('error', $content['status']);
        $this->assertStringContainsString('Données manquantes', $content['message']);
    }

    public function testDuplicateSchoolYearWithNonExistentSourceYear(): void
    {
        $this->repository
            ->expects($this->once())
            ->method('find')
            ->with(999)
            ->willReturn(null);

        $data = [
            'label' => '2024-2025',
            'sourceYearId' => 999,
            'duplicationOptions' => []
        ];
        $json = json_encode($data);
        if ($json === false) {
            throw new RuntimeException('Failed to encode data as JSON');
        }

        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            $json
        );

        $response = $this->controller->duplicate($request);

        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
        $this->assertIsString($response->getContent());
        $content = json_decode($response->getContent(), true);

        $this->assertEquals('error', $content['status']);
        $this->assertStringContainsString('Année source non trouvée', $content['message']);
    }
}