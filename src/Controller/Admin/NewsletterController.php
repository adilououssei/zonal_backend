<?php

namespace App\Controller\Admin;

use App\Entity\Newsletter;
use App\Repository\NewsletterRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

// Gestion admin des abonnés à la newsletter (liste + suppression). À ne pas
// confondre avec le NewsletterController public (App\Controller) qui gère
// l'inscription/désinscription des visiteurs.
//
// Note : ce contrôleur n'a pas d'attribut #[IsGranted(...)] au niveau de la
// classe (contrairement à RoleController ou SettingsController) : l'accès est
// seulement protégé par la règle générale "/api/admin -> ROLE_ADMIN" du
// firewall (security.yaml). La permission fine "newsletter" de la matrice de
// rôles (Role::$permissions) ne sert donc aujourd'hui qu'à masquer le lien
// dans le menu admin côté front ; elle n'est pas vérifiée ici côté serveur.
#[Route('/api/admin/newsletter')]
class NewsletterController extends AbstractController
{
    #[Route('/subscribers', name: 'admin_newsletter_subscribers', methods: ['GET'])]
    public function index(NewsletterRepository $repo): JsonResponse
    {
        $subscribers = $repo->findBy([], ['subscribedAt' => 'DESC']);
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
            'subscribedAt' => $subscriber->getSubscribedAt()?->format('c'),
        ];
    }
}
