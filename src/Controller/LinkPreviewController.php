<?php

namespace App\Controller;

use App\Entity\Event;
use App\Entity\Gallery;
use App\Entity\News;
use App\Entity\Project;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

// Aperçus de liens pour les réseaux sociaux (Facebook, LinkedIn, WhatsApp, X...).
// Le site public est une SPA : les robots de ces réseaux ne lisent que le HTML
// brut et ne voient donc jamais les balises Open Graph posées par React (voir
// zonal/src/components/Seo.tsx). Le Nginx du frontend (zonal/docker/nginx.conf.template)
// redirige donc ces robots, et eux seuls, de /events/12 vers /og/events/12 ici,
// qui renvoie une page minimale avec le titre, le résumé et l'image de la publication.
// Un humain qui atterrirait sur cette page est renvoyé vers la vraie page du site.
class LinkPreviewController extends AbstractController
{
    private const TYPES = [
        'events' => Event::class,
        'news' => News::class,
        'projects' => Project::class,
        'gallery' => Gallery::class,
    ];

    private const DESCRIPTION_MAX_LENGTH = 200;

    #[Route('/og/{type}/{id}', name: 'link_preview', methods: ['GET', 'HEAD'], requirements: ['type' => 'events|news|projects|gallery', 'id' => '\d+'])]
    public function preview(
        string $type,
        int $id,
        Request $request,
        EntityManagerInterface $em,
        #[Autowire('%env(FRONTEND_URL)%')] string $frontendUrl,
    ): Response {
        $frontendUrl = rtrim($frontendUrl, '/');
        $entity = $em->getRepository(self::TYPES[$type])->find($id);

        if (!$entity) {
            return $this->render('link_preview.html.twig', [
                'title' => 'ZONAL ONG',
                'description' => null,
                'image' => $frontendUrl . '/images/logoOrigin.png',
                'url' => $frontendUrl . '/' . $type,
                'ogType' => 'website',
            ], new Response(null, Response::HTTP_NOT_FOUND));
        }

        [$description, $image] = match (true) {
            $entity instanceof News => [$entity->getExcerpt() ?: $entity->getContent(), $entity->getCoverImage()],
            $entity instanceof Event => [$entity->getDescription(), $entity->getCoverImage()],
            $entity instanceof Project => [$entity->getDescription(), $entity->getImage()],
            // Un album n'a pas de texte : le résumé indique le nombre de photos
            $entity instanceof Gallery => [sprintf('Album photo ZONAL · %d photo(s)', count($entity->getImages() ?: [$entity->getSrc()])), $entity->getSrc()],
        };

        $response = $this->render('link_preview.html.twig', [
            'title' => $entity->getTitle(),
            'description' => $this->summarize($description),
            'image' => $this->absoluteImageUrl($image, $request, $frontendUrl),
            'url' => sprintf('%s/%s/%d', $frontendUrl, $type, $id),
            'ogType' => $entity instanceof News ? 'article' : 'website',
        ]);

        // Les réseaux sociaux gardent eux-mêmes l'aperçu en cache ; un cache court
        // côté HTTP suffit et laisse une modification apparaître rapidement.
        $response->setPublic();
        $response->setMaxAge(600);

        return $response;
    }

    // Texte brut sur une ligne, sans balises ni syntaxe de mise en forme, coupé
    // proprement pour tenir dans l'aperçu.
    private function summarize(?string $text): ?string
    {
        if ($text === null || $text === '') {
            return null;
        }

        $text = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/[*_#>`~]+/u', '', $text);
        $text = trim(preg_replace('/\s+/u', ' ', $text));

        if (mb_strlen($text) <= self::DESCRIPTION_MAX_LENGTH) {
            return $text;
        }

        $cut = mb_substr($text, 0, self::DESCRIPTION_MAX_LENGTH);
        $lastSpace = mb_strrpos($cut, ' ');

        return rtrim($lastSpace ? mb_substr($cut, 0, $lastSpace) : $cut, ' ,;:.') . '…';
    }

    // Les images uploadées sont enregistrées avec leur URL complète (voir
    // UploadController) ; on complète au cas où une ancienne donnée serait relative.
    private function absoluteImageUrl(?string $image, Request $request, string $frontendUrl): string
    {
        if (!$image) {
            return $frontendUrl . '/images/logoOrigin.png';
        }

        if (preg_match('#^https?://#i', $image)) {
            return $image;
        }

        return str_starts_with($image, '/uploads/')
            ? $request->getSchemeAndHttpHost() . $image
            : $frontendUrl . '/' . ltrim($image, '/');
    }
}
