<?php

namespace App\Repository;

use App\Entity\Partner;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Requêtes personnalisées pour les partenaires (en plus des méthodes
 * standard find()/findAll()/findBy() fournies par Doctrine).
 *
 * @extends ServiceEntityRepository<Partner>
 */
class PartnerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Partner::class);
    }

    /** @return Partner[] */
    public function findAllOrdered(): array
    {
        return $this->findBy([], ['name' => 'ASC']);
    }

    /** @return Partner[] */
    public function findByStatus(string $status): array
    {
        return $this->findBy(['status' => $status], ['name' => 'ASC']);
    }
}
