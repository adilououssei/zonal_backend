<?php

namespace App\Controller\Admin;

use App\Entity\Document;
use App\Entity\Event;
use App\Entity\Gallery;
use App\Entity\News;
use App\Entity\Newsletter;
use App\Entity\Partner;
use App\Entity\Project;
use App\Entity\Testimonial;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

// Alimente la page d'accueil de l'admin (compteurs, derniers contenus ajoutés,
// graphiques). Accessible à tout utilisateur ROLE_ADMIN (voir security.yaml) ;
// il n'y a pas de permission "dashboard" bloquante côté serveur, seul le menu
// est masqué côté front si la permission est désactivée.
#[Route('/api/admin/dashboard')]
class DashboardController extends AbstractController
{
    #[Route('/stats', name: 'admin_dashboard_stats', methods: ['GET'])]
    public function stats(EntityManagerInterface $em): JsonResponse {
        $counts = [
            'totalProjects' => (int) $em->createQuery('SELECT COUNT(p) FROM ' . Project::class . ' p')->getSingleScalarResult(),
            'totalEvents' => (int) $em->createQuery('SELECT COUNT(e) FROM ' . Event::class . ' e')->getSingleScalarResult(),
            'totalNews' => (int) $em->createQuery('SELECT COUNT(n) FROM ' . News::class . ' n')->getSingleScalarResult(),
            'totalUsers' => (int) $em->createQuery('SELECT COUNT(u) FROM ' . User::class . ' u')->getSingleScalarResult(),
            'totalPartners' => (int) $em->createQuery('SELECT COUNT(p) FROM ' . Partner::class . ' p')->getSingleScalarResult(),
            'totalGallery' => (int) $em->createQuery('SELECT COUNT(g) FROM ' . Gallery::class . ' g')->getSingleScalarResult(),
            'totalTestimonials' => (int) $em->createQuery('SELECT COUNT(t) FROM ' . Testimonial::class . ' t')->getSingleScalarResult(),
            'totalDocuments' => (int) $em->createQuery('SELECT COUNT(d) FROM ' . Document::class . ' d')->getSingleScalarResult(),
            'totalSubscribers' => (int) $em->createQuery('SELECT COUNT(n) FROM ' . Newsletter::class . ' n')->getSingleScalarResult(),
        ];

        // Les 5 éléments les plus récents de chaque type, affichés dans les widgets "Derniers ajouts"
        $recentEvents = array_map(fn (Event $e) => [
            'id' => $e->getId(),
            'title' => $e->getTitle(),
            'date' => $e->getDate()?->format('Y-m-d'),
            'location' => $e->getLocation(),
            'status' => $e->getStatus(),
        ], $em->getRepository(Event::class)->findBy([], ['date' => 'DESC'], 5));

        $recentNews = array_map(fn (News $n) => [
            'id' => $n->getId(),
            'title' => $n->getTitle(),
            'date' => $n->getDate()?->format('Y-m-d'),
            'category' => $n->getCategory(),
            'coverImage' => $n->getCoverImage(),
        ], $em->getRepository(News::class)->findBy([], ['date' => 'DESC'], 5));

        $recentProjects = array_map(fn (Project $p) => [
            'id' => $p->getId(),
            'title' => $p->getTitle(),
            'location' => $p->getLocation(),
            'status' => $p->getStatus(),
            'startDate' => $p->getStartDate()?->format('Y-m-d'),
        ], $em->getRepository(Project::class)->findBy([], ['startDate' => 'DESC'], 5));

        $monthlyStats = $this->getMonthlyStats($em);

        $contentDistribution = [
            ['label' => 'Projets', 'value' => $counts['totalProjects'], 'color' => '#1a6b3c'],
            ['label' => 'Événements', 'value' => $counts['totalEvents'], 'color' => '#dc2626'],
            ['label' => 'Articles', 'value' => $counts['totalNews'], 'color' => '#3b82f6'],
            ['label' => 'Partenaires', 'value' => $counts['totalPartners'], 'color' => '#f59e0b'],
        ];

        return $this->json([
            'stats' => $counts,
            'recentEvents' => $recentEvents,
            'recentNews' => $recentNews,
            'recentProjects' => $recentProjects,
            'monthlyStats' => $monthlyStats,
            'contentDistribution' => $contentDistribution,
        ]);
    }

    // Compte, mois par mois, le nombre d'événements + articles + projets créés
    // (toutes catégories confondues), utilisé pour le graphique d'activité du dashboard.
    private function getMonthlyStats(EntityManagerInterface $em): array
    {
        $conn = $em->getConnection();

        $months = [];

        $tables = ['event', 'news', 'project'];

        foreach ($tables as $table) {
            // La colonne de date s'appelle "start_date" pour les projets, "date" pour les autres
            $dateCol = $table === 'project' ? 'start_date' : 'date';

            try {
                $rows = $conn->fetchAllAssociative(
                    "SELECT MONTH($dateCol) as m, COUNT(*) as c
                     FROM `$table`
                     WHERE $dateCol IS NOT NULL
                     GROUP BY m
                     ORDER BY m ASC"
                );
            } catch (\Exception) {
                continue;
            }

            foreach ($rows as $row) {
                $month = (int) $row['m'];
                $count = (int) $row['c'];
                if (!isset($months[$month])) {
                    $months[$month] = 0;
                }
                $months[$month] += $count;
            }
        }

        // On garantit les 12 mois dans la réponse, même ceux sans aucune donnée (valeur 0),
        // pour que le graphique affiche toujours une échelle complète Jan -> Déc
        $labels = ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin', 'Juil', 'Août', 'Sep', 'Oct', 'Nov', 'Déc'];
        $result = [];
        for ($i = 1; $i <= 12; $i++) {
            $result[] = [
                'label' => $labels[$i - 1],
                'value' => $months[$i] ?? 0,
            ];
        }

        return $result;
    }
}
