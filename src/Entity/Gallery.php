<?php

namespace App\Entity;

use App\Repository\GalleryRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Élément de la galerie photo publique (page "Galerie"). $src est l'image
 * principale/miniature ; $images est une liste optionnelle d'images
 * supplémentaires quand l'élément représente un album plutôt qu'une photo unique.
 */
#[ORM\Entity(repositoryClass: GalleryRepository::class)]
#[ORM\Table(name: '`gallery`')]
#[ORM\HasLifecycleCallbacks]
class Gallery
{
    // Utilisateur admin ayant créé l'élément (traçabilité) ; conservé même si le compte est supprimé (SET NULL)
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $createdBy = null;
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    private ?string $title = null;

    // Image principale / miniature affichée dans la grille de la galerie
    #[ORM\Column(length: 500)]
    #[Assert\NotBlank]
    private ?string $src = null;

    #[ORM\Column(length: 100)]
    private string $category = '';

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $date = null;

    // Images supplémentaires si l'élément est un album (liste de chemins)
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $images = null;

    // Traduction anglaise du titre
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $titleEn = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
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

    public function getId(): ?int { return $this->id; }
    public function getTitle(): ?string { return $this->title; }
    public function setTitle(string $title): static { $this->title = $title; return $this; }
    public function getSrc(): ?string { return $this->src; }
    public function setSrc(string $src): static { $this->src = $src; return $this; }
    public function getCategory(): string { return $this->category; }
    public function setCategory(string $category): static { $this->category = $category; return $this; }
    public function getDate(): ?\DateTimeImmutable { return $this->date; }
    public function setDate(?\DateTimeImmutable $date): static { $this->date = $date; return $this; }
    public function getImages(): ?array { return $this->images; }
    public function setImages(?array $images): static { $this->images = $images; return $this; }
    public function getCreatedAt(): ?\DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): ?\DateTimeImmutable { return $this->updatedAt; }

    public function getTitleEn(): ?string { return $this->titleEn; }
    public function setTitleEn(?string $titleEn): static { $this->titleEn = $titleEn; return $this; }

    public function getCreatedBy(): ?User { return $this->createdBy; }
    public function setCreatedBy(?User $createdBy): static { $this->createdBy = $createdBy; return $this; }
}
