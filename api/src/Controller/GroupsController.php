<?php

namespace App\Controller;

use App\Entity\Formation;
use App\Entity\Groups;
use App\Entity\CourseTypes;
use App\Repository\GroupsRepository;
use App\Service\SchoolYearService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/groups', name: 'api_groups_')]
class GroupsController extends AbstractController
{

    public function __construct(
        private readonly SchoolYearService $schoolYearService
    ) {
    }

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(Request $request, GroupsRepository $repository): JsonResponse
    {
        $formationId = $request->query->get('id_formation');
        $schoolYearId = $request->query->get('id_school_year');

        if ($schoolYearId === null) {
            $schoolYear = $this->schoolYearService->getCurrentSchoolYear();

            if ($schoolYear instanceof JsonResponse) {
                return $schoolYear;
            }
            $schoolYearId = $schoolYear->getId();
        }

        $groups = $repository->findByFilters(
            $formationId ? (int) $formationId : null,
            $schoolYearId ? (int) $schoolYearId : null
        );

        $data = array_map(fn(Groups $group) => [
            'id' => $group->getId(),
            'name' => $group->getName(),
            'description' => $group->getDescription(),
            'order_number' => $group->getOrderNumber(),
            'id_parent_group' => $group->getIdGroups()?->getId(),
            'id_course_types' => $group->getIdCourseTypes()?->getId(),
            'id_formation' => $group->getIdFormation()?->getId(),
        ], $groups);

        return $this->json($data, Response::HTTP_OK);
    }



    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Groups $group): JsonResponse
    {
        $data = [
            'id' => $group->getId(),
            'name' => $group->getName(),
            'description' => $group->getDescription(),
            'order_number' => $group->getOrderNumber(),
            'id_parent_group' => $group->getIdGroups()?->getId(),
            'id_course_types' => $group->getIdCourseTypes()?->getId(),
            'id_formation' => $group->getIdFormation()?->getId(),
        ];

        return $this->json($data, Response::HTTP_OK);
    }


    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if ($data === null) {
            return $this->json(['error' => 'Invalid JSON data'], Response::HTTP_BAD_REQUEST);
        }

        if (empty($data['name'])) {
            return $this->json(['error' => 'The "name" field is required'], Response::HTTP_BAD_REQUEST);
        }

        $group = new Groups();
        $group->setName($data['name']);
        $group->setDescription($data['description'] ?? null);
        $group->setOrderNumber(isset($data['order_number']) ? (int) $data['order_number'] : null);

        if (isset($data['id_parent_group'])) {
            $parentGroup = $em->getRepository(Groups::class)->find((int) $data['id_parent_group']);
            $group->setIdGroups($parentGroup);
        }

        if (isset($data['id_course_types'])) {
            $courseType = $em->getRepository(CourseTypes::class)->find((int) $data['id_course_types']);
            $group->setIdCourseTypes($courseType);
        }

        if (isset($data['id_formation'])) {
            $formation = $em->getRepository(Formation::class)->find((int) $data['id_formation']);
            $group->setIdFormation($formation);
        }

        $em->persist($group);
        $em->flush();

        return $this->json([
            'id' => $group->getId(),
            'name' => $group->getName(),
            'description' => $group->getDescription(),
            'order_number' => $group->getOrderNumber(),
            'id_parent_group' => $group->getIdGroups()?->getId(),
            'id_course_types' => $group->getIdCourseTypes()?->getId(),
            'id_formation' => $group->getIdFormation()?->getId(),
        ], Response::HTTP_CREATED);
    }


    #[Route('/{id}', name: 'update', methods: ['PUT'])]
    public function update(Request $request, Groups $group, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if ($data === null) {
            return $this->json(['error' => 'Invalid JSON data'], Response::HTTP_BAD_REQUEST);
        }

        if (isset($data['name'])) {
            $group->setName($data['name']);
        }

        if (isset($data['description'])) {
            $group->setDescription($data['description']);
        }

        if (isset($data['order_number'])) {
            $group->setOrderNumber((int) $data['order_number']);
        }

        if (isset($data['id_parent_group'])) {
            $parentGroup = $em->getRepository(Groups::class)->find((int) $data['id_parent_group']);
            $group->setIdGroups($parentGroup);
        }

        if (isset($data['id_course_types'])) {
            $courseType = $em->getRepository(CourseTypes::class)->find((int) $data['id_course_types']);
            $group->setIdCourseTypes($courseType);
        }

        if (isset($data['id_formation'])) {
            $formation = $em->getRepository(Formation::class)->find((int) $data['id_formation']);
            $group->setIdFormation($formation);
        }

        $em->flush();

        return $this->json(['message' => 'Group updated successfully'], Response::HTTP_OK);
    }


    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(Groups $group, EntityManagerInterface $em): JsonResponse
    {
        $em->remove($group);
        $em->flush();

        return $this->json(['message' => 'Group deleted successfully'], Response::HTTP_NO_CONTENT);
    }
}
