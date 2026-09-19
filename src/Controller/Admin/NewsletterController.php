<?php

namespace App\Controller\Admin;

use App\Entity\Newsletter;
use App\Repository\NewsletterRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

// Gestion admin des abonnés à la newsletter (liste + suppression). À ne pas
// confondre avec le NewsletterController public (App\Controller) qui gère
// l'inscription/désinscription des visiteurs.
#[Route('/api/admin/newsletter')]
#[IsGranted('MODULE_NEWSLETTER')]
class NewsletterController extends AbstractController
{
    #[Route('/subscribers', name: 'admin_newsletter_subscribers', methods: ['GET'])]
    public function index(NewsletterRepository $repo): JsonResponse
    {
        $subscribers = $repo->findConfirmed();
        $data = array_map(fn (Newsletter $s) => $this->serialize($s), $subscribers);
        return $this->json($data);
    }

    #[Route('/subscribers/{id}', name: 'admin_newsletter_subscriber_delete', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function delete(Newsletter $subscriber, EntityManagerInterface $em): JsonResponse
    {
        $em->remove($subscriber);
        $em->flush();

        return $this->json(['message' => 'Abonné supprimé.']);
    }

    private function serialize(Newsletter $subscriber): array
    {
        return [
            'id' => $subscriber->getId(),
            'email' => $subscriber->getEmail(),
            'name' => $subscriber->getName(),
            'isActive' => $subscriber->isActive(),
            // Date de confirmation (l'inscription n'existe pour l'équipe qu'à partir de là)
            'subscribedAt' => ($subscriber->getConfirmedAt() ?? $subscriber->getSubscribedAt())?->format('c'),
        ];
    }
}
