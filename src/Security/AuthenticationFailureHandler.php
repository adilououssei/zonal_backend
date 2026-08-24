<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;

// Handler d'échec pour un firewall Symfony basé sur form_login/authenticator classique.
// Non branché dans security.yaml actuellement : la connexion réelle passe par
// AuthController::login (JWT généré manuellement), pas par ce mécanisme.
// Conservé au cas où un login "classique" Symfony serait réactivé plus tard.
class AuthenticationFailureHandler implements AuthenticationFailureHandlerInterface
{
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        return new JsonResponse(
            ['error' => 'Identifiants invalides.'],
            Response::HTTP_UNAUTHORIZED
        );
    }
}
