<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;

// Parcours "mot de passe oublié" : demande de réinitialisation par email, puis
// changement du mot de passe via le jeton reçu par email.
#[Route('/api')]
class ForgotPasswordController extends AbstractController
{
    #[Route('/forgot-password', name: 'forgot_password', methods: ['POST'])]
    public function forgotPassword(
        Request $request,
        UserRepository $repo,
        EntityManagerInterface $em,
        MailerInterface $mailer,
        LoggerInterface $logger,
        #[Autowire(service: 'limiter.public_form')] RateLimiterFactory $publicFormLimiter,
        #[Autowire('%env(FRONTEND_URL)%')] string $frontendUrl,
    ): JsonResponse {
        // Limite le nombre de demandes par IP pour éviter le spam de cet endpoint
        if (!$publicFormLimiter->create($request->getClientIp())->consume(1)->isAccepted()) {
            return $this->json(['error' => 'Trop de demandes. Réessayez plus tard.'], Response::HTTP_TOO_MANY_REQUESTS);
        }

        $data = json_decode($request->getContent(), true);
        $email = $data['email'] ?? '';

        if (!filter_var($email, \FILTER_VALIDATE_EMAIL)) {
            return $this->json(['error' => 'Adresse e-mail invalide.'], Response::HTTP_BAD_REQUEST);
        }

        $user = $repo->findOneBy(['email' => $email]);
        if (!$user) {
            // Message volontairement identique que l'email existe ou non, pour ne pas
            // permettre à quelqu'un de deviner quels emails sont enregistrés (énumération)
            return $this->json(['message' => 'Si cette adresse existe, un email de réinitialisation a été envoyé.']);
        }

        $token = bin2hex(random_bytes(32));
        $user->setPasswordResetToken($token);
        $user->setPasswordResetExpiresAt(new \DateTimeImmutable('+1 hour'));
        $em->flush();

        try {
            $email = (new Email())
                ->from('noreply@zonalong.org')
                ->to($user->getEmail())
                ->subject('Réinitialisation de mot de passe – ZONAL')
                ->html($this->renderView('emails/reset_password.html.twig', [
                    'user' => $user,
                    'resetUrl' => rtrim($frontendUrl, '/') . "/reset-password/$token",
                ]));
            $mailer->send($email);
        } catch (\Exception $e) {
            // On ne révèle pas l'échec au client (éviter l'énumération d'emails),
            // mais on le trace : avant ce correctif, ces échecs étaient invisibles.
            $logger->error('Échec de l\'envoi de l\'email de réinitialisation de mot de passe', [
                'email' => $user->getEmail(),
                'exception' => $e,
            ]);
        }

        return $this->json(['message' => 'Si cette adresse existe, un email de réinitialisation a été envoyé.']);
    }

    #[Route('/reset-password', name: 'reset_password', methods: ['POST'])]
    public function resetPassword(
        Request $request,
        UserRepository $repo,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher,
        #[Autowire(service: 'limiter.login')] RateLimiterFactory $loginLimiter,
    ): JsonResponse {
        // Réutilise le même limiteur que la connexion pour empêcher un bruteforce du jeton
        if (!$loginLimiter->create($request->getClientIp())->consume(1)->isAccepted()) {
            return $this->json(['error' => 'Trop de tentatives. Réessayez plus tard.'], Response::HTTP_TOO_MANY_REQUESTS);
        }

        $data = json_decode($request->getContent(), true);
        $token = $data['token'] ?? '';
        $newPassword = $data['password'] ?? '';

        if (strlen($newPassword) < 6) {
            return $this->json(['error' => 'Le mot de passe doit faire au moins 6 caractères.'], Response::HTTP_BAD_REQUEST);
        }

        $user = $repo->findOneBy(['passwordResetToken' => $token]);
        if (!$user || !$user->isPasswordResetTokenValid()) {
            return $this->json(['error' => 'Lien de réinitialisation invalide ou expiré.'], Response::HTTP_BAD_REQUEST);
        }

        $user->setPassword($passwordHasher->hashPassword($user, $newPassword));
        // Le jeton est à usage unique : on l'invalide immédiatement après utilisation
        $user->setPasswordResetToken(null);
        $user->setPasswordResetExpiresAt(null);
        $em->flush();

        return $this->json(['message' => 'Mot de passe réinitialisé avec succès.']);
    }
}
