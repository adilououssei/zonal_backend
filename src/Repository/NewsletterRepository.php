<?php

namespace App\Repository;

use App\Entity\Newsletter;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Requêtes personnalisées pour les abonnés à la newsletter (en plus des
 * méthodes standard find()/findAll()/findBy() fournies par Doctrine).
 *
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

    // Utilisé pour retrouver l'abonné à partir du lien de désinscription envoyé par email
    public function findByUnsubscribeToken(string $token): ?Newsletter
    {
        return $this->findOneBy(['unsubscribeToken' => $token]);
    }

    // Utilisé pour retrouver l'inscription à partir du lien de confirmation envoyé par email
    public function findByConfirmationToken(string $token): ?Newsletter
    {
        return $this->findOneBy(['confirmationToken' => $token]);
    }

    // Abonnés actuellement actifs (non désinscrits), utilisé lors de l'envoi des notifications
    /** @return Newsletter[] */
    public function findActive(): array
    {
        return $this->findBy(['isActive' => true], ['subscribedAt' => 'DESC']);
    }
}
