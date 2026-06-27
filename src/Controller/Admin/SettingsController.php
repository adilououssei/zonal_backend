<?php

namespace App\Controller\Admin;

use App\Entity\Settings;
use App\Repository\SettingsRepository;
use App\Service\LocaleHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/admin/settings')]
class SettingsController extends AbstractController
{
    public function __construct(
        private LocaleHelper $localeHelper,
    ) {
    }
    #[Route('', name: 'admin_settings_get', methods: ['GET'])]
    public function getSettings(SettingsRepository $repo, EntityManagerInterface $em): JsonResponse
    {
        $settings = $repo->findOneBy([]);
        if (!$settings) {
            $settings = new Settings();
            $em->persist($settings);
            $em->flush();
        }
        return $this->json($this->serialize($settings));
    }

    #[Route('', name: 'admin_settings_update', methods: ['PUT'])]
    public function update(Request $request, SettingsRepository $repo, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!$data) {
            return $this->json(['error' => 'Données requises.'], Response::HTTP_BAD_REQUEST);
        }

        $settings = $repo->findOneBy([]);
        if (!$settings) {
            $settings = new Settings();
            $em->persist($settings);
        }

        // General
        if (isset($data['orgName'])) $settings->setOrgName($data['orgName']);
        if (isset($data['orgNameEn'])) $settings->setOrgNameEn($data['orgNameEn']);
        if (isset($data['logo'])) $settings->setLogo($data['logo']);
        if (isset($data['slogan'])) $settings->setSlogan($data['slogan']);
        if (isset($data['sloganEn'])) $settings->setSloganEn($data['sloganEn']);
        if (isset($data['description'])) $settings->setDescription($data['description']);
        if (isset($data['descriptionEn'])) $settings->setDescriptionEn($data['descriptionEn']);
        if (isset($data['email'])) $settings->setEmail($data['email']);
        if (isset($data['phone'])) $settings->setPhone($data['phone']);

        // Contact
        if (isset($data['contactEmail'])) $settings->setContactEmail($data['contactEmail']);
        if (isset($data['contactPhone'])) $settings->setContactPhone($data['contactPhone']);
        if (isset($data['contactPhoneSecondary'])) $settings->setContactPhoneSecondary($data['contactPhoneSecondary']);
        if (isset($data['address'])) $settings->setAddress($data['address']);
        if (isset($data['whatsapp'])) $settings->setWhatsapp($data['whatsapp']);
        if (isset($data['googleMapsIframe'])) $settings->setGoogleMapsIframe($data['googleMapsIframe']);

        // Social
        if (isset($data['facebook'])) $settings->setFacebook($data['facebook']);
        if (isset($data['linkedin'])) $settings->setLinkedin($data['linkedin']);
        if (isset($data['youtube'])) $settings->setYoutube($data['youtube']);
        if (isset($data['whatsappUrl'])) $settings->setWhatsappUrl($data['whatsappUrl']);

        // Footer
        if (isset($data['footerPresentation'])) $settings->setFooterPresentation($data['footerPresentation']);
        if (isset($data['footerPresentationEn'])) $settings->setFooterPresentationEn($data['footerPresentationEn']);
        if (isset($data['copyright'])) $settings->setCopyright($data['copyright']);
        if (isset($data['copyrightEn'])) $settings->setCopyrightEn($data['copyrightEn']);
        if (isset($data['openingHours'])) $settings->setOpeningHours($data['openingHours']);
        if (isset($data['legalLink'])) $settings->setLegalLink($data['legalLink']);
        if (isset($data['privacyLink'])) $settings->setPrivacyLink($data['privacyLink']);

        // Images
        if (isset($data['heroImage'])) $settings->setHeroImage($data['heroImage']);
        if (isset($data['aboutImage'])) $settings->setAboutImage($data['aboutImage']);
        if (isset($data['programsImage'])) $settings->setProgramsImage($data['programsImage']);
        if (isset($data['eventsImage'])) $settings->setEventsImage($data['eventsImage']);
        if (isset($data['contactImage'])) $settings->setContactImage($data['contactImage']);

        // SEO
        if (isset($data['metaTitle'])) $settings->setMetaTitle($data['metaTitle']);
        if (isset($data['metaDescription'])) $settings->setMetaDescription($data['metaDescription']);
        if (isset($data['metaKeywords'])) $settings->setMetaKeywords($data['metaKeywords']);
        if (isset($data['ogImage'])) $settings->setOgImage($data['ogImage']);
        if (isset($data['googleAnalyticsId'])) $settings->setGoogleAnalyticsId($data['googleAnalyticsId']);

        $em->flush();
        return $this->json($this->serialize($settings));
    }

    private function serialize(Settings $s): array
    {
        return [
            'orgName' => $this->localeHelper->localize($s, 'orgName'),
            'orgNameEn' => $s->getOrgNameEn(),
            'logo' => $s->getLogo(),
            'slogan' => $this->localeHelper->localize($s, 'slogan'),
            'sloganEn' => $s->getSloganEn(),
            'description' => $this->localeHelper->localize($s, 'description'),
            'descriptionEn' => $s->getDescriptionEn(),
            'email' => $s->getEmail(),
            'phone' => $s->getPhone(),

            'contactEmail' => $s->getContactEmail(),
            'contactPhone' => $s->getContactPhone(),
            'contactPhoneSecondary' => $s->getContactPhoneSecondary(),
            'address' => $s->getAddress(),
            'whatsapp' => $s->getWhatsapp(),
            'googleMapsIframe' => $s->getGoogleMapsIframe(),

            'facebook' => $s->getFacebook(),
            'linkedin' => $s->getLinkedin(),
            'youtube' => $s->getYoutube(),
            'whatsappUrl' => $s->getWhatsappUrl(),

            'footerPresentation' => $this->localeHelper->localize($s, 'footerPresentation'),
            'footerPresentationEn' => $s->getFooterPresentationEn(),
            'copyright' => $this->localeHelper->localize($s, 'copyright'),
            'copyrightEn' => $s->getCopyrightEn(),
            'openingHours' => $s->getOpeningHours(),
            'legalLink' => $s->getLegalLink(),
            'privacyLink' => $s->getPrivacyLink(),

            'heroImage' => $s->getHeroImage(),
            'aboutImage' => $s->getAboutImage(),
            'programsImage' => $s->getProgramsImage(),
            'eventsImage' => $s->getEventsImage(),
            'contactImage' => $s->getContactImage(),

            'metaTitle' => $s->getMetaTitle(),
            'metaDescription' => $s->getMetaDescription(),
            'metaKeywords' => $s->getMetaKeywords(),
            'ogImage' => $s->getOgImage(),
            'googleAnalyticsId' => $s->getGoogleAnalyticsId(),
        ];
    }
}
