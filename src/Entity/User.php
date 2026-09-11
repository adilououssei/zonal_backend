<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Représente un compte utilisateur du back-office (administrateur, éditeur, etc.).
 *
 * Cette entité sert à la fois de compte de connexion (via UserInterface, utilisée
 * par le système de sécurité Symfony) et de fiche utilisateur affichée dans la
 * page "Utilisateurs" de l'admin. Les droits fins (quels modules il peut voir
 * et utiliser via /api/admin/*) viennent entièrement de la relation avec
 * l'entité Role ($role) et de sa matrice de permissions (voir
 * ModulePermissionVoter). $roles ne contient que ROLE_USER (tout compte créé
 * depuis la page "Utilisateurs") ou ROLE_SUPER_ADMIN (le compte unique et
 * protégé créé via app:create-super-admin, qui contourne toujours la matrice).
 */
#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[ORM\UniqueConstraint(name: 'UNIQ_EMAIL', fields: ['email'])]
#[ORM\HasLifecycleCallbacks]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    // Identifiant de connexion (sert aussi d'identifiant "username" pour Symfony Security)
    #[ORM\Column(length: 180)]
    #[Assert\NotBlank]
    #[Assert\Email]
    private ?string $email = null;

    // Rôles Symfony bruts : ROLE_USER (tout compte normal) ou ROLE_SUPER_ADMIN
    #[ORM\Column]
    private array $roles = [];

    // Mot de passe déjà haché (jamais stocké en clair)
    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $firstName = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $lastName = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $phone = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $avatar = null;

    // Permet de désactiver un compte sans le supprimer
    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => true])]
    private bool $isActive = true;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $lastLoginAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    // Rôle métier (permissions fines par module : newsletter, users, roles...) affiché dans la page Rôles.
    // Différent de $roles ci-dessus qui, lui, est le rôle de sécurité Symfony.
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Role $role = null;

    // Jeton temporaire utilisé pour le lien "mot de passe oublié"
    #[ORM\Column(length: 64, nullable: true)]
    private ?string $passwordResetToken = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $passwordResetExpiresAt = null;

    public function getRole(): ?Role { return $this->role; }
    public function setRole(?Role $role): static { $this->role = $role; return $this; }

    public function getPasswordResetToken(): ?string { return $this->passwordResetToken; }
    public function setPasswordResetToken(?string $token): static { $this->passwordResetToken = $token; return $this; }

    public function getPasswordResetExpiresAt(): ?\DateTimeImmutable { return $this->passwordResetExpiresAt; }
    public function setPasswordResetExpiresAt(?\DateTimeImmutable $date): static { $this->passwordResetExpiresAt = $date; return $this; }

    /**
     * Vérifie que le jeton de réinitialisation de mot de passe existe encore
     * et n'a pas expiré, avant d'autoriser le changement de mot de passe.
     */
    public function isPasswordResetTokenValid(): bool
    {
        return $this->passwordResetToken !== null
            && $this->passwordResetExpiresAt !== null
            && $this->passwordResetExpiresAt > new \DateTimeImmutable();
    }

    // Renseigne automatiquement la date de création à l'insertion en base
    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    // Renseigne automatiquement la date de mise à jour à chaque modification
    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;
        return $this;
    }

    // Identifiant utilisé par Symfony Security pour retrouver l'utilisateur (ici : l'email)
    public function getUserIdentifier(): string
    {
        return $this->email ?? '';
    }

    // Tout utilisateur authentifié a au minimum ROLE_USER, même si ce n'est pas stocké explicitement
    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';
        return array_unique($roles);
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;
        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;
        return $this;
    }

    // Requis par l'interface Symfony ; rien à effacer car on ne stocke pas d'infos sensibles temporaires
    public function eraseCredentials(): void
    {
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(?string $firstName): static
    {
        $this->firstName = $firstName;
        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(?string $lastName): static
    {
        $this->lastName = $lastName;
        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): static
    {
        $this->phone = $phone;
        return $this;
    }

    public function getAvatar(): ?string
    {
        return $this->avatar;
    }

    public function setAvatar(?string $avatar): static
    {
        $this->avatar = $avatar;
        return $this;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;
        return $this;
    }

    public function getLastLoginAt(): ?\DateTimeImmutable
    {
        return $this->lastLoginAt;
    }

    public function setLastLoginAt(?\DateTimeImmutable $lastLoginAt): static
    {
        $this->lastLoginAt = $lastLoginAt;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }
}
