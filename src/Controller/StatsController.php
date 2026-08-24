<?php

namespace App\Controller;

use App\Entity\Document;
use App\Entity\Event;
use App\Entity\Gallery;
use App\Entity\News;
use App\Entity\Partner;
use App\Entity\Project;
use App\Entity\Testimonial;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

// API publique (accès libre, voir security.yaml) exposant des compteurs globaux
// (nombre de projets, événements, etc.), utilisés pour les chiffres-clés affichés
// sur le site (ex: page d'accueil "X projets réalisés").
#[Route('/api/stats')]
class StatsController extends AbstractController
{
    #[Route('', name: 'public_stats', methods: ['GET'])]
    public function stats(EntityManagerInterface $em): JsonResponse
    {
        return $this->json([
            'projects' => (int) $em->createQuery('SELECT COUNT(p) FROM ' . Project::class . ' p')->getSingleScalarResult(),
            'completedProjects' => (int) $em->createQuery('SELECT COUNT(p) FROM ' . Project::class . ' p WHERE p.status = :status')->setParameter('status', 'completed')->getSingleScalarResult(),
            'events' => (int) $em->createQuery('SELECT COUNT(e) FROM ' . Event::class . ' e')->getSingleScalarResult(),
            'news' => (int) $em->createQuery('SELECT COUNT(n) FROM ' . News::class . ' n')->getSingleScalarResult(),
            'partners' => (int) $em->createQuery('SELECT COUNT(p) FROM ' . Partner::class . ' p')->getSingleScalarResult(),
            'testimonials' => (int) $em->createQuery('SELECT COUNT(t) FROM ' . Testimonial::class . ' t')->getSingleScalarResult(),
            'gallery' => (int) $em->createQuery('SELECT COUNT(g) FROM ' . Gallery::class . ' g')->getSingleScalarResult(),
            'documents' => (int) $em->createQuery('SELECT COUNT(d) FROM ' . Document::class . ' d')->getSingleScalarResult(),
        ]);
    }
}
