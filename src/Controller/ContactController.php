<?php

namespace App\Controller;

use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;

// Traite le formulaire de contact public : envoie un email à l'équipe ZONAL
// avec les infos saisies par le visiteur (le "reply-to" pointe vers l'email
// du visiteur pour pouvoir lui répondre directement).
#[Route('/api/contact')]
class ContactController extends AbstractController
{
    #[Route('', name: 'contact_send', methods: ['POST'])]
    public function send(
        Request $request,
        MailerInterface $mailer,
        LoggerInterface $logger,
        #[Autowire(service: 'limiter.public_form')] RateLimiterFactory $publicFormLimiter,
    ): JsonResponse {
        // Limite le nombre de messages par IP pour éviter le spam de ce formulaire
        if (!$publicFormLimiter->create($request->getClientIp())->consume(1)->isAccepted()) {
            return $this->json(['error' => 'Trop de messages envoyés. Réessayez plus tard.'], Response::HTTP_TOO_MANY_REQUESTS);
        }

        $data = json_decode($request->getContent(), true);
        $name = trim($data['name'] ?? '');
        $email = trim($data['email'] ?? '');
        $subject = trim($data['subject'] ?? '');
        $message = trim($data['message'] ?? '');

        if (!$name || !$email || !$subject || !$message) {
            return $this->json(['error' => 'Tous les champs sont obligatoires.'], Response::HTTP_BAD_REQUEST);
        }

        if (!filter_var($email, \FILTER_VALIDATE_EMAIL)) {
            return $this->json(['error' => 'Adresse e-mail invalide.'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $email = (new Email())
                ->from('noreply@zonalchd.org')
                ->replyTo($email)
                ->to('zonal.ch@gmail.com')
                ->subject("[Contact ZONAL] $subject")
                ->html($this->renderView('emails/contact.html.twig', [
                    'name' => $name,
                    'email' => $email,
                    'subject' => $subject,
                    // htmlspecialchars échappe le HTML saisi par le visiteur (anti-XSS dans l'email),
                    // nl2br conserve les retours à la ligne du message d'origine
                    'message' => nl2br(htmlspecialchars($message)),
                ]));
            $mailer->send($email);
        } catch (\Exception $e) {
            $logger->error('Échec de l\'envoi de l\'email de contact', ['exception' => $e]);
            return $this->json(['error' => 'Erreur lors de l\'envoi du message.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return $this->json(['message' => 'Message envoyé avec succès.']);
    }
}
