<?php

namespace App\Controller\Admin;

use App\Entity\Project;
use App\Repository\ProjectRepository;
use App\Service\DeepLTranslator;
use App\Service\LocaleHelper;
use App\Service\NewsletterNotifier;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

// CRUD admin des projets. À la création, un email est envoyé aux abonnés
// newsletter (NewsletterNotifier) et les champs anglais manquants sont
// traduits automatiquement (DeepLTranslator) si le service est configuré.
#[Route('/api/admin/projects')]
#[IsGranted('MODULE_PROJECTS')]
class ProjectController extends AbstractController
{
    public function __construct(
        private LocaleHelper $localeHelper,
        private NewsletterNotifier $newsletterNotifier,
        private DeepLTranslator $translator,
    ) {
    }

    /**
     * Remplit automatiquement les champs *En manquants par traduction FR -> EN.
     */
    private function autoTranslate(Project $project): void
    {
        if (!$this->translator->isConfigured()) return;

        if (!$project->getTitleEn() && $project->getTitle()) {
            $project->setTitleEn($this->translator->translateToEnglish($project->getTitle()));
        }
        if (!$project->getDescriptionEn() && $project->getDescription()) {
            $project->setDescriptionEn($this->translator->translateToEnglish($project->getDescription(), isHtml: true));
        }
        if (!$project->getLocationEn() && $project->getLocation()) {
            $project->setLocationEn($this->translator->translateToEnglish($project->getLocation()));
        }
    }
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
        $project->setCreatedBy($this->getUser());
        $project->setTitle($data['title']);
        $project->setTitleEn($data['titleEn'] ?? null);
        $project->setDescription($data['description'] ?? null);
        $project->setDescriptionEn($data['descriptionEn'] ?? null);
        $project->setImage($data['image'] ?? null);
        $project->setLocation($data['location']);
        $project->setLocationEn($data['locationEn'] ?? null);
        $project->setBudget($data['budget'] ?? null);
        $project->setStatus($data['status'] ?? 'planned');

        // Les dates sont optionnelles : on ignore silencieusement un format invalide
        // plutôt que de bloquer la création du projet pour ce détail
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

        $this->autoTranslate($project);

        $em->persist($project);
        $em->flush();

        // Notifie tous les abonnés actifs de la newsletter par email
        $this->newsletterNotifier->notifyNewContent(
            'Nouveau projet',
            $project->getTitle(),
            $this->excerptFromHtml($project->getDescription()),
            '/projects/' . $project->getId(),
            $project->getImage(),
        );

        return $this->json($this->serializeProject($project), Response::HTTP_CREATED);
    }

    // Extrait un court résumé en texte brut à partir d'une description HTML (pour l'email de notification)
    private function excerptFromHtml(?string $html, int $maxLength = 160): ?string
    {
        if (!$html) return null;
        $text = trim(strip_tags($html));
        if ($text === '') return null;
        return mb_strlen($text) > $maxLength ? mb_substr($text, 0, $maxLength) . '…' : $text;
    }

    #[Route('/{id}', name: 'admin_projects_update', methods: ['PUT'], requirements: ['id' => '\d+'])]
    public function update(Request $request, Project $project, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return $this->json(['error' => 'Données requises.'], Response::HTTP_BAD_REQUEST);
        }

        if (isset($data['title'])) $project->setTitle($data['title']);
        if (isset($data['titleEn'])) $project->setTitleEn($data['titleEn']);
        if (isset($data['description'])) $project->setDescription($data['description']);
        if (isset($data['descriptionEn'])) $project->setDescriptionEn($data['descriptionEn']);
        if (isset($data['image'])) $project->setImage($data['image']);
        if (isset($data['location'])) $project->setLocation($data['location']);
        if (isset($data['locationEn'])) $project->setLocationEn($data['locationEn']);
        if (isset($data['budget'])) $project->setBudget($data['budget']);
        if (isset($data['status'])) $project->setStatus($data['status']);
        if (isset($data['startDate'])) {
            try { $project->setStartDate(new \DateTimeImmutable($data['startDate'])); } catch (\Exception) {}
        }
        if (isset($data['endDate'])) {
            try { $project->setEndDate(new \DateTimeImmutable($data['endDate'])); } catch (\Exception) {}
        }

        $this->autoTranslate($project);

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
            'title' => $this->localeHelper->localize($project, 'title'),
            'titleEn' => $project->getTitleEn(),
            'description' => $this->localeHelper->localize($project, 'description'),
            'descriptionEn' => $project->getDescriptionEn(),
            'image' => $project->getImage(),
            'location' => $this->localeHelper->localize($project, 'location'),
            'locationEn' => $project->getLocationEn(),
            'budget' => $project->getBudget(),
            'startDate' => $project->getStartDate()?->format('Y-m-d'),
            'endDate' => $project->getEndDate()?->format('Y-m-d'),
            'status' => $project->getStatus(),
            'createdAt' => $project->getCreatedAt()?->format('c'),
            'updatedAt' => $project->getUpdatedAt()?->format('c'),
            'createdBy' => $project->getCreatedBy() ? [
                'id' => $project->getCreatedBy()->getId(),
                'name' => trim(($project->getCreatedBy()->getFirstName() ?? '') . ' ' . ($project->getCreatedBy()->getLastName() ?? '')),
            ] : null,
        ];
    }
}
