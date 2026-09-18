<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Vérifie les jetons reCAPTCHA auprès de l'API Google, pour protéger les
 * formulaires publics contre les soumissions automatisées par des bots :
 * - v3 (invisible, basé sur un score) pour la newsletter ;
 * - v2 (case "Je ne suis pas un robot") pour le formulaire de contact.
 * Les deux types utilisent des paires de clés distinctes chez Google.
 *
 * Best-effort comme DeepLTranslator : si la clé secrète correspondante n'est
 * pas configurée (dev local sans compte reCAPTCHA), la vérification est
 * ignorée plutôt que de bloquer les formulaires.
 */
class RecaptchaVerifier
{
    private const VERIFY_URL = 'https://www.google.com/recaptcha/api/siteverify';

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly LoggerInterface $logger,
        #[Autowire('%env(RECAPTCHA_SECRET_KEY)%')] private readonly ?string $secretKey = null,
        #[Autowire('%env(RECAPTCHA_V2_SECRET_KEY)%')] private readonly ?string $checkboxSecretKey = null,
    ) {
    }

    public function isConfigured(): bool
    {
        return !empty($this->secretKey);
    }

    /**
     * reCAPTCHA v3.
     *
     * @param string $expectedAction Action déclarée côté frontend (ex. "newsletter_subscribe") :
     *                                doit correspondre à celle du jeton, pour empêcher qu'un
     *                                jeton obtenu sur une autre action soit rejoué ici.
     * @param float $minScore Score minimal accepté (0 = bot certain, 1 = humain certain)
     */
    public function verify(?string $token, string $expectedAction, float $minScore = 0.5): bool
    {
        if (empty($this->secretKey)) {
            return true;
        }

        if (!$token) {
            return false;
        }

        $data = $this->callGoogle($this->secretKey, $token);
        if ($data === null) {
            // Best-effort : une panne de l'API Google ne doit pas bloquer les envois
            return true;
        }

        return ($data['success'] ?? false)
            && ($data['action'] ?? null) === $expectedAction
            && ($data['score'] ?? 0) >= $minScore;
    }

    /** reCAPTCHA v2 (case à cocher "Je ne suis pas un robot"). */
    public function verifyCheckbox(?string $token): bool
    {
        if (empty($this->checkboxSecretKey)) {
            return true;
        }

        if (!$token) {
            return false;
        }

        $data = $this->callGoogle($this->checkboxSecretKey, $token);
        if ($data === null) {
            return true;
        }

        return (bool) ($data['success'] ?? false);
    }

    /** @return array<string, mixed>|null null si l'API Google est injoignable */
    private function callGoogle(string $secret, string $token): ?array
    {
        try {
            $response = $this->httpClient->request('POST', self::VERIFY_URL, [
                'body' => [
                    'secret' => $secret,
                    'response' => $token,
                ],
                'timeout' => 10,
            ]);

            return $response->toArray(false);
        } catch (\Throwable $e) {
            $this->logger->error('Échec de la vérification reCAPTCHA', ['exception' => $e]);
            return null;
        }
    }
}
