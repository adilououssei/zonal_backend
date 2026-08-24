<?php

namespace App\Entity;

use App\Repository\TestimonialRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Témoignage affiché sur le site (avis d'un bénéficiaire, partenaire, etc.).
 * $rating est une note de 1 à 5. $status permet de publier/dépublier un
 * témoignage sans le supprimer. Les champs suffixés "En" contiennent la
 * traduction anglaise.
 */
#[ORM\Entity(repositoryClass: TestimonialRepository::class)]
#[ORM\Table(name: '`testimonial`')]
#[ORM\HasLifecycleCallbacks]
class Testimonial
{
    // Utilisateur admin ayant créé le témoignage (traçabilité) ; conservé même si le compte est supprimé (SET NULL)
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $createdBy = null;
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    private ?string $author = null;

    // Fonction/rôle de l'auteur du témoignage (ex: "Bénéficiaire", "Partenaire")
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $role = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank]
    private ?string $content = null;

    #[ORM\Column(type: Types::SMALLINT)]
    #[Assert\Range(min: 1, max: 5)]
    private int $rating = 5;

    #[ORM\Column(type: Types::STRING, length: 500, nullable: true)]
    private ?string $avatar = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $date = null;

    // "published" ou "draft" : contrôle l'affichage sur le site public
    #[ORM\Column(length: 20, options: ['default' => 'published'])]
    private string $status = 'published';

    // --- Traductions anglaises ---
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $authorEn = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $roleEn = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $contentEn = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $this->createdAt = new \DateTimeImmutable();
        // Si aucune date n'est fournie par l'admin, on prend la date de création par défaut
        if ($this->date === null) {
            $this->date = new \DateTimeImmutable();
        }
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }
    public function getAuthor(): ?string { return $this->author; }
    public function setAuthor(string $author): static { $this->author = $author; return $this; }
    public function getRole(): ?string { return $this->role; }
    public function setRole(?string $role): static { $this->role = $role; return $this; }
    public function getContent(): ?string { return $this->content; }
    public function setContent(string $content): static { $this->content = $content; return $this; }
    public function getRating(): int { return $this->rating; }
    public function setRating(int $rating): static { $this->rating = $rating; return $this; }
    public function getAvatar(): ?string { return $this->avatar; }
    public function setAvatar(?string $avatar): static { $this->avatar = $avatar; return $this; }
    public function getDate(): ?\DateTimeImmutable { return $this->date; }
    public function setDate(?\DateTimeImmutable $date): static { $this->date = $date; return $this; }
    public function getStatus(): string { return $this->status; }
    public function setStatus(string $status): static { $this->status = $status; return $this; }
    public function getCreatedAt(): ?\DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): ?\DateTimeImmutable { return $this->updatedAt; }

    public function getAuthorEn(): ?string { return $this->authorEn; }
    public function setAuthorEn(?string $authorEn): static { $this->authorEn = $authorEn; return $this; }
    public function getRoleEn(): ?string { return $this->roleEn; }
    public function setRoleEn(?string $roleEn): static { $this->roleEn = $roleEn; return $this; }
    public function getContentEn(): ?string { return $this->contentEn; }
    public function setContentEn(?string $contentEn): static { $this->contentEn = $contentEn; return $this; }

    public function getCreatedBy(): ?User { return $this->createdBy; }
    public function setCreatedBy(?User $createdBy): static { $this->createdBy = $createdBy; return $this; }
}
