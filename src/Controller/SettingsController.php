<?php

namespace App\Controller;

use App\Entity\Settings;
use App\Repository\SettingsRepository;
use App\Service\LocaleHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

// Version publique (lecture seule) des réglages globaux du site : nom de
// l'organisation, réseaux sociaux, pied de page, et métadonnées SEO. Sert au
// front public (balises <title>/meta, données structurées JSON-LD, footer)
// — sans authentification, contrairement à /api/admin/settings qui, lui,
// permet la modification et est réservé au super admin.
#[Route('/api/settings')]
class SettingsController extends AbstractController
{
    public function __construct(
        private LocaleHelper $localeHelper,
    ) {
    }

    #[Route('', name: 'public_settings_get', methods: ['GET'])]
    public function getSettings(SettingsRepository $repo, EntityManagerInterface $em): JsonResponse
    {
        $settings = $repo->findOneBy([]);
        if (!$settings) {
            $settings = new Settings();
            $em->persist($settings);
            $em->flush();
        }

        return $this->json([
            'orgName' => $this->localeHelper->localize($settings, 'orgName'),
            'logo' => $settings->getLogo(),
            'slogan' => $this->localeHelper->localize($settings, 'slogan'),
            'description' => $this->localeHelper->localize($settings, 'description'),
            'email' => $settings->getEmail(),
            'phone' => $settings->getPhone(),

            'contactEmail' => $settings->getContactEmail(),
            'contactPhone' => $settings->getContactPhone(),
            'address' => $settings->getAddress(),
            'whatsapp' => $settings->getWhatsapp(),

            'facebook' => $settings->getFacebook(),
            'linkedin' => $settings->getLinkedin(),
            'youtube' => $settings->getYoutube(),
            'whatsappUrl' => $settings->getWhatsappUrl(),

            'footerPresentation' => $this->localeHelper->localize($settings, 'footerPresentation'),
            'copyright' => $this->localeHelper->localize($settings, 'copyright'),
            'legalLink' => $settings->getLegalLink(),
            'privacyLink' => $settings->getPrivacyLink(),

            'metaTitle' => $settings->getMetaTitle(),
            'metaDescription' => $settings->getMetaDescription(),
            'metaKeywords' => $settings->getMetaKeywords(),
            'ogImage' => $settings->getOgImage(),
        ]);
    }
}
