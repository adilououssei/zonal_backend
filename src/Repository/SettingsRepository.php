<?php

namespace App\Repository;

use App\Entity\Settings;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

// Aucune requête personnalisée : les méthodes standard find()/findAll()/findBy() de Doctrine suffisent
// (il n'y a de toute façon qu'une seule ligne de Settings en base).
/**
 * @extends ServiceEntityRepository<Settings>
 */
class SettingsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Settings::class);
    }
}
