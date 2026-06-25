<?php

namespace App\Entity;

use App\Repository\SettingsRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SettingsRepository::class)]
#[ORM\Table(name: '`settings`')]
class Settings
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    // General tab
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $orgName = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $logo = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $slogan = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $email = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $phone = null;

    // Contact tab
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $contactEmail = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $contactPhone = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $contactPhoneSecondary = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $address = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $whatsapp = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $googleMapsIframe = null;

    // Social tab
    #[ORM\Column(length: 500, nullable: true)]
    private ?string $facebook = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $linkedin = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $youtube = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $whatsappUrl = null;

    // Footer tab
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $footerPresentation = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $copyright = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $openingHours = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $legalLink = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $privacyLink = null;

    // Images tab
    #[ORM\Column(length: 500, nullable: true)]
    private ?string $heroImage = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $aboutImage = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $programsImage = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $eventsImage = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $contactImage = null;

    // SEO tab
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $metaTitle = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $metaDescription = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $metaKeywords = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $ogImage = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $googleAnalyticsId = null;

    public function getId(): ?int { return $this->id; }

    // General
    public function getOrgName(): ?string { return $this->orgName; }
    public function setOrgName(?string $v): static { $this->orgName = $v; return $this; }
    public function getLogo(): ?string { return $this->logo; }
    public function setLogo(?string $v): static { $this->logo = $v; return $this; }
    public function getSlogan(): ?string { return $this->slogan; }
    public function setSlogan(?string $v): static { $this->slogan = $v; return $this; }
    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $v): static { $this->description = $v; return $this; }
    public function getEmail(): ?string { return $this->email; }
    public function setEmail(?string $v): static { $this->email = $v; return $this; }
    public function getPhone(): ?string { return $this->phone; }
    public function setPhone(?string $v): static { $this->phone = $v; return $this; }

    // Contact
    public function getContactEmail(): ?string { return $this->contactEmail; }
    public function setContactEmail(?string $v): static { $this->contactEmail = $v; return $this; }
    public function getContactPhone(): ?string { return $this->contactPhone; }
    public function setContactPhone(?string $v): static { $this->contactPhone = $v; return $this; }
    public function getContactPhoneSecondary(): ?string { return $this->contactPhoneSecondary; }
    public function setContactPhoneSecondary(?string $v): static { $this->contactPhoneSecondary = $v; return $this; }
    public function getAddress(): ?string { return $this->address; }
    public function setAddress(?string $v): static { $this->address = $v; return $this; }
    public function getWhatsapp(): ?string { return $this->whatsapp; }
    public function setWhatsapp(?string $v): static { $this->whatsapp = $v; return $this; }
    public function getGoogleMapsIframe(): ?string { return $this->googleMapsIframe; }
    public function setGoogleMapsIframe(?string $v): static { $this->googleMapsIframe = $v; return $this; }

    // Social
    public function getFacebook(): ?string { return $this->facebook; }
    public function setFacebook(?string $v): static { $this->facebook = $v; return $this; }
    public function getLinkedin(): ?string { return $this->linkedin; }
    public function setLinkedin(?string $v): static { $this->linkedin = $v; return $this; }
    public function getYoutube(): ?string { return $this->youtube; }
    public function setYoutube(?string $v): static { $this->youtube = $v; return $this; }
    public function getWhatsappUrl(): ?string { return $this->whatsappUrl; }
    public function setWhatsappUrl(?string $v): static { $this->whatsappUrl = $v; return $this; }

    // Footer
    public function getFooterPresentation(): ?string { return $this->footerPresentation; }
    public function setFooterPresentation(?string $v): static { $this->footerPresentation = $v; return $this; }
    public function getCopyright(): ?string { return $this->copyright; }
    public function setCopyright(?string $v): static { $this->copyright = $v; return $this; }
    public function getOpeningHours(): ?array { return $this->openingHours; }
    public function setOpeningHours(?array $v): static { $this->openingHours = $v; return $this; }
    public function getLegalLink(): ?string { return $this->legalLink; }
    public function setLegalLink(?string $v): static { $this->legalLink = $v; return $this; }
    public function getPrivacyLink(): ?string { return $this->privacyLink; }
    public function setPrivacyLink(?string $v): static { $this->privacyLink = $v; return $this; }

    // Images
    public function getHeroImage(): ?string { return $this->heroImage; }
    public function setHeroImage(?string $v): static { $this->heroImage = $v; return $this; }
    public function getAboutImage(): ?string { return $this->aboutImage; }
    public function setAboutImage(?string $v): static { $this->aboutImage = $v; return $this; }
    public function getProgramsImage(): ?string { return $this->programsImage; }
    public function setProgramsImage(?string $v): static { $this->programsImage = $v; return $this; }
    public function getEventsImage(): ?string { return $this->eventsImage; }
    public function setEventsImage(?string $v): static { $this->eventsImage = $v; return $this; }
    public function getContactImage(): ?string { return $this->contactImage; }
    public function setContactImage(?string $v): static { $this->contactImage = $v; return $this; }

    // SEO
    public function getMetaTitle(): ?string { return $this->metaTitle; }
    public function setMetaTitle(?string $v): static { $this->metaTitle = $v; return $this; }
    public function getMetaDescription(): ?string { return $this->metaDescription; }
    public function setMetaDescription(?string $v): static { $this->metaDescription = $v; return $this; }
    public function getMetaKeywords(): ?string { return $this->metaKeywords; }
    public function setMetaKeywords(?string $v): static { $this->metaKeywords = $v; return $this; }
    public function getOgImage(): ?string { return $this->ogImage; }
    public function setOgImage(?string $v): static { $this->ogImage = $v; return $this; }
    public function getGoogleAnalyticsId(): ?string { return $this->googleAnalyticsId; }
    public function setGoogleAnalyticsId(?string $v): static { $this->googleAnalyticsId = $v; return $this; }
}
