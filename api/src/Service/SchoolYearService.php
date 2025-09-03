<?php

namespace App\Service;

use App\Entity\SchoolYear;
use App\Repository\SchoolYearRepository;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class SchoolYearService
{
    private RequestStack $requestStack;
    private SchoolYearRepository $schoolYearRepository;

    public function __construct(RequestStack $requestStack, SchoolYearRepository $schoolYearRepository)
    {
        $this->requestStack = $requestStack;
        $this->schoolYearRepository = $schoolYearRepository;
    }

    public function getCurrentSchoolYear(): JsonResponse | SchoolYear
    {
        $request = $this->requestStack->getCurrentRequest();
        $schoolYearId = $request?->headers->get('School-Year');

        if (!$schoolYearId) {
            $currentSchoolYear = $this->schoolYearRepository->findOneBy(['current_school_year' => true]);

            if (!$currentSchoolYear) {
                return new JsonResponse([
                    'status' => 'error',
                    'message' => 'No current school year found',
                ], Response::HTTP_NOT_FOUND);
            }

            $schoolYearId = $currentSchoolYear->getId();
        }

        $schoolYear = $this->schoolYearRepository->find($schoolYearId);

        if (!$schoolYear) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Invalid school year ID',
            ], Response::HTTP_BAD_REQUEST);
        }

        return $schoolYear;
    }
}
