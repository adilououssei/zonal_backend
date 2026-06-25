<?php

namespace App\Controller\Admin;

use App\Entity\Project;
use App\Repository\ProjectRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/admin/projects')]
class ProjectController extends AbstractController
{
    #[Route('', name: 'admin_projects_list', methods: ['GET'])]
    public function index(ProjectRepository $projectRepository): JsonResponse
    {
        $projects = $projectRepository->findAllOrderedByDate();
        $data = array_map(fn (Project $p) => $this->serializeProject($p), $projects);
        return $this->json($data);
    }

    #[Route('/{id}', name: 'admin_projects_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(Project $project): JsonResponse
    {
        return $this->json($this->serializeProject($project));
    }

    #[Route('', name: 'admin_projects_create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!$data || empty($data['title']) || empty($data['location'])) {
            return $this->json(['error' => 'Titre et lieu sont requis.'], Response::HTTP_BAD_REQUEST);
        }

        $project = new Project();
        $project->setTitle($data['title']);
        $project->setDescription($data['description'] ?? null);
        $project->setImage($data['image'] ?? null);
        $project->setLocation($data['location']);
        $project->setBudget($data['budget'] ?? null);
        $project->setStatus($data['status'] ?? 'planned');

        if (!empty($data['startDate'])) {
            try {
                $project->setStartDate(new \DateTimeImmutable($data['startDate']));
            } catch (\Exception) {}
        }
        if (!empty($data['endDate'])) {
            try {
                $project->setEndDate(new \DateTimeImmutable($data['endDate']));
            } catch (\Exception) {}
        }

        $em->persist($project);
        $em->flush();

        return $this->json($this->serializeProject($project), Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'admin_projects_update', methods: ['PUT'], requirements: ['id' => '\d+'])]
    public function update(Request $request, Project $project, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return $this->json(['error' => 'Données requises.'], Response::HTTP_BAD_REQUEST);
        }

        if (isset($data['title'])) $project->setTitle($data['title']);
        if (isset($data['description'])) $project->setDescription($data['description']);
        if (isset($data['image'])) $project->setImage($data['image']);
        if (isset($data['location'])) $project->setLocation($data['location']);
        if (isset($data['budget'])) $project->setBudget($data['budget']);
        if (isset($data['status'])) $project->setStatus($data['status']);
        if (isset($data['startDate'])) {
            try { $project->setStartDate(new \DateTimeImmutable($data['startDate'])); } catch (\Exception) {}
        }
        if (isset($data['endDate'])) {
            try { $project->setEndDate(new \DateTimeImmutable($data['endDate'])); } catch (\Exception) {}
        }

        $em->flush();

        return $this->json($this->serializeProject($project));
    }

    #[Route('/{id}', name: 'admin_projects_delete', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function delete(Project $project, EntityManagerInterface $em): JsonResponse
    {
        $em->remove($project);
        $em->flush();
        return $this->json(['message' => 'Projet supprimé.']);
    }

    private function serializeProject(Project $project): array
    {
        return [
            'id' => $project->getId(),
            'title' => $project->getTitle(),
            'description' => $project->getDescription(),
            'image' => $project->getImage(),
            'location' => $project->getLocation(),
            'budget' => $project->getBudget(),
            'startDate' => $project->getStartDate()?->format('Y-m-d'),
            'endDate' => $project->getEndDate()?->format('Y-m-d'),
            'status' => $project->getStatus(),
            'createdAt' => $project->getCreatedAt()?->format('c'),
            'updatedAt' => $project->getUpdatedAt()?->format('c'),
        ];
    }
}
