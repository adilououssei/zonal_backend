<?php

namespace App\Entity;

use App\Repository\NewsletterRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: NewsletterRepository::class)]
#[ORM\Table(name: '`newsletter`')]
#[ORM\HasLifecycleCallbacks]
class Newsletter
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Email]
    private ?string $email = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $name = null;

    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    private bool $isActive = true;

    #[ORM\Column(length: 64, unique: true)]
    private ?string $unsubscribeToken = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeImmutable $subscribedAt = null;

    public function getId(): ?int { return $this->id; }

    public function getEmail(): ?string { return $this->email; }
    public function setEmail(string $email): static { $this->email = $email; return $this; }

    public function getName(): ?string { return $this->name; }
    public function setName(?string $name): static { $this->name = $name; return $this; }

    public function isActive(): bool { return $this->isActive; }
    public function setIsActive(bool $isActive): static { $this->isActive = $isActive; return $this; }

    public function getUnsubscribeToken(): ?string { return $this->unsubscribeToken; }
    public function setUnsubscribeToken(string $unsubscribeToken): static { $this->unsubscribeToken = $unsubscribeToken; return $this; }

    public function getSubscribedAt(): ?\DateTimeImmutable { return $this->subscribedAt; }
    public function setSubscribedAt(\DateTimeImmutable $subscribedAt): static { $this->subscribedAt = $subscribedAt; return $this; }

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $this->subscribedAt = new \DateTimeImmutable();
        if (!$this->unsubscribeToken) {
            $this->unsubscribeToken = bin2hex(random_bytes(32));
        }
    }
}
