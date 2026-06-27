<?php

namespace App\Controller;

use App\Entity\Testimonial;
use App\Repository\TestimonialRepository;
use App\Service\LocaleHelper;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/testimonials')]
class TestimonialController extends AbstractController
{
    public function __construct(
        private LocaleHelper $localeHelper,
    ) {
    }
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
            'author' => $this->localeHelper->localize($testimonial, 'author'),
            'role' => $this->localeHelper->localize($testimonial, 'role'),
            'content' => $this->localeHelper->localize($testimonial, 'content'),
            'rating' => $testimonial->getRating(),
            'avatar' => $testimonial->getAvatar(),
            'date' => $testimonial->getDate()?->format('Y-m-d'),
            'status' => $testimonial->getStatus(),
        ];
    }
}
