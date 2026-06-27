<?php

namespace App\Entity;

use App\Repository\NewsRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: NewsRepository::class)]
#[ORM\Table(name: '`news`')]
#[ORM\HasLifecycleCallbacks]
class News
{
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
    private ?string $excerpt = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $content = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    #[Assert\NotBlank]
    private ?\DateTimeImmutable $date = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank]
    private ?string $category = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $author = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $coverImage = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $gallery = null;

    #[ORM\Column(type: Types::INTEGER, options: ['default' => 0])]
    private int $views = 0;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $titleEn = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $excerptEn = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $contentEn = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $authorEn = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $categoryEn = null;

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
    public function getExcerpt(): ?string { return $this->excerpt; }
    public function setExcerpt(?string $excerpt): static { $this->excerpt = $excerpt; return $this; }
    public function getContent(): ?string { return $this->content; }
    public function setContent(?string $content): static { $this->content = $content; return $this; }
    public function getDate(): ?\DateTimeImmutable { return $this->date; }
    public function setDate(\DateTimeImmutable $date): static { $this->date = $date; return $this; }
    public function getCategory(): ?string { return $this->category; }
    public function setCategory(string $category): static { $this->category = $category; return $this; }
    public function getAuthor(): ?string { return $this->author; }
    public function setAuthor(?string $author): static { $this->author = $author; return $this; }
    public function getCoverImage(): ?string { return $this->coverImage; }
    public function setCoverImage(?string $coverImage): static { $this->coverImage = $coverImage; return $this; }
    public function getGallery(): ?array { return $this->gallery; }
    public function setGallery(?array $gallery): static { $this->gallery = $gallery; return $this; }
    public function getViews(): int { return $this->views; }
    public function setViews(int $views): static { $this->views = $views; return $this; }
    public function getCreatedAt(): ?\DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): ?\DateTimeImmutable { return $this->updatedAt; }

    public function getTitleEn(): ?string { return $this->titleEn; }
    public function setTitleEn(?string $titleEn): static { $this->titleEn = $titleEn; return $this; }
    public function getExcerptEn(): ?string { return $this->excerptEn; }
    public function setExcerptEn(?string $excerptEn): static { $this->excerptEn = $excerptEn; return $this; }
    public function getContentEn(): ?string { return $this->contentEn; }
    public function setContentEn(?string $contentEn): static { $this->contentEn = $contentEn; return $this; }
    public function getAuthorEn(): ?string { return $this->authorEn; }
    public function setAuthorEn(?string $authorEn): static { $this->authorEn = $authorEn; return $this; }
    public function getCategoryEn(): ?string { return $this->categoryEn; }
    public function setCategoryEn(?string $categoryEn): static { $this->categoryEn = $categoryEn; return $this; }

    public function getCreatedBy(): ?User { return $this->createdBy; }
    public function setCreatedBy(?User $createdBy): static { $this->createdBy = $createdBy; return $this; }
}
