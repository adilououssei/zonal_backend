<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    // Utilisé pour la connexion (l'email sert d'identifiant) et pour vérifier qu'un email n'est pas déjà pris
    public function findByEmail(string $email): ?User
    {
        return $this->findOneBy(['email' => $email]);
    }
}
