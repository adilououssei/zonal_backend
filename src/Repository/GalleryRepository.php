<?php

namespace App\Repository;

use App\Entity\Gallery;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Requêtes personnalisées pour la galerie photo (en plus des méthodes
 * standard find()/findAll()/findBy() fournies par Doctrine).
 *
 * @extends ServiceEntityRepository<Gallery>
 */
class GalleryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Gallery::class);
    }

    /** @return Gallery[] */
    public function findAllOrderedByDate(string $direction = 'DESC'): array
    {
        return $this->findBy([], ['date' => $direction]);
    }

    /** @return Gallery[] */
    public function findByCategory(string $category): array
    {
        return $this->findBy(['category' => $category], ['date' => 'DESC']);
    }
}
