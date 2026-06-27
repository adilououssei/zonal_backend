<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\RequestStack;

class LocaleHelper
{
    private RequestStack $requestStack;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

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
