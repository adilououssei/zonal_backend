<?php

namespace App\Controller;

use App\Entity\Partner;
use App\Repository\PartnerRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/partners')]
class PartnerController extends AbstractController
{
    #[Route('', name: 'public_partners_list', methods: ['GET'])]
    public function index(PartnerRepository $partnerRepository): JsonResponse
    {
        $partners = $partnerRepository->findByStatus('active');
        $data = array_map(fn (Partner $p) => $this->serialize($p), $partners);
        return $this->json($data);
    }

    private function serialize(Partner $partner): array
    {
        return [
            'id' => $partner->getId(),
            'name' => $partner->getName(),
            'domain' => $partner->getDomain(),
            'email' => $partner->getEmail(),
            'phone' => $partner->getPhone(),
            'logo' => $partner->getLogo(),
            'status' => $partner->getStatus(),
        ];
    }
}
