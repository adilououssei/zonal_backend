<?php

namespace App\Controller;

use App\Repository\EventRepository;
use App\Service\LocaleHelper;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/events')]
class EventController extends AbstractController
{
    public function __construct(
        private LocaleHelper $localeHelper,
    ) {
    }
    #[Route('', name: 'events_list', methods: ['GET'])]
    public function index(EventRepository $eventRepository): JsonResponse
    {
        $events = $eventRepository->findAllOrderedByDate('DESC');
        $data = array_map(fn ($e) => $this->serializeEvent($e), $events);
        return $this->json($data);
    }

    #[Route('/upcoming', name: 'events_upcoming', methods: ['GET'])]
    public function upcoming(EventRepository $eventRepository): JsonResponse
    {
        $events = $eventRepository->findUpcoming();
        $data = array_map(fn ($e) => $this->serializeEvent($e), $events);
        return $this->json($data);
    }

    #[Route('/past', name: 'events_past', methods: ['GET'])]
    public function past(EventRepository $eventRepository): JsonResponse
    {
        $events = $eventRepository->findPast();
        $data = array_map(fn ($e) => $this->serializeEvent($e), $events);
        return $this->json($data);
    }

    #[Route('/{id}', name: 'events_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(int $id, EventRepository $eventRepository): JsonResponse
    {
        $event = $eventRepository->find($id);
        if (!$event) {
            return $this->json(['error' => 'Événement introuvable.'], 404);
        }
        return $this->json($this->serializeEvent($event));
    }

    private function serializeEvent($event): array
    {
        $date = $event->getDate();

        return [
            'id' => $event->getId(),
            'title' => $this->localeHelper->localize($event, 'title'),
            'description' => $this->localeHelper->localize($event, 'description'),
            'date' => $date?->format('Y-m-d'),
            'day' => $date?->format('d'),
            'month' => $this->formatMonth($date?->format('n')),
            'year' => $date?->format('Y'),
            'time' => $event->getTime(),
            'location' => $this->localeHelper->localize($event, 'location'),
            'category' => $event->getCategory(),
            'status' => $this->computeStatusFromDate($date),
            'image' => $event->getCoverImage(),
            'gallery' => $event->getGallery(),
        ];
    }

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
            '1' => 'JUIN', '2' => 'FÉV.', '3' => 'MARS', '4' => 'AVR.',
            '5' => 'MAI', '6' => 'JUIN', '7' => 'JUIL.', '8' => 'AOÛT',
            '9' => 'SEPT.', '10' => 'OCT.', '11' => 'NOV.', '12' => 'DÉC.',
        ];
        return $months[$monthNumber] ?? strtoupper($monthNumber ?? '');
    }
}
