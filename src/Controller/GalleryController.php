<?php

namespace App\Controller;

use App\Entity\Gallery;
use App\Repository\GalleryRepository;
use App\Service\LocaleHelper;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

// API publique (accès libre, voir security.yaml) : galerie photo affichée sur le site.
#[Route('/api/gallery')]
class GalleryController extends AbstractController
{
    public function __construct(
        private LocaleHelper $localeHelper,
    ) {
    }
    #[Route('', name: 'public_gallery_list', methods: ['GET'])]
    public function index(GalleryRepository $galleryRepository): JsonResponse
    {
        $items = $galleryRepository->findAllOrderedByDate('DESC');
        $data = array_map(fn (Gallery $g) => $this->serialize($g), $items);
        return $this->json($data);
    }

    #[Route('/{id}', name: 'public_gallery_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(Gallery $gallery): JsonResponse
    {
        return $this->json($this->serialize($gallery));
    }

    private function serialize(Gallery $gallery): array
    {
        $images = $gallery->getImages();
        return [
            'id' => $gallery->getId(),
            'title' => $this->localeHelper->localize($gallery, 'title'),
            'src' => $gallery->getSrc(),
            'category' => $gallery->getCategory(),
            'date' => $gallery->getDate()?->format('Y-m-d'),
            'images' => $images,
            // Nombre de photos de l'album ; 1 par défaut quand il n'y a qu'une image unique (pas d'album)
            'imageCount' => $images ? count($images) : 1,
        ];
    }
}
