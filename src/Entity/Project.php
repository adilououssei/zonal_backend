<?php

namespace App\Entity;

use App\Repository\ProjectRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Projet de l'ONG affiché sur le site (page "Projets"). Les champs suffixés
 * "En" contiennent la traduction anglaise. $status suit l'avancement
 * (ex: "planned", "ongoing", "completed").
 */
#[ORM\Entity(repositoryClass: ProjectRepository::class)]
#[ORM\Table(name: '`project`')]
#[ORM\HasLifecycleCallbacks]
class Project
{
    // Utilisateur admin ayant créé le projet (traçabilité) ; conservé même si le compte est supprimé (SET NULL)
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

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $image = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    private ?string $location = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $budget = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $startDate = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $endDate = null;

    // Avancement du projet : "planned", "ongoing", "completed"...
    #[ORM\Column(length: 20, options: ['default' => 'planned'])]
    private string $status = 'planned';

    // --- Traductions anglaises ---
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $titleEn = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $descriptionEn = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $locationEn = null;

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
    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): static { $this->description = $description; return $this; }
    public function getImage(): ?string { return $this->image; }
    public function setImage(?string $image): static { $this->image = $image; return $this; }
    public function getLocation(): ?string { return $this->location; }
    public function setLocation(string $location): static { $this->location = $location; return $this; }
    public function getBudget(): ?string { return $this->budget; }
    public function setBudget(?string $budget): static { $this->budget = $budget; return $this; }
    public function getStartDate(): ?\DateTimeImmutable { return $this->startDate; }
    public function setStartDate(?\DateTimeImmutable $startDate): static { $this->startDate = $startDate; return $this; }
    public function getEndDate(): ?\DateTimeImmutable { return $this->endDate; }
    public function setEndDate(?\DateTimeImmutable $endDate): static { $this->endDate = $endDate; return $this; }
    public function getStatus(): string { return $this->status; }
    public function setStatus(string $status): static { $this->status = $status; return $this; }
    public function getCreatedAt(): ?\DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): ?\DateTimeImmutable { return $this->updatedAt; }

    public function getTitleEn(): ?string { return $this->titleEn; }
    public function setTitleEn(?string $titleEn): static { $this->titleEn = $titleEn; return $this; }
    public function getDescriptionEn(): ?string { return $this->descriptionEn; }
    public function setDescriptionEn(?string $descriptionEn): static { $this->descriptionEn = $descriptionEn; return $this; }
    public function getLocationEn(): ?string { return $this->locationEn; }
    public function setLocationEn(?string $locationEn): static { $this->locationEn = $locationEn; return $this; }

    public function getCreatedBy(): ?User { return $this->createdBy; }
    public function setCreatedBy(?User $createdBy): static { $this->createdBy = $createdBy; return $this; }
}
