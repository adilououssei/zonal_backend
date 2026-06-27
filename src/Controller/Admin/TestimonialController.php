<?php

namespace App\Controller\Admin;

use App\Entity\Testimonial;
use App\Repository\TestimonialRepository;
use App\Service\LocaleHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/admin/testimonials')]
class TestimonialController extends AbstractController
{
    public function __construct(
        private LocaleHelper $localeHelper,
    ) {
    }
    #[Route('', name: 'admin_testimonials_list', methods: ['GET'])]
    public function index(TestimonialRepository $testimonialRepository): JsonResponse
    {
        $testimonials = $testimonialRepository->findAllOrdered();
        $data = array_map(fn (Testimonial $t) => $this->serialize($t), $testimonials);
        return $this->json($data);
    }

    #[Route('/{id}', name: 'admin_testimonials_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(Testimonial $testimonial): JsonResponse
    {
        return $this->json($this->serialize($testimonial));
    }

    #[Route('', name: 'admin_testimonials_create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!$data || empty($data['author']) || empty($data['content'])) {
            return $this->json(['error' => 'L\'auteur et le contenu sont requis.'], Response::HTTP_BAD_REQUEST);
        }

        $testimonial = new Testimonial();
        $testimonial->setCreatedBy($this->getUser());
        $testimonial->setAuthor($data['author']);
        $testimonial->setAuthorEn($data['authorEn'] ?? null);
        $testimonial->setRole($data['role'] ?? null);
        $testimonial->setRoleEn($data['roleEn'] ?? null);
        $testimonial->setContent($data['content']);
        $testimonial->setContentEn($data['contentEn'] ?? null);
        $testimonial->setRating($data['rating'] ?? 5);
        $testimonial->setAvatar($data['avatar'] ?? null);
        if (isset($data['date'])) {
            $testimonial->setDate(new \DateTimeImmutable($data['date']));
        }
        $testimonial->setStatus($data['status'] ?? 'published');

        $em->persist($testimonial);
        $em->flush();

        return $this->json($this->serialize($testimonial), Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'admin_testimonials_update', methods: ['PUT'], requirements: ['id' => '\d+'])]
    public function update(Request $request, Testimonial $testimonial, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return $this->json(['error' => 'Données requises.'], Response::HTTP_BAD_REQUEST);
        }

        if (isset($data['author'])) $testimonial->setAuthor($data['author']);
        if (isset($data['authorEn'])) $testimonial->setAuthorEn($data['authorEn']);
        if (isset($data['role'])) $testimonial->setRole($data['role']);
        if (isset($data['roleEn'])) $testimonial->setRoleEn($data['roleEn']);
        if (isset($data['content'])) $testimonial->setContent($data['content']);
        if (isset($data['contentEn'])) $testimonial->setContentEn($data['contentEn']);
        if (isset($data['rating'])) $testimonial->setRating($data['rating']);
        if (isset($data['avatar'])) $testimonial->setAvatar($data['avatar']);
        if (isset($data['date'])) $testimonial->setDate(new \DateTimeImmutable($data['date']));
        if (isset($data['status'])) $testimonial->setStatus($data['status']);

        $em->flush();

        return $this->json($this->serialize($testimonial));
    }

    #[Route('/{id}', name: 'admin_testimonials_delete', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function delete(Testimonial $testimonial, EntityManagerInterface $em): JsonResponse
    {
        $em->remove($testimonial);
        $em->flush();
        return $this->json(['message' => 'Témoignage supprimé.']);
    }

    private function serialize(Testimonial $testimonial): array
    {
        return [
            'id' => $testimonial->getId(),
            'author' => $this->localeHelper->localize($testimonial, 'author'),
            'authorEn' => $testimonial->getAuthorEn(),
            'role' => $this->localeHelper->localize($testimonial, 'role'),
            'roleEn' => $testimonial->getRoleEn(),
            'content' => $this->localeHelper->localize($testimonial, 'content'),
            'contentEn' => $testimonial->getContentEn(),
            'rating' => $testimonial->getRating(),
            'avatar' => $testimonial->getAvatar(),
            'date' => $testimonial->getDate()?->format('Y-m-d'),
            'status' => $testimonial->getStatus(),
            'createdAt' => $testimonial->getCreatedAt()?->format('c'),
            'updatedAt' => $testimonial->getUpdatedAt()?->format('c'),
            'createdBy' => $testimonial->getCreatedBy() ? [
                'id' => $testimonial->getCreatedBy()->getId(),
                'name' => trim(($testimonial->getCreatedBy()->getFirstName() ?? '') . ' ' . ($testimonial->getCreatedBy()->getLastName() ?? '')),
            ] : null,
        ];
    }
}
