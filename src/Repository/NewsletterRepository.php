<?php

namespace App\Repository;

use App\Entity\Newsletter;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Newsletter>
 */
class NewsletterRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Newsletter::class);
    }

    public function findByEmail(string $email): ?Newsletter
    {
        return $this->findOneBy(['email' => $email]);
    }

    public function findByUnsubscribeToken(string $token): ?Newsletter
    {
        return $this->findOneBy(['unsubscribeToken' => $token]);
    }

    /** @return Newsletter[] */
    public function findActive(): array
    {
        return $this->findBy(['isActive' => true], ['subscribedAt' => 'DESC']);
    }
}
