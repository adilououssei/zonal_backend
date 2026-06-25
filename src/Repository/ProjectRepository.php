<?php

namespace App\Repository;

use App\Entity\Project;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Project>
 */
class ProjectRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Project::class);
    }

    /** @return Project[] */
    public function findAllOrderedByDate(string $direction = 'DESC'): array
    {
        return $this->findBy([], ['startDate' => $direction]);
    }

    /** @return Project[] */
    public function findByStatus(string $status): array
    {
        return $this->findBy(['status' => $status], ['startDate' => 'DESC']);
    }
}
