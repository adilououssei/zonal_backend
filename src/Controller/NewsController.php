<?php

namespace App\Controller;

use App\Entity\News;
use App\Repository\NewsRepository;
use App\Service\LocaleHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/news')]
class NewsController extends AbstractController
{
    public function __construct(
        private LocaleHelper $localeHelper,
    ) {
    }
    #[Route('', name: 'public_news_list', methods: ['GET'])]
    public function index(NewsRepository $newsRepository): JsonResponse
    {
        $news = $newsRepository->findAllOrderedByDate('DESC');
        $data = array_map(fn (News $n) => $this->serializeNews($n), $news);
        return $this->json($data);
    }

    #[Route('/{id}', name: 'public_news_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(News $news, EntityManagerInterface $em): JsonResponse
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
            'excerpt' => $this->localeHelper->localize($news, 'excerpt'),
            'content' => $this->localeHelper->localize($news, 'content'),
            'date' => $date?->format('Y-m-d'),
            'category' => $this->localeHelper->localize($news, 'category'),
            'author' => $this->localeHelper->localize($news, 'author'),
            'image' => $news->getCoverImage(),
            'gallery' => $news->getGallery(),
            'views' => $news->getViews(),
        ];
    }
}
