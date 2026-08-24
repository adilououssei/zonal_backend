<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\RequestStack;

// Détermine la langue de la requête courante et renvoie la bonne traduction
// d'un champ d'entité (ex: "title" vs "titleEn"). Utilisé par tous les
// contrôleurs publics et admin pour sérialiser le contenu multilingue.
class LocaleHelper
{
    private RequestStack $requestStack;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    // La langue peut être forcée via ?_locale=en, sinon on se base sur l'en-tête
    // Accept-Language envoyé par le navigateur ; le français est la langue par défaut.
    public function getLocale(): string
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request) return 'fr';

        $locale = $request->query->get('_locale');
        if ($locale && in_array($locale, ['fr', 'en'])) return $locale;

        $header = $request->headers->get('Accept-Language');
        if ($header && str_starts_with($header, 'en')) return 'en';

        return 'fr';
    }

    // Renvoie $entity->get{Field}En() si la langue est anglaise et que ce champ
    // traduit existe et n'est pas vide, sinon replie sur $entity->get{Field}()
    // (le champ français d'origine).
    public function localize(object $entity, string $field): ?string
    {
        $locale = $this->getLocale();
        if ($locale === 'en') {
            $getter = 'get' . ucfirst($field) . 'En';
            if (method_exists($entity, $getter)) {
                $value = $entity->$getter();
                if ($value !== null && $value !== '') return $value;
            }
        }
        $getter = 'get' . ucfirst($field);
        return method_exists($entity, $getter) ? $entity->$getter() : null;
    }
}
