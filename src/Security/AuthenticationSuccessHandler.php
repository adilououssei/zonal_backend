<?php

namespace App\Security;

use App\Entity\User;
use Firebase\JWT\JWT;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;

// Handler de succès pour un firewall Symfony basé sur form_login/authenticator classique.
// Non branché dans security.yaml actuellement : la connexion réelle passe par
// AuthController::login (JWT généré manuellement), qui duplique la même logique
// de génération de jeton. Conservé au cas où un login "classique" Symfony
// serait réactivé plus tard.
class AuthenticationSuccessHandler implements AuthenticationSuccessHandlerInterface
{
    private const ALGORITHM = 'HS256';

    public function __construct(
        private readonly string $jwtSecret,
    ) {
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token): ?Response
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return null;
        }

        $now = new \DateTimeImmutable();
        $payload = [
            'iat' => $now->getTimestamp(),
            'exp' => $now->modify('+24 hours')->getTimestamp(),
            'email' => $user->getEmail(),
            'roles' => $user->getRoles(),
        ];

        $jwt = JWT::encode($payload, $this->jwtSecret, self::ALGORITHM);

        return new JsonResponse([
            'token' => $jwt,
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'roles' => $user->getRoles(),
                'firstName' => $user->getFirstName(),
                'lastName' => $user->getLastName(),
            ],
        ]);
    }
}
