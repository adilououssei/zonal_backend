<?php

namespace App\Controller;

use App\Entity\Newsletter;
use App\Repository\NewsletterRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;

// Endpoints publics de la newsletter : inscription et désinscription (à ne pas
// confondre avec Admin\NewsletterController qui gère la liste des abonnés côté admin).
#[Route('/api/newsletter')]
class NewsletterController extends AbstractController
{
    #[Route('/subscribe', name: 'newsletter_subscribe', methods: ['POST'])]
    public function subscribe(
        Request $request,
        NewsletterRepository $repo,
        EntityManagerInterface $em,
        MailerInterface $mailer,
        LoggerInterface $logger,
        #[Autowire(service: 'limiter.public_form')] RateLimiterFactory $publicFormLimiter,
        #[Autowire('%env(FRONTEND_URL)%')] string $frontendUrl,
    ): JsonResponse {
        // Limite le nombre d'inscriptions par IP pour éviter le spam de ce formulaire
        if (!$publicFormLimiter->create($request->getClientIp())->consume(1)->isAccepted()) {
            return $this->json(['error' => 'Trop de demandes. Réessayez plus tard.'], Response::HTTP_TOO_MANY_REQUESTS);
        }

        $data = json_decode($request->getContent(), true);
        $email = $data['email'] ?? '';

        if (!filter_var($email, \FILTER_VALIDATE_EMAIL)) {
            return $this->json(['error' => 'Adresse e-mail invalide.'], Response::HTTP_BAD_REQUEST);
        }

        $existing = $repo->findByEmail($email);
        if ($existing) {
            if ($existing->isActive()) {
                return $this->json(['message' => 'Vous êtes déjà abonné à notre newsletter.'], Response::HTTP_OK);
            }
            // L'email existait déjà mais s'était désinscrit : on réactive l'abonnement
            // plutôt que de créer un doublon, avec un nouveau jeton de désinscription
            $existing->setIsActive(true);
            $existing->setUnsubscribeToken(bin2hex(random_bytes(32)));
            if (!empty($data['name'])) {
                $existing->setName($data['name']);
            }
            $em->flush();
            $this->sendConfirmationEmail($mailer, $logger, $existing, $frontendUrl);

            return $this->json(['message' => 'Abonnement réactivé.'], Response::HTTP_OK);
        }

        $subscriber = new Newsletter();
        $subscriber->setEmail($email);
        $subscriber->setName($data['name'] ?? null);
        $subscriber->setIsActive(true);

        $em->persist($subscriber);
        $em->flush();

        $this->sendConfirmationEmail($mailer, $logger, $subscriber, $frontendUrl);

        return $this->json(['message' => 'Merci de vous être abonné à notre newsletter !'], Response::HTTP_CREATED);
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

    private function sendConfirmationEmail(
        MailerInterface $mailer,
        LoggerInterface $logger,
        Newsletter $subscriber,
        string $frontendUrl,
    ): void {
        try {
            $email = (new Email())
                ->from('noreply@zonalong.org')
                ->to($subscriber->getEmail())
                ->subject('Confirmation d\'abonnement à la newsletter – ZONAL')
                ->html($this->renderView('emails/newsletter_confirmation.html.twig', [
                    'name' => $subscriber->getName(),
                    'unsubscribeUrl' => rtrim($frontendUrl, '/') . '/newsletter/unsubscribe/' . $subscriber->getUnsubscribeToken(),
                ]));

            $mailer->send($email);
        } catch (\Exception $e) {
            // On ne fait pas échouer l'abonnement si l'email ne part pas, mais on
            // le trace : avant ce correctif, ces échecs étaient invisibles.
            $logger->error('Échec de l\'envoi de l\'email de confirmation newsletter', [
                'email' => $subscriber->getEmail(),
                'exception' => $e,
            ]);
        }
    }
}
