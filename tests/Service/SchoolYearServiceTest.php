<?php

namespace App\Tests\Service;

use App\Entity\SchoolYear;
use App\Repository\SchoolYearRepository;
use App\Service\SchoolYearService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

class SchoolYearServiceTest extends TestCase
{
    /** @var MockObject&RequestStack */
    private MockObject $requestStack;

    /** @var MockObject&SchoolYearRepository */
    private MockObject $schoolYearRepository;

    private SchoolYearService $service;

    /** @var MockObject&Request */
    private MockObject $request;

    protected function setUp(): void
    {
        $this->requestStack = $this->getMockBuilder(RequestStack::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->schoolYearRepository = $this->getMockBuilder(SchoolYearRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->request = $this->getMockBuilder(Request::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->request->headers = new HeaderBag();

        $this->service = new SchoolYearService(
            $this->requestStack,
            $this->schoolYearRepository
        );
    }

    public function testGetCurrentSchoolYearFromHeader(): void
    {
        $schoolYear = new SchoolYear();
        $schoolYear->setId(1);

        $this->request->headers->set('School-Year', '1');

        $this->requestStack
            ->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($this->request);

        $this->schoolYearRepository
            ->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($schoolYear);

        $result = $this->service->getCurrentSchoolYear();

        $this->assertInstanceOf(SchoolYear::class, $result);
        $this->assertEquals(1, $result->getId());
    }

    public function testGetCurrentSchoolYearFromRepository(): void
    {
        $schoolYear = new SchoolYear();
        $schoolYear->setId(2);

        $this->requestStack
            ->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($this->request);

        $this->schoolYearRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['current_school_year' => true])
            ->willReturn($schoolYear);

        $this->schoolYearRepository
            ->expects($this->once())
            ->method('find')
            ->with(2)
            ->willReturn($schoolYear);

        $result = $this->service->getCurrentSchoolYear();

        $this->assertInstanceOf(SchoolYear::class, $result);
        $this->assertEquals(2, $result->getId());
    }

    public function testGetCurrentSchoolYearNoCurrentYear(): void
    {
        $this->requestStack
            ->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($this->request);

        $this->schoolYearRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['current_school_year' => true])
            ->willReturn(null);

        $result = $this->service->getCurrentSchoolYear();

        $this->assertInstanceOf(JsonResponse::class, $result);
        $this->assertEquals(Response::HTTP_NOT_FOUND, $result->getStatusCode());

        $content = json_decode((string) $result->getContent(), true);
        $this->assertEquals('error', $content['status']);
        $this->assertEquals('No current school year found', $content['message']);
    }

    public function testGetCurrentSchoolYearInvalidId(): void
    {
        $this->request->headers->set('School-Year', '999');

        $this->requestStack
            ->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($this->request);

        $this->schoolYearRepository
            ->expects($this->once())
            ->method('find')
            ->with(999)
            ->willReturn(null);

        $result = $this->service->getCurrentSchoolYear();

        $this->assertInstanceOf(JsonResponse::class, $result);
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $result->getStatusCode());

        $content = json_decode((string) $result->getContent(), true);
        $this->assertEquals('error', $content['status']);
        $this->assertEquals('Invalid school year ID', $content['message']);
    }
}
