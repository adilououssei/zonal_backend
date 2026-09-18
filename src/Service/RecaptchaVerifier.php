<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Vérifie les jetons reCAPTCHA v3 auprès de l'API Google, pour protéger les
 * formulaires publics (newsletter...) contre les soumissions automatisées
 * par des bots.
 *
 * Best-effort comme DeepLTranslator : si la clé secrète n'est pas configurée
 * (dev local sans compte reCAPTCHA), la vérification est ignorée plutôt que
 * de bloquer les formulaires.
 */
class RecaptchaVerifier
{
    private const VERIFY_URL = 'https://www.google.com/recaptcha/api/siteverify';

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly LoggerInterface $logger,
        #[Autowire('%env(RECAPTCHA_SECRET_KEY)%')] private readonly ?string $secretKey = null,
    ) {
    }

    public function isConfigured(): bool
    {
        return !empty($this->secretKey);
    }

    /**
     * @param string $expectedAction Action déclarée côté frontend (ex. "newsletter_subscribe") :
     *                                doit correspondre à celle du jeton, pour empêcher qu'un
     *                                jeton obtenu sur une autre action soit rejoué ici.
     * @param float $minScore Score minimal reCAPTCHA v3 accepté (0 = bot certain, 1 = humain certain)
     */
    public function verify(?string $token, string $expectedAction, float $minScore = 0.5): bool
    {
        if (!$this->isConfigured()) {
            return true;
        }

        if (!$token) {
            return false;
        }

        try {
            $response = $this->httpClient->request('POST', self::VERIFY_URL, [
                'body' => [
                    'secret' => $this->secretKey,
                    'response' => $token,
                ],
                'timeout' => 10,
            ]);

            $data = $response->toArray(false);

            return ($data['success'] ?? false)
                && ($data['action'] ?? null) === $expectedAction
                && ($data['score'] ?? 0) >= $minScore;
        } catch (\Throwable $e) {
            $this->logger->error('Échec de la vérification reCAPTCHA', ['exception' => $e]);
            // Best-effort : une panne de l'API Google ne doit pas bloquer les inscriptions
            return true;
        }
    }
}
