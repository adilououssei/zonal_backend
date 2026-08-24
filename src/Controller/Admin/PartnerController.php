<?php

namespace App\Controller\Admin;

use App\Entity\Partner;
use App\Repository\PartnerRepository;
use App\Service\LocaleHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

// CRUD admin des partenaires.
#[Route('/api/admin/partners')]
class PartnerController extends AbstractController
{
    public function __construct(
        private LocaleHelper $localeHelper,
    ) {
    }
    #[Route('', name: 'admin_partners_list', methods: ['GET'])]
    public function index(PartnerRepository $partnerRepository): JsonResponse
    {
        $partners = $partnerRepository->findAllOrdered();
        $data = array_map(fn (Partner $p) => $this->serialize($p), $partners);
        return $this->json($data);
    }

    #[Route('/{id}', name: 'admin_partners_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(Partner $partner): JsonResponse
    {
        return $this->json($this->serialize($partner));
    }

    #[Route('', name: 'admin_partners_create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!$data || empty($data['name'])) {
            return $this->json(['error' => 'Le nom est requis.'], Response::HTTP_BAD_REQUEST);
        }

        $partner = new Partner();
        $partner->setCreatedBy($this->getUser());
        $partner->setName($data['name']);
        $partner->setNameEn($data['nameEn'] ?? null);
        $partner->setDomain($data['domain'] ?? null);
        $partner->setDomainEn($data['domainEn'] ?? null);
        $partner->setEmail($data['email'] ?? null);
        $partner->setPhone($data['phone'] ?? null);
        $partner->setLogo($data['logo'] ?? null);
        $partner->setStatus($data['status'] ?? 'active');

        $em->persist($partner);
        $em->flush();

        return $this->json($this->serialize($partner), Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'admin_partners_update', methods: ['PUT'], requirements: ['id' => '\d+'])]
    public function update(Request $request, Partner $partner, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return $this->json(['error' => 'Données requises.'], Response::HTTP_BAD_REQUEST);
        }

        if (isset($data['name'])) $partner->setName($data['name']);
        if (isset($data['nameEn'])) $partner->setNameEn($data['nameEn']);
        if (isset($data['domain'])) $partner->setDomain($data['domain']);
        if (isset($data['domainEn'])) $partner->setDomainEn($data['domainEn']);
        if (isset($data['email'])) $partner->setEmail($data['email']);
        if (isset($data['phone'])) $partner->setPhone($data['phone']);
        if (isset($data['logo'])) $partner->setLogo($data['logo']);
        if (isset($data['status'])) $partner->setStatus($data['status']);

        $em->flush();

        return $this->json($this->serialize($partner));
    }

    // Bascule rapidement un partenaire actif <-> inactif (bouton dédié dans la liste,
    // sans passer par le formulaire d'édition complet)
    #[Route('/{id}/toggle-status', name: 'admin_partners_toggle_status', methods: ['PATCH'], requirements: ['id' => '\d+'])]
    public function toggleStatus(Partner $partner, EntityManagerInterface $em): JsonResponse
    {
        $partner->setStatus($partner->getStatus() === 'active' ? 'inactive' : 'active');
        $em->flush();
        return $this->json($this->serialize($partner));
    }

    #[Route('/{id}', name: 'admin_partners_delete', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function delete(Partner $partner, EntityManagerInterface $em): JsonResponse
    {
        $em->remove($partner);
        $em->flush();
        return $this->json(['message' => 'Partenaire supprimé.']);
    }

    private function serialize(Partner $partner): array
    {
        return [
            'id' => $partner->getId(),
            'name' => $this->localeHelper->localize($partner, 'name'),
            'nameEn' => $partner->getNameEn(),
            'domain' => $this->localeHelper->localize($partner, 'domain'),
            'domainEn' => $partner->getDomainEn(),
            'email' => $partner->getEmail(),
            'phone' => $partner->getPhone(),
            'logo' => $partner->getLogo(),
            'status' => $partner->getStatus(),
            'createdAt' => $partner->getCreatedAt()?->format('c'),
            'updatedAt' => $partner->getUpdatedAt()?->format('c'),
            'createdBy' => $partner->getCreatedBy() ? [
                'id' => $partner->getCreatedBy()->getId(),
                'name' => trim(($partner->getCreatedBy()->getFirstName() ?? '') . ' ' . ($partner->getCreatedBy()->getLastName() ?? '')),
            ] : null,
        ];
    }
}
