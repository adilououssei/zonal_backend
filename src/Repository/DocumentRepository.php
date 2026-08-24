<?php

namespace App\Repository;

use App\Entity\Document;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Requêtes personnalisées pour les documents téléchargeables (en plus des
 * méthodes standard find()/findAll()/findBy() fournies par Doctrine).
 *
 * @extends ServiceEntityRepository<Document>
 */
class DocumentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Document::class);
    }

    // Documents triés du plus récent au plus ancien (utilisé pour la liste publique/admin)
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
