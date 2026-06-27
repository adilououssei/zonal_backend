<?php

namespace App\Controller\Admin;

use App\Entity\News;
use App\Repository\NewsRepository;
use App\Service\LocaleHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/admin/news')]
class NewsController extends AbstractController
{
    public function __construct(
        private LocaleHelper $localeHelper,
    ) {
    }
    #[Route('', name: 'admin_news_list', methods: ['GET'])]
    public function index(NewsRepository $newsRepository): JsonResponse
    {
        $news = $newsRepository->findAllOrderedByDate();
        $data = array_map(fn (News $n) => $this->serializeNews($n), $news);
        return $this->json($data);
    }

    #[Route('/{id}', name: 'admin_news_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(News $news): JsonResponse
    {
        return $this->json($this->serializeNews($news));
    }

    #[Route('', name: 'admin_news_create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!$data || empty($data['title']) || empty($data['date']) || empty($data['category'])) {
            return $this->json(['error' => 'Titre, date et catégorie sont requis.'], Response::HTTP_BAD_REQUEST);
        }

        $news = new News();
        $news->setCreatedBy($this->getUser());
        $news->setTitle($data['title']);
        $news->setTitleEn($data['titleEn'] ?? null);
        $news->setExcerpt($data['excerpt'] ?? null);
        $news->setExcerptEn($data['excerptEn'] ?? null);
        $news->setContent($data['content'] ?? null);
        $news->setContentEn($data['contentEn'] ?? null);
        $news->setCategory($data['category']);
        $news->setCategoryEn($data['categoryEn'] ?? null);
        $news->setAuthor($data['author'] ?? null);
        $news->setAuthorEn($data['authorEn'] ?? null);
        $news->setCoverImage($data['coverImage'] ?? null);
        $news->setGallery($data['gallery'] ?? null);
        $news->setViews($data['views'] ?? 0);

        try {
            $news->setDate(new \DateTimeImmutable($data['date']));
        } catch (\Exception) {
            return $this->json(['error' => 'Format de date invalide (attendu: YYYY-MM-DD).'], Response::HTTP_BAD_REQUEST);
        }

        $em->persist($news);
        $em->flush();

        return $this->json($this->serializeNews($news), Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'admin_news_update', methods: ['PUT'], requirements: ['id' => '\d+'])]
    public function update(Request $request, News $news, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return $this->json(['error' => 'Données requises.'], Response::HTTP_BAD_REQUEST);
        }

        if (isset($data['title'])) $news->setTitle($data['title']);
        if (isset($data['titleEn'])) $news->setTitleEn($data['titleEn']);
        if (isset($data['excerpt'])) $news->setExcerpt($data['excerpt']);
        if (isset($data['excerptEn'])) $news->setExcerptEn($data['excerptEn']);
        if (isset($data['content'])) $news->setContent($data['content']);
        if (isset($data['contentEn'])) $news->setContentEn($data['contentEn']);
        if (isset($data['category'])) $news->setCategory($data['category']);
        if (isset($data['categoryEn'])) $news->setCategoryEn($data['categoryEn']);
        if (isset($data['author'])) $news->setAuthor($data['author']);
        if (isset($data['authorEn'])) $news->setAuthorEn($data['authorEn']);
        if (isset($data['coverImage'])) $news->setCoverImage($data['coverImage']);
        if (isset($data['gallery'])) $news->setGallery($data['gallery']);
        if (isset($data['views'])) $news->setViews($data['views']);
        if (isset($data['date'])) {
            try {
                $news->setDate(new \DateTimeImmutable($data['date']));
            } catch (\Exception) {
                return $this->json(['error' => 'Format de date invalide (attendu: YYYY-MM-DD).'], Response::HTTP_BAD_REQUEST);
            }
        }

        $em->flush();

        return $this->json($this->serializeNews($news));
    }

    #[Route('/{id}', name: 'admin_news_delete', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function delete(News $news, EntityManagerInterface $em): JsonResponse
    {
        $em->remove($news);
        $em->flush();

        return $this->json(['message' => 'Article supprimé.']);
    }

    #[Route('/{id}/views', name: 'admin_news_increment_views', methods: ['PATCH'], requirements: ['id' => '\d+'])]
    public function incrementViews(News $news, EntityManagerInterface $em): JsonResponse
    {
        $news->setViews($news->getViews() + 1);
        $em->flush();
        return $this->json($this->serializeNews($news));
    }

    private function serializeNews(News $news): array
    {
        $date = $news->getDate();

        return [
            'id' => $news->getId(),
            'title' => $this->localeHelper->localize($news, 'title'),
            'titleEn' => $news->getTitleEn(),
            'excerpt' => $this->localeHelper->localize($news, 'excerpt'),
            'excerptEn' => $news->getExcerptEn(),
            'content' => $this->localeHelper->localize($news, 'content'),
            'contentEn' => $news->getContentEn(),
            'date' => $date?->format('Y-m-d'),
            'category' => $this->localeHelper->localize($news, 'category'),
            'categoryEn' => $news->getCategoryEn(),
            'author' => $this->localeHelper->localize($news, 'author'),
            'authorEn' => $news->getAuthorEn(),
            'coverImage' => $news->getCoverImage(),
            'gallery' => $news->getGallery(),
            'views' => $news->getViews(),
            'createdAt' => $news->getCreatedAt()?->format('c'),
            'updatedAt' => $news->getUpdatedAt()?->format('c'),
            'createdBy' => $news->getCreatedBy() ? [
                'id' => $news->getCreatedBy()->getId(),
                'name' => trim(($news->getCreatedBy()->getFirstName() ?? '') . ' ' . ($news->getCreatedBy()->getLastName() ?? '')),
            ] : null,
        ];
    }
}
