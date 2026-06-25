<?php

namespace App\Repository;

use App\Entity\Event;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Event>
 */
class EventRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Event::class);
    }

    /** @return Event[] */
    public function findAllOrderedByDate(string $direction = 'DESC'): array
    {
        return $this->findBy([], ['date' => $direction]);
    }

    /** @return Event[] */
    public function findByStatus(string $status): array
    {
        return $this->findBy(['status' => $status], ['date' => 'DESC']);
    }

    /** @return Event[] */
    public function findUpcoming(): array
    {
        return $this->createQueryBuilder('e')
            ->where('e.status = :status OR e.date >= :now')
            ->setParameter('status', 'À venir')
            ->setParameter('now', new \DateTimeImmutable())
            ->orderBy('e.date', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /** @return Event[] */
    public function findPast(): array
    {
        return $this->createQueryBuilder('e')
            ->where('e.status = :status OR e.date < :now')
            ->setParameter('status', 'Terminé')
            ->setParameter('now', new \DateTimeImmutable())
            ->orderBy('e.date', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
