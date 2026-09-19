<?php

namespace App\Entity;

use App\Repository\NewsletterRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Abonné à la newsletter du site. $unsubscribeToken est un jeton unique
 * généré automatiquement, utilisé dans le lien de désinscription envoyé
 * par email (permet de se désinscrire sans être connecté).
 */
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

    // Permet de désactiver l'abonnement (désinscription) sans supprimer l'historique
    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    private bool $isActive = true;

    // Jeton unique utilisé dans le lien "se désinscrire" envoyé par email
    #[ORM\Column(length: 64, unique: true)]
    private ?string $unsubscribeToken = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeImmutable $subscribedAt = null;

    // Double opt-in : une inscription reste inactive tant que le propriétaire de
    // l'adresse n'a pas cliqué sur le lien de confirmation reçu par email (un bot
    // peut soumettre n'importe quelle adresse, mais pas lire cette boîte mail).
    #[ORM\Column(length: 64, unique: true, nullable: true)]
    private ?string $confirmationToken = null;

    // Date d'envoi du dernier email de confirmation : limite les renvois (anti-harcèlement)
    // et sert à faire expirer le lien.
    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $confirmationSentAt = null;

    // Renseignée à la première confirmation ; null = adresse jamais confirmée (en attente)
    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $confirmedAt = null;

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

    public function getConfirmationToken(): ?string { return $this->confirmationToken; }
    public function setConfirmationToken(?string $confirmationToken): static { $this->confirmationToken = $confirmationToken; return $this; }

    public function getConfirmationSentAt(): ?\DateTimeImmutable { return $this->confirmationSentAt; }
    public function setConfirmationSentAt(?\DateTimeImmutable $confirmationSentAt): static { $this->confirmationSentAt = $confirmationSentAt; return $this; }

    public function getConfirmedAt(): ?\DateTimeImmutable { return $this->confirmedAt; }
    public function setConfirmedAt(?\DateTimeImmutable $confirmedAt): static { $this->confirmedAt = $confirmedAt; return $this; }

    // Inscription jamais confirmée (ni active, ni désinscrite) : en attente du clic sur le lien email
    public function isPending(): bool { return !$this->isActive && $this->confirmedAt === null; }

    // Renseigne automatiquement la date d'inscription et génère le jeton de désinscription si absent
    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $this->subscribedAt = new \DateTimeImmutable();
        if (!$this->unsubscribeToken) {
            $this->unsubscribeToken = bin2hex(random_bytes(32));
        }
    }
}
