<?php

namespace App\Repository;

use App\Entity\Document;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Document>
 */
class DocumentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Document::class);
    }

    /** @return Document[] */
    public function findAllOrdered(): array
    {
        return $this->findBy([], ['date' => 'DESC', 'createdAt' => 'DESC']);
    }

    /** @return Document[] */
    public function findByCategory(string $category): array
    {
        return $this->findBy(['category' => $category], ['date' => 'DESC']);
    }
}
