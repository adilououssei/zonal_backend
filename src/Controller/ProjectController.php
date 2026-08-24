<?php

namespace App\Controller;

use App\Entity\Project;
use App\Repository\ProjectRepository;
use App\Service\LocaleHelper;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

// API publique (accès libre, voir security.yaml) : projets de l'ONG affichés sur le site.
#[Route('/api/projects')]
class ProjectController extends AbstractController
{
    public function __construct(
        private LocaleHelper $localeHelper,
    ) {
    }
    #[Route('', name: 'public_projects_list', methods: ['GET'])]
    public function index(ProjectRepository $projectRepository): JsonResponse
    {
        $projects = $projectRepository->findAllOrderedByDate('DESC');
        $data = array_map(fn (Project $p) => $this->serializeProject($p), $projects);
        return $this->json($data);
    }

    #[Route('/completed', name: 'public_projects_completed', methods: ['GET'])]
    public function completed(ProjectRepository $projectRepository): JsonResponse
    {
        $projects = $projectRepository->findByStatus('completed');
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
            'title' => $this->localeHelper->localize($project, 'title'),
            'description' => $this->localeHelper->localize($project, 'description'),
            'image' => $project->getImage(),
            'location' => $this->localeHelper->localize($project, 'location'),
            'startDate' => $project->getStartDate()?->format('Y-m-d'),
            'endDate' => $project->getEndDate()?->format('Y-m-d'),
            'status' => $project->getStatus(),
        ];
    }
}
