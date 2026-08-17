<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api')]
class ForgotPasswordController extends AbstractController
{
    #[Route('/forgot-password', name: 'forgot_password', methods: ['POST'])]
    public function forgotPassword(
        Request $request,
        UserRepository $repo,
        EntityManagerInterface $em,
        MailerInterface $mailer,
        #[Autowire(service: 'limiter.public_form')] RateLimiterFactory $publicFormLimiter,
    ): JsonResponse {
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
                    'resetUrl' => "http://localhost:5173/reset-password/$token",
                ]));
            $mailer->send($email);
        } catch (\Exception) {
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
        $user->setPasswordResetToken(null);
        $user->setPasswordResetExpiresAt(null);
        $em->flush();

        return $this->json(['message' => 'Mot de passe réinitialisé avec succès.']);
    }
}
