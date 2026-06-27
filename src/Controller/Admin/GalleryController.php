<?php

namespace App\Controller\Admin;

use App\Entity\Gallery;
use App\Repository\GalleryRepository;
use App\Service\LocaleHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/admin/gallery')]
class GalleryController extends AbstractController
{
    public function __construct(
        private LocaleHelper $localeHelper,
    ) {
    }
    #[Route('', name: 'admin_gallery_list', methods: ['GET'])]
    public function index(GalleryRepository $galleryRepository): JsonResponse
    {
        $items = $galleryRepository->findAllOrderedByDate();
        $data = array_map(fn (Gallery $g) => $this->serialize($g), $items);
        return $this->json($data);
    }

    #[Route('/{id}', name: 'admin_gallery_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(Gallery $gallery): JsonResponse
    {
        return $this->json($this->serialize($gallery));
    }

    #[Route('', name: 'admin_gallery_create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!$data || empty($data['title']) || empty($data['src'])) {
            return $this->json(['error' => 'Titre et image sont requis.'], Response::HTTP_BAD_REQUEST);
        }

        $gallery = new Gallery();
        $gallery->setCreatedBy($this->getUser());
        $gallery->setTitle($data['title']);
        $gallery->setTitleEn($data['titleEn'] ?? null);
        $gallery->setSrc($data['src']);
        $gallery->setCategory($data['category'] ?? '');
        $gallery->setImages($data['images'] ?? null);

        if (!empty($data['date'])) {
            try {
                $gallery->setDate(new \DateTimeImmutable($data['date']));
            } catch (\Exception) {}
        }

        $em->persist($gallery);
        $em->flush();

        return $this->json($this->serialize($gallery), Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'admin_gallery_update', methods: ['PUT'], requirements: ['id' => '\d+'])]
    public function update(Request $request, Gallery $gallery, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return $this->json(['error' => 'Données requises.'], Response::HTTP_BAD_REQUEST);
        }

        if (isset($data['title'])) $gallery->setTitle($data['title']);
        if (isset($data['titleEn'])) $gallery->setTitleEn($data['titleEn']);
        if (isset($data['src'])) $gallery->setSrc($data['src']);
        if (isset($data['category'])) $gallery->setCategory($data['category']);
        if (isset($data['date'])) {
            try { $gallery->setDate(new \DateTimeImmutable($data['date'])); } catch (\Exception) {}
        }
        if (array_key_exists('images', $data)) $gallery->setImages($data['images']);

        $em->flush();

        return $this->json($this->serialize($gallery));
    }

    #[Route('/{id}', name: 'admin_gallery_delete', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function delete(Gallery $gallery, EntityManagerInterface $em): JsonResponse
    {
        $em->remove($gallery);
        $em->flush();
        return $this->json(['message' => 'Image supprimée.']);
    }

    private function serialize(Gallery $gallery): array
    {
        $images = $gallery->getImages();
        return [
            'id' => $gallery->getId(),
            'title' => $this->localeHelper->localize($gallery, 'title'),
            'titleEn' => $gallery->getTitleEn(),
            'src' => $gallery->getSrc(),
            'category' => $gallery->getCategory(),
            'date' => $gallery->getDate()?->format('Y-m-d'),
            'images' => $images,
            'imageCount' => $images ? count($images) : 1,
            'createdAt' => $gallery->getCreatedAt()?->format('c'),
            'updatedAt' => $gallery->getUpdatedAt()?->format('c'),
            'createdBy' => $gallery->getCreatedBy() ? [
                'id' => $gallery->getCreatedBy()->getId(),
                'name' => trim(($gallery->getCreatedBy()->getFirstName() ?? '') . ' ' . ($gallery->getCreatedBy()->getLastName() ?? '')),
            ] : null,
        ];
    }
}
