<?php

namespace App\Controller;

use App\Entity\Newsletter;
use App\Repository\NewsletterRepository;
use App\Service\RecaptchaVerifier;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;

// Endpoints publics de la newsletter : inscription (en deux étapes : demande puis
// confirmation par email, "double opt-in"), et désinscription (à ne pas confondre
// avec Admin\NewsletterController qui gère la liste des abonnés côté admin).
#[Route('/api/newsletter')]
class NewsletterController extends AbstractController
{
    private const PENDING_MESSAGE = 'Merci ! Un email de confirmation vient de vous être envoyé : cliquez sur le lien qu\'il contient pour finaliser votre inscription.';

    #[Route('/subscribe', name: 'newsletter_subscribe', methods: ['POST'])]
    public function subscribe(
        Request $request,
        NewsletterRepository $repo,
        EntityManagerInterface $em,
        MailerInterface $mailer,
        LoggerInterface $logger,
        RecaptchaVerifier $recaptcha,
        #[Autowire(service: 'limiter.public_form')] RateLimiterFactory $publicFormLimiter,
        #[Autowire('%env(FRONTEND_URL)%')] string $frontendUrl,
    ): JsonResponse {
        // Un robot d'inscription en masse envoie un User-Agent qui commence par un guillemet,
        // ce qu'aucun vrai navigateur ne fait. Même réponse générique que pour un email invalide.
        $userAgent = (string) $request->headers->get('User-Agent', '');
        if ($userAgent !== '' && in_array($userAgent[0], ['"', "'"], true)) {
            $logger->warning('Inscription newsletter rejetée : User-Agent suspect', [
                'ip' => $request->getClientIp(),
                'userAgent' => mb_substr($userAgent, 0, 200),
            ]);
            return $this->json(['error' => 'Adresse e-mail invalide.'], Response::HTTP_BAD_REQUEST);
        }

        // Limite le nombre d'inscriptions par IP pour éviter le spam de ce formulaire
        if (!$publicFormLimiter->create($request->getClientIp())->consume(1)->isAccepted()) {
            return $this->json(['error' => 'Trop de demandes. Réessayez plus tard.'], Response::HTTP_TOO_MANY_REQUESTS);
        }

        $data = json_decode($request->getContent(), true);
        $email = $data['email'] ?? '';

        if (!filter_var($email, \FILTER_VALIDATE_EMAIL)) {
            return $this->json(['error' => 'Adresse e-mail invalide.'], Response::HTTP_BAD_REQUEST);
        }

        // Même message d'erreur générique que la validation d'email ci-dessus : on
        // évite de révéler à un bot que c'est spécifiquement reCAPTCHA qui a échoué.
        if (!$recaptcha->verify($data['recaptchaToken'] ?? null, 'newsletter_subscribe')) {
            return $this->json(['error' => 'Adresse e-mail invalide.'], Response::HTTP_BAD_REQUEST);
        }

        $existing = $repo->findByEmail($email);
        if ($existing) {
            if ($existing->isActive()) {
                return $this->json(['message' => 'Vous êtes déjà abonné à notre newsletter.'], Response::HTTP_OK);
            }
            // Inscription jamais confirmée, ou ancien désabonné qui revient : dans les deux
            // cas l'abonnement ne redevient actif qu'après un clic sur le lien de confirmation
            if (!empty($data['name'])) {
                $existing->setName($data['name']);
            }
            $this->sendConfirmationRequest($mailer, $logger, $em, $existing, $frontendUrl);

            return $this->json(['message' => self::PENDING_MESSAGE], Response::HTTP_CREATED);
        }

        $subscriber = new Newsletter();
        $subscriber->setEmail($email);
        $subscriber->setName($data['name'] ?? null);
        $subscriber->setIsActive(false);

        $em->persist($subscriber);
        $em->flush();

        $this->sendConfirmationRequest($mailer, $logger, $em, $subscriber, $frontendUrl);

        return $this->json(['message' => self::PENDING_MESSAGE], Response::HTTP_CREATED);
    }

    // Clic sur le lien de confirmation reçu par email : l'abonnement devient actif.
    // Le jeton est à usage unique et expire au bout de 7 jours.
    #[Route('/confirm/{token}', name: 'newsletter_confirm', methods: ['GET'])]
    public function confirm(
        string $token,
        NewsletterRepository $repo,
        EntityManagerInterface $em,
        MailerInterface $mailer,
        LoggerInterface $logger,
        #[Autowire('%env(FRONTEND_URL)%')] string $frontendUrl,
    ): JsonResponse {
        $subscriber = $repo->findByConfirmationToken($token);
        $sentAt = $subscriber?->getConfirmationSentAt();
        if (!$subscriber || !$sentAt || $sentAt < new \DateTimeImmutable('-7 days')) {
            return $this->json(['error' => 'Lien de confirmation invalide ou expiré.'], Response::HTTP_NOT_FOUND);
        }

        $subscriber->setIsActive(true);
        $subscriber->setConfirmedAt(new \DateTimeImmutable());
        $subscriber->setConfirmationToken(null);
        $subscriber->setConfirmationSentAt(null);
        $subscriber->setUnsubscribeToken(bin2hex(random_bytes(32)));
        $em->flush();

        $this->sendWelcomeEmail($mailer, $logger, $subscriber, $frontendUrl);

        return $this->json(['message' => 'Votre inscription à la newsletter est confirmée. Merci !']);
    }

    // Désinscription via le lien "se désinscrire" contenu dans les emails envoyés
    // (le jeton fait office d'authentification implicite, pas besoin d'être connecté)
    #[Route('/unsubscribe/{token}', name: 'newsletter_unsubscribe', methods: ['GET'])]
    public function unsubscribe(
        string $token,
        NewsletterRepository $repo,
        EntityManagerInterface $em,
    ): JsonResponse {
        $subscriber = $repo->findByUnsubscribeToken($token);
        if (!$subscriber) {
            return $this->json(['error' => 'Lien de désabonnement invalide.'], Response::HTTP_NOT_FOUND);
        }

        $subscriber->setIsActive(false);
        $em->flush();

        return $this->json(['message' => 'Vous avez été désabonné de notre newsletter.']);
    }

    private function sendConfirmationRequest(
        MailerInterface $mailer,
        LoggerInterface $logger,
        EntityManagerInterface $em,
        Newsletter $subscriber,
        string $frontendUrl,
    ): void {
        // Pas de nouvel email si un a déjà été envoyé il y a moins de 15 minutes : sinon
        // ce formulaire pourrait servir à harceler une adresse de demandes de confirmation.
        $lastSent = $subscriber->getConfirmationSentAt();
        if ($lastSent && $lastSent > new \DateTimeImmutable('-15 minutes')) {
            return;
        }

        $token = bin2hex(random_bytes(32));
        $subscriber->setConfirmationToken($token);
        $subscriber->setConfirmationSentAt(new \DateTimeImmutable());
        $em->flush();

        try {
            $email = (new Email())
                ->from(new Address('noreply@zonalchd.org', 'Zonal'))
                ->to($subscriber->getEmail())
                ->subject('Confirmez votre inscription à la newsletter – ZONAL')
                ->html($this->renderView('emails/newsletter_confirm_request.html.twig', [
                    'name' => $subscriber->getName(),
                    'confirmUrl' => rtrim($frontendUrl, '/') . '/newsletter/confirm/' . $token,
                ]));

            $mailer->send($email);
        } catch (\Exception $e) {
            // On ne fait pas échouer la demande si l'email ne part pas, mais on le trace
            $logger->error('Échec de l\'envoi de l\'email de confirmation d\'inscription newsletter', [
                'email' => $subscriber->getEmail(),
                'exception' => $e,
            ]);
        }
    }

    // Email de bienvenue, envoyé une fois l'inscription confirmée
    private function sendWelcomeEmail(
        MailerInterface $mailer,
        LoggerInterface $logger,
        Newsletter $subscriber,
        string $frontendUrl,
    ): void {
        try {
            $email = (new Email())
                ->from(new Address('noreply@zonalchd.org', 'Zonal'))
                ->to($subscriber->getEmail())
                ->subject('Bienvenue dans la newsletter – ZONAL')
                ->html($this->renderView('emails/newsletter_confirmation.html.twig', [
                    'name' => $subscriber->getName(),
                    'unsubscribeUrl' => rtrim($frontendUrl, '/') . '/newsletter/unsubscribe/' . $subscriber->getUnsubscribeToken(),
                ]));

            $mailer->send($email);
        } catch (\Exception $e) {
            $logger->error('Échec de l\'envoi de l\'email de bienvenue newsletter', [
                'email' => $subscriber->getEmail(),
                'exception' => $e,
            ]);
        }
    }
}
