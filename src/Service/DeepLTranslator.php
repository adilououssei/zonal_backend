<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Traduction automatique FR -> EN du contenu dynamique (événements,
 * actualités, projets) via l'API DeepL, pour éviter la double saisie
 * manuelle des champs *En par les administrateurs.
 *
 * Best-effort : si la clé est absente, le quota est dépassé, ou l'API est
 * injoignable, renvoie null plutôt que de bloquer la publication.
 */
class DeepLTranslator
{
    private const API_URL = 'https://api-free.deepl.com/v2/translate';

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly LoggerInterface $logger,
        #[Autowire('%env(DEEPL_API_KEY)%')] private readonly ?string $apiKey = null,
    ) {
    }

    public function isConfigured(): bool
    {
        return !empty($this->apiKey);
    }

    /**
     * @param bool $isHtml Préserve les balises HTML (description/contenu riches) pendant la traduction.
     */
    public function translateToEnglish(?string $text, bool $isHtml = false): ?string
    {
        $text = trim((string) $text);
        if ($text === '' || !$this->isConfigured()) {
            return null;
        }

        try {
            $response = $this->httpClient->request('POST', self::API_URL, [
                'headers' => [
                    'Authorization' => 'DeepL-Auth-Key ' . $this->apiKey,
                ],
                'body' => [
                    'text' => [$text],
                    'source_lang' => 'FR',
                    'target_lang' => 'EN-US',
                    'tag_handling' => $isHtml ? 'html' : null,
                ],
                'timeout' => 10,
            ]);

            $data = $response->toArray();
            return $data['translations'][0]['text'] ?? null;
        } catch (\Throwable $e) {
            $this->logger->error('Échec de la traduction automatique DeepL', ['exception' => $e]);
            return null;
        }
    }
}
