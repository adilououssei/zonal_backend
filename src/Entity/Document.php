<?php

namespace App\Entity;

use App\Repository\DocumentRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Document téléchargeable mis à disposition sur le site (rapports, chartes...).
 * $file est le chemin du fichier stocké sur le serveur, $size sa taille en
 * octets et $type son format (PDF, DOCX...).
 */
#[ORM\Entity(repositoryClass: DocumentRepository::class)]
#[ORM\Table(name: '`document`')]
#[ORM\HasLifecycleCallbacks]
class Document
{
    // Utilisateur admin ayant ajouté le document (traçabilité) ; conservé même si le compte est supprimé (SET NULL)
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $createdBy = null;
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    private ?string $name = null;

    // Format du fichier (PDF, DOCX...)
    #[ORM\Column(length: 10)]
    private string $type = 'PDF';

    // Taille du fichier en octets
    #[ORM\Column(type: Types::BIGINT, nullable: true)]
    private ?int $size = null;

    // Chemin/URL du fichier stocké sur le serveur
    #[ORM\Column(length: 500)]
    #[Assert\NotBlank]
    private ?string $file = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $category = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $date = null;

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
    public function getName(): ?string { return $this->name; }
    public function setName(string $name): static { $this->name = $name; return $this; }
    public function getType(): string { return $this->type; }
    public function setType(string $type): static { $this->type = $type; return $this; }
    public function getSize(): ?int { return $this->size; }
    public function setSize(?int $size): static { $this->size = $size; return $this; }
    public function getFile(): ?string { return $this->file; }
    public function setFile(string $file): static { $this->file = $file; return $this; }
    public function getCategory(): ?string { return $this->category; }
    public function setCategory(?string $category): static { $this->category = $category; return $this; }
    public function getDate(): ?\DateTimeImmutable { return $this->date; }
    public function setDate(?\DateTimeImmutable $date): static { $this->date = $date; return $this; }
    public function getCreatedAt(): ?\DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): ?\DateTimeImmutable { return $this->updatedAt; }

    public function getCreatedBy(): ?User { return $this->createdBy; }
    public function setCreatedBy(?User $createdBy): static { $this->createdBy = $createdBy; return $this; }
}
