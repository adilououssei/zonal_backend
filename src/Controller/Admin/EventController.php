<?php

namespace App\Controller\Admin;

use App\Entity\Event;
use App\Repository\EventRepository;
use App\Service\DeepLTranslator;
use App\Service\LocaleHelper;
use App\Service\NewsletterNotifier;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

// CRUD admin des événements. À la création, un email est envoyé aux abonnés
// newsletter (NewsletterNotifier) et les champs anglais manquants sont
// traduits automatiquement (DeepLTranslator) si le service est configuré.
#[Route('/api/admin/events')]
#[IsGranted('MODULE_EVENTS')]
class EventController extends AbstractController
{
    public function __construct(
        private LocaleHelper $localeHelper,
        private NewsletterNotifier $newsletterNotifier,
        private DeepLTranslator $translator,
    ) {
    }

    /**
     * Remplit automatiquement les champs *En manquants par traduction FR -> EN,
     * pour éviter la double saisie manuelle par l'administrateur.
     */
    private function autoTranslate(Event $event): void
    {
        if (!$this->translator->isConfigured()) return;

        if (!$event->getTitleEn() && $event->getTitle()) {
            $event->setTitleEn($this->translator->translateToEnglish($event->getTitle()));
        }
        if (!$event->getDescriptionEn() && $event->getDescription()) {
            $event->setDescriptionEn($this->translator->translateToEnglish($event->getDescription(), isHtml: true));
        }
        if (!$event->getLocationEn() && $event->getLocation()) {
            $event->setLocationEn($this->translator->translateToEnglish($event->getLocation()));
        }
        if (!$event->getCategoryEn() && $event->getCategory()) {
            $event->setCategoryEn($this->translator->translateToEnglish($event->getCategory()));
        }
    }
    #[Route('', name: 'admin_events_list', methods: ['GET'])]
    public function index(EventRepository $eventRepository): JsonResponse
    {
        $events = $eventRepository->findAllOrderedByDate();
        $data = array_map(fn (Event $e) => $this->serializeEvent($e), $events);
        return $this->json($data);
    }

    #[Route('/{id}', name: 'admin_events_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(Event $event): JsonResponse
    {
        return $this->json($this->serializeEvent($event));
    }

    #[Route('', name: 'admin_events_create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!$data || empty($data['title']) || empty($data['date']) || empty($data['location'])) {
            return $this->json(['error' => 'Titre, date et lieu sont requis.'], Response::HTTP_BAD_REQUEST);
        }

        $event = new Event();
        $event->setCreatedBy($this->getUser());
        $event->setTitle($data['title']);
        $event->setTitleEn($data['titleEn'] ?? null);
        $event->setDescription($data['description'] ?? null);
        $event->setDescriptionEn($data['descriptionEn'] ?? null);
        $event->setLocation($data['location']);
        $event->setLocationEn($data['locationEn'] ?? null);
        $event->setCategory($data['category'] ?? null);
        $event->setCategoryEn($data['categoryEn'] ?? null);
        $event->setTime($data['time'] ?? null);
        $event->setCoverImage($data['coverImage'] ?? null);
        $event->setGallery($data['gallery'] ?? null);

        try {
            $event->setDate(new \DateTimeImmutable($data['date']));
        } catch (\Exception) {
            return $this->json(['error' => 'Format de date invalide (attendu: YYYY-MM-DD).'], Response::HTTP_BAD_REQUEST);
        }

        // Si l'admin ne fournit pas de statut, on le déduit de la date (voir computeStatusFromDate)
        $event->setStatus(isset($data['status']) ? $data['status'] : $this->computeStatusFromDate($event->getDate()));

        $this->autoTranslate($event);

        $em->persist($event);
        $em->flush();

        // Notifie tous les abonnés actifs de la newsletter par email
        $this->newsletterNotifier->notifyNewContent(
            'Nouvel événement',
            $event->getTitle(),
            $this->excerptFromHtml($event->getDescription()),
            '/events/' . $event->getId(),
            $event->getCoverImage(),
        );

        return $this->json($this->serializeEvent($event), Response::HTTP_CREATED);
    }

    // Extrait un court résumé en texte brut à partir d'une description HTML (pour l'email de notification)
    private function excerptFromHtml(?string $html, int $maxLength = 160): ?string
    {
        if (!$html) return null;
        $text = trim(strip_tags($html));
        if ($text === '') return null;
        return mb_strlen($text) > $maxLength ? mb_substr($text, 0, $maxLength) . '…' : $text;
    }

    #[Route('/{id}', name: 'admin_events_update', methods: ['PUT'], requirements: ['id' => '\d+'])]
    public function update(Request $request, Event $event, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return $this->json(['error' => 'Données requises.'], Response::HTTP_BAD_REQUEST);
        }

        if (isset($data['title'])) $event->setTitle($data['title']);
        if (isset($data['titleEn'])) $event->setTitleEn($data['titleEn']);
        if (isset($data['description'])) $event->setDescription($data['description']);
        if (isset($data['descriptionEn'])) $event->setDescriptionEn($data['descriptionEn']);
        if (isset($data['location'])) $event->setLocation($data['location']);
        if (isset($data['locationEn'])) $event->setLocationEn($data['locationEn']);
        if (isset($data['category'])) $event->setCategory($data['category']);
        if (isset($data['categoryEn'])) $event->setCategoryEn($data['categoryEn']);
        if (isset($data['time'])) $event->setTime($data['time']);
        if (isset($data['coverImage'])) $event->setCoverImage($data['coverImage']);
        if (isset($data['gallery'])) $event->setGallery($data['gallery']);
        if (isset($data['date'])) {
            try {
                $event->setDate(new \DateTimeImmutable($data['date']));
            } catch (\Exception) {
                return $this->json(['error' => 'Format de date invalide (attendu: YYYY-MM-DD).'], Response::HTTP_BAD_REQUEST);
            }
        }
        if (isset($data['status'])) {
            $event->setStatus($data['status']);
        } else {
            // Statut recalculé automatiquement si la date a changé et qu'aucun statut n'est fourni
            $event->setStatus($this->computeStatusFromDate($event->getDate()));
        }

        $this->autoTranslate($event);

        $em->flush();

        return $this->json($this->serializeEvent($event));
    }

    #[Route('/{id}', name: 'admin_events_delete', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function delete(Event $event, EntityManagerInterface $em): JsonResponse
    {
        $em->remove($event);
        $em->flush();

        return $this->json(['message' => 'Événement supprimé.']);
    }

    private function serializeEvent(Event $event): array
    {
        $date = $event->getDate();

        return [
            'id' => $event->getId(),
            'title' => $this->localeHelper->localize($event, 'title'),
            'titleEn' => $event->getTitleEn(),
            'description' => $this->localeHelper->localize($event, 'description'),
            'descriptionEn' => $event->getDescriptionEn(),
            'date' => $date?->format('Y-m-d'),
            'day' => $date?->format('d'),
            'month' => $this->formatMonth($date?->format('n')),
            'year' => $date?->format('Y'),
            'time' => $event->getTime(),
            'location' => $this->localeHelper->localize($event, 'location'),
            'locationEn' => $event->getLocationEn(),
            'category' => $event->getCategory(),
            'categoryEn' => $event->getCategoryEn(),
            'status' => $event->getStatus(),
            'coverImage' => $event->getCoverImage(),
            'gallery' => $event->getGallery(),
            'createdAt' => $event->getCreatedAt()?->format('c'),
            'updatedAt' => $event->getUpdatedAt()?->format('c'),
            'createdBy' => $event->getCreatedBy() ? [
                'id' => $event->getCreatedBy()->getId(),
                'name' => trim(($event->getCreatedBy()->getFirstName() ?? '') . ' ' . ($event->getCreatedBy()->getLastName() ?? '')),
            ] : null,
        ];
    }

    // Contrairement à l'API publique, l'admin peut aussi imposer un statut manuel
    // (via $data['status']) ; cette fonction ne sert que de valeur par défaut.
    private function computeStatusFromDate(?\DateTimeImmutable $date): string
    {
        if (!$date) return 'À venir';

        $now = new \DateTimeImmutable();
        $today = $now->setTime(0, 0, 0);
        $eventDay = $date->setTime(0, 0, 0);

        if ($eventDay < $today) return 'Terminé';
        if ($eventDay == $today) return 'En cours';
        return 'À venir';
    }

    private function formatMonth(?string $monthNumber): string
    {
        $months = [
            '1' => 'JANV.', '2' => 'FÉV.', '3' => 'MARS', '4' => 'AVR.',
            '5' => 'MAI', '6' => 'JUIN', '7' => 'JUIL.', '8' => 'AOÛT',
            '9' => 'SEPT.', '10' => 'OCT.', '11' => 'NOV.', '12' => 'DÉC.',
        ];
        return $months[$monthNumber] ?? strtoupper($monthNumber ?? '');
    }
}
