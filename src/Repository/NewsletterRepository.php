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

    // Abonnés visibles dans l'admin : ceux qui ont confirmé leur inscription (actifs, ou
    // désinscrits ensuite). Les demandes jamais confirmées n'y figurent pas : ce sont
    // en pratique des adresses soumises par des robots ou par erreur.
    /** @return Newsletter[] */
    public function findConfirmed(): array
    {
        return $this->createQueryBuilder('n')
            ->andWhere('n.confirmedAt IS NOT NULL')
            ->orderBy('n.confirmedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    // Supprime les demandes jamais confirmées après 7 jours (durée de validité du lien de
    // confirmation), pour que les inscriptions soumises par des robots ne s'accumulent pas.
    public function purgeExpiredPending(): void
    {
        $this->createQueryBuilder('n')
            ->delete()
            ->andWhere('n.isActive = false')
            ->andWhere('n.confirmedAt IS NULL')
            ->andWhere('COALESCE(n.confirmationSentAt, n.subscribedAt) < :limit')
            ->setParameter('limit', new \DateTimeImmutable('-7 days'))
            ->getQuery()
            ->execute();
    }

    // Abonnés actuellement actifs (non désinscrits), utilisé lors de l'envoi des notifications
    /** @return Newsletter[] */
    public function findActive(): array
    {
        return $this->findBy(['isActive' => true], ['subscribedAt' => 'DESC']);
    }
}
