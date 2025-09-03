<?php

namespace App\Controller;

use App\Entity\Assignments;
use App\Repository\AssignmentsRepository;
use App\Repository\SubResourcesRepository;
use App\Repository\UsersRepository;
use App\Repository\CourseTypesRepository;
use App\Service\SchoolYearService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/assignments', name: 'api_assignments_')]
class AssignmentsController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function list(
        Request $request,
        AssignmentsRepository $assignmentsRepository
    ): JsonResponse {
        $userId = $request->query->get('id_user');
        $subResourceId = $request->query->get('id_sub_resource');
        $courseTypeId = $request->query->get('id_course_type');
        $semesterId = $request->query->get('id_semester');
        $dateStart = $request->query->get('date_start');
        $dateEnd = $request->query->get('date_end');

        $assignments = $assignmentsRepository->findByFilters(
            $subResourceId ? (int) $subResourceId : null,
            $userId ? (int) $userId : null,
            $courseTypeId ? (int) $courseTypeId : null,
            $semesterId ? (int) $semesterId : null,
            $dateStart ? (string) $dateStart : null,
            $dateEnd ? (string) $dateEnd : null,
        );

        $data = array_map(function($assignment) {
            $user = $assignment->getIdUsers();
            $subResource = $assignment->getIdSubResources();
            $resource = $subResource?->getIdResources();
            $semester = $resource?->getIdSemesters();
            $formation = $semester?->getIdFormation();
            $courseType = $assignment->getIdCourseTypes();

            return [
                'id' => $assignment->getId(),
                'allocated_hours' => $assignment->getAllocatedHours(),
                'assignment_date' => $assignment->getAssignmentDate()?->format('Y-m-d'),
                'annotation' => $assignment->getAnnotation(),
                'id_sub_resources' => $subResource?->getId(),
                'id_users' => $user?->getId(),
                'user_fullname' => $user ? ($user->getFirstname() . ' AssignmentsController.php' . $user->getLastname()) : null,
                'id_course_type' => $courseType?->getId(),
                'course_type_name' => $courseType?->getName(),
                'semester_id' => $semester?->getId(),
                'formation_id' => $formation?->getId(),
                'total_hours' => $resource?->getTotalHours(),
                'resource_name' => $resource ? ($resource->getIdentifier() . ' - ' . $resource->getName()) : null
            ];
        }, $assignments);

        return $this->json($data);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Assignments $assignment): JsonResponse
    {
        $data = [
            'id' => $assignment->getId(),
            'allocated_hours' => $assignment->getAllocatedHours(),
            'assignment_date' => $assignment->getAssignmentDate()?->format('Y-m-d'),
            'annotation' => $assignment->getAnnotation(),
            'id_sub_resources' => $assignment->getIdSubResources()?->getId(),
            'id_users' => $assignment->getIdUsers()?->getId(),
            'id_course_type' => $assignment->getIdCourseTypes()?->getId(),
            'notifications' => $assignment->getNotifications()->map(fn($notification) => $notification->getId())->toArray(),
        ];

        return $this->json($data, Response::HTTP_OK);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(
        Request $request,
        EntityManagerInterface $em,
        SubResourcesRepository $subResourcesRepository,
        UsersRepository $usersRepository,
        CourseTypesRepository $courseTypesRepository
    ): JsonResponse {
        /** @var array{allocated_hours?: float|null, assignment_date?: string|null, annotation?: string|null, id_sub_resources?: int|null, id_users?: int|null, id_course_type?: int|null}|null $data */        $data = json_decode($request->getContent(), true);

        if ($data === null) {
            return $this->json(['error' => 'Invalid JSON data'], Response::HTTP_BAD_REQUEST);
        }

        $assignment = new Assignments();

        if (isset($data['allocated_hours'])) {
            $assignment->setAllocatedHours((float) $data['allocated_hours']);
        }

        if (isset($data['assignment_date'])) {
            $assignment->setAssignmentDate(new \DateTime($data['assignment_date']));
        }

        if (isset($data['annotation'])) {
            $assignment->setAnnotation($data['annotation']);
        }

        if (isset($data['id_sub_resources'])) {
            $subResource = $subResourcesRepository->find((int) $data['id_sub_resources']);
            if ($subResource) {
                $assignment->setIdSubResources($subResource);
            } else {
                return $this->json(['error' => 'Invalid sub resource ID'], Response::HTTP_BAD_REQUEST);
            }
        }

        if (isset($data['id_users'])) {
            $user = $usersRepository->find((int) $data['id_users']);
            if ($user) {
                $assignment->setIdUsers($user);
            } else {
                return $this->json(['error' => 'Invalid user ID'], Response::HTTP_BAD_REQUEST);
            }
        }

        if (isset($data['id_course_type'])) {
            $courseType = $courseTypesRepository->find((int) $data['id_course_type']);
            if ($courseType) {
                $assignment->setIdCourseTypes($courseType);
            } else {
                return $this->json(['error' => 'Invalid course type ID'], Response::HTTP_BAD_REQUEST);
            }
        }

        $em->persist($assignment);
        $em->flush();

        return $this->json([
            'id' => $assignment->getId(),
            'allocated_hours' => $assignment->getAllocatedHours(),
            'assignment_date' => $assignment->getAssignmentDate()?->format('Y-m-d'),
            'annotation' => $assignment->getAnnotation(),
            'id_sub_resources' => $assignment->getIdSubResources()?->getId(),
            'id_users' => $assignment->getIdUsers()?->getId(),
            'id_course_type' => $assignment->getIdCourseTypes()?->getId(),
            'notifications' => []
        ], Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'update', methods: ['PUT'])]
    public function update(
        Request $request,
        Assignments $assignment,
        EntityManagerInterface $em,
        SubResourcesRepository $subResourcesRepository,
        UsersRepository $usersRepository,
        CourseTypesRepository $courseTypesRepository
    ): JsonResponse {
        /** @var array{allocated_hours?: float|null, assignment_date?: string|null, annotation?: string|null, id_sub_resources?: int|null, id_users?: int|null, id_course_type?: int|null}|null $data */
        $data = json_decode($request->getContent(), true);

        if ($data === null) {
            return $this->json(['error' => 'Invalid JSON data'], Response::HTTP_BAD_REQUEST);
        }

        if (isset($data['allocated_hours'])) {
            $assignment->setAllocatedHours((float) $data['allocated_hours']);
        }

        if (isset($data['assignment_date'])) {
            $assignment->setAssignmentDate(new \DateTime($data['assignment_date']));
        }

        if (isset($data['annotation'])) {
            $assignment->setAnnotation($data['annotation']);
        }

        if (isset($data['id_sub_resources'])) {
            $subResource = $subResourcesRepository->find((int) $data['id_sub_resources']);
            $assignment->setIdSubResources($subResource);
        }

        if (isset($data['id_users'])) {
            $user = $usersRepository->find((int) $data['id_users']);
            $assignment->setIdUsers($user);
        }

        if (isset($data['id_course_type'])) {
            $courseType = $courseTypesRepository->find((int) $data['id_course_type']);
            $assignment->setIdCourseTypes($courseType);
        }

        $em->flush();

        return $this->json(['message' => 'Assignment updated successfully'], Response::HTTP_OK);
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(Assignments $assignment, EntityManagerInterface $em): JsonResponse
    {
        $em->remove($assignment);
        $em->flush();

        return $this->json(['message' => 'Assignment deleted successfully'], Response::HTTP_NO_CONTENT);
    }
}