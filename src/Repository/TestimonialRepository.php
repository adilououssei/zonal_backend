<?php

namespace App\Repository;

use App\Entity\Testimonial;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Testimonial>
 */
class TestimonialRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Testimonial::class);
    }

    /** @return Testimonial[] */
    public function findAllOrdered(): array
    {
        return $this->findBy([], ['date' => 'DESC']);
    }

    /** @return Testimonial[] */
    public function findByStatus(string $status): array
    {
        return $this->findBy(['status' => $status], ['date' => 'DESC']);
    }
}
