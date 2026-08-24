<?php

namespace App\Entity;

use App\Repository\RoleRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Rôle métier utilisé pour gérer les permissions fines du back-office
 * (page "Rôles" de l'admin). Chaque rôle a un nom libre (ex: "Administrateur",
 * "Éditeur") et une liste de permissions par module (dashboard, events, news,
 * newsletter, users, roles, settings...) stockée en JSON.
 *
 * Attention : ceci est distinct des rôles de sécurité Symfony (ROLE_USER,
 * ROLE_ADMIN, ROLE_SUPER_ADMIN) définis sur User::$roles, qui contrôlent
 * l'accès aux routes API. Ce Role sert uniquement à afficher/masquer les
 * modules du menu admin selon le module list défini dans RoleController::permissions().
 *
 * Le compte Super Administrateur contourne systématiquement cette liste de
 * permissions côté front (voir AdminLayout.tsx) : il a toujours accès à tout,
 * même à un module ajouté après coup et pas encore coché ici.
 */
#[ORM\Entity(repositoryClass: RoleRepository::class)]
#[ORM\Table(name: '`role`')]
#[ORM\HasLifecycleCallbacks]
class Role
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100, unique: true)]
    #[Assert\NotBlank]
    private ?string $name = null;

    // Tableau associatif { "cléDuModule": true|false }, ex: { "newsletter": true, "users": false }
    #[ORM\Column(type: Types::JSON)]
    private array $permissions = [];

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function getPermissions(): array
    {
        return $this->permissions;
    }

    public function setPermissions(array $permissions): static
    {
        $this->permissions = $permissions;
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
