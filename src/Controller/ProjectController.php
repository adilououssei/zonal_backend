<?php

namespace App\Controller;

use App\Entity\Project;
use App\Repository\ProjectRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/projects')]
class ProjectController extends AbstractController
{
    #[Route('', name: 'public_projects_list', methods: ['GET'])]
    public function index(ProjectRepository $projectRepository): JsonResponse
    {
        $projects = $projectRepository->findAllOrderedByDate('DESC');
        $data = array_map(fn (Project $p) => $this->serializeProject($p), $projects);
        return $this->json($data);
    }

    #[Route('/{id}', name: 'public_projects_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(Project $project): JsonResponse
    {
        return $this->json($this->serializeProject($project));
    }

    private function serializeProject(Project $project): array
    {
        return [
            'id' => $project->getId(),
            'title' => $project->getTitle(),
            'description' => $project->getDescription(),
            'image' => $project->getImage(),
            'location' => $project->getLocation(),
            'startDate' => $project->getStartDate()?->format('Y-m-d'),
            'endDate' => $project->getEndDate()?->format('Y-m-d'),
            'status' => $project->getStatus(),
        ];
    }
}
