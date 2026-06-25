<?php

namespace App\Controller;

use App\Entity\Testimonial;
use App\Repository\TestimonialRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/testimonials')]
class TestimonialController extends AbstractController
{
    #[Route('', name: 'public_testimonials_list', methods: ['GET'])]
    public function index(TestimonialRepository $testimonialRepository): JsonResponse
    {
        $testimonials = $testimonialRepository->findByStatus('published');
        $data = array_map(fn (Testimonial $t) => $this->serialize($t), $testimonials);
        return $this->json($data);
    }

    private function serialize(Testimonial $testimonial): array
    {
        return [
            'id' => $testimonial->getId(),
            'author' => $testimonial->getAuthor(),
            'role' => $testimonial->getRole(),
            'content' => $testimonial->getContent(),
            'rating' => $testimonial->getRating(),
            'avatar' => $testimonial->getAvatar(),
            'date' => $testimonial->getDate()?->format('Y-m-d'),
            'status' => $testimonial->getStatus(),
        ];
    }
}
