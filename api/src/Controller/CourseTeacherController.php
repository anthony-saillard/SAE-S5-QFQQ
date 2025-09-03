<?php

namespace App\Controller;

use App\Entity\CourseTeacher;
use App\Entity\SubResources;
use App\Entity\Users;
use App\Entity\Groups;
use App\Repository\CourseTeacherRepository;
use App\Service\SchoolYearService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/course-teachers', name: 'api_course_teachers_')]
class CourseTeacherController extends AbstractController
{

    public function __construct(
        private readonly SchoolYearService $schoolYearService
    ) {
    }

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(Request $request, CourseTeacherRepository $repository): JsonResponse
    {
        $idGroup = $request->query->get('id_group') ? (int)$request->query->get('id_group') : null;
        $idSubResource = $request->query->get('id_sub_resource') ? (int)$request->query->get('id_sub_resource') : null;
        $idUser = $request->query->get('id_user') ? (int)$request->query->get('id_user') : null;
        $schoolYear = $this->schoolYearService->getCurrentSchoolYear();
        if ($schoolYear instanceof JsonResponse) {
            return $schoolYear;
        }
        $courseTeachers = $repository->findByFilters($idGroup, $idSubResource, $idUser, $schoolYear->getId());


        $data = array_map(fn($courseTeacher) => [
            'id' => $courseTeacher->getId(),
            'id_sub_resource' => $courseTeacher->getIdSubResource()?->getId(),
            'sub_resource_name' => $courseTeacher->getIdSubResource()?->getName(),
            'id_user' => $courseTeacher->getIdUser()?->getId(),
            'user_name' => $courseTeacher->getIdUser()?->getFirstName(),
            'id_group' => $courseTeacher->getIdGroups()?->getId(),
            'group_name' => $courseTeacher->getIdGroups()?->getName(),
        ], $courseTeachers);

        return $this->json($data, Response::HTTP_OK);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(CourseTeacher $courseTeacher): JsonResponse
    {
        $data = [
            'id' => $courseTeacher->getId(),
            'id_sub_resource' => $courseTeacher->getIdSubResource()?->getId(),
            'sub_resource_name' => $courseTeacher->getIdSubResource()?->getName(),
            'id_user' => $courseTeacher->getIdUser()?->getId(),
            'user_name' => $courseTeacher->getIdUser()?->getFirstName(),
            'id_group' => $courseTeacher->getIdGroups()?->getId(),
            'group_name' => $courseTeacher->getIdGroups()?->getName(),
        ];

        return $this->json($data, Response::HTTP_OK);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em): JsonResponse
    {
        /** @var array{id_sub_resource?: int|null, id_user?: int|null, id_group?: int|null}|null $data */
        $data = json_decode($request->getContent(), true);

        if ($data === null) {
            return $this->json(['error' => 'Invalid JSON data'], Response::HTTP_BAD_REQUEST);
        }

        $courseTeacher = new CourseTeacher();

        if (isset($data['id_sub_resource'])) {
            $subResource = $em->getRepository(SubResources::class)->find($data['id_sub_resource']);
            if (!$subResource) {
                return $this->json(['error' => 'SubResource not found'], 404);
            }
            $courseTeacher->setIdSubResource($subResource);
        }

        if (isset($data['id_user'])) {
            $user = $em->getRepository(Users::class)->find($data['id_user']);
            if (!$user) {
                return $this->json(['error' => 'User not found'], 404);
            }
            $courseTeacher->setIdUser($user);
        }

        if (isset($data['id_group'])) {
            $group = $em->getRepository(Groups::class)->find($data['id_group']);
            if (!$group) {
                return $this->json(['error' => 'Group not found'], 404);
            }
            $courseTeacher->setIdGroups($group);
        }

        $em->persist($courseTeacher);
        $em->flush();

        return $this->json([
            'id' => $courseTeacher->getId(),
            'id_sub_resource' => $courseTeacher->getIdSubResource()?->getId(),
            'id_user' => $courseTeacher->getIdUser()?->getId(),
            'id_group' => $courseTeacher->getIdGroups()?->getId(),
        ], Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'update', methods: ['PUT'])]
    public function update(Request $request, CourseTeacher $courseTeacher, EntityManagerInterface $em): JsonResponse
    {
        /** @var array{id_sub_resource?: int|null, id_user?: int|null, id_group?: int|null}|null $data */
        $data = json_decode($request->getContent(), true);

        if ($data === null) {
            return $this->json(['error' => 'Invalid JSON data'], 400);
        }

        if (isset($data['id_sub_resource'])) {
            $subResource = $em->getRepository(SubResources::class)->find($data['id_sub_resource']);
            if (!$subResource) {
                return $this->json(['error' => 'SubResource not found'], 404);
            }
            $courseTeacher->setIdSubResource($subResource);
        }

        if (isset($data['id_user'])) {
            $user = $em->getRepository(Users::class)->find($data['id_user']);
            if (!$user) {
                return $this->json(['error' => 'User not found'], 404);
            }
            $courseTeacher->setIdUser($user);
        }

        if (isset($data['id_group'])) {
            $group = $em->getRepository(Groups::class)->find($data['id_group']);
            if (!$group) {
                return $this->json(['error' => 'Group not found'], 404);
            }
            $courseTeacher->setIdGroups($group);
        }

        $em->flush();

        return $this->json(['message' => 'CourseTeacher updated successfully'], Response::HTTP_OK);
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(CourseTeacher $courseTeacher, EntityManagerInterface $em): JsonResponse
    {
        $em->remove($courseTeacher);
        $em->flush();

        return $this->json(['message' => 'CourseTeacher deleted successfully'], Response::HTTP_NO_CONTENT);
    }
}