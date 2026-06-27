<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/contact')]
class ContactController extends AbstractController
{
    #[Route('', name: 'contact_send', methods: ['POST'])]
    public function send(
        Request $request,
        MailerInterface $mailer,
    ): JsonResponse {
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
                ->from('noreply@zonalong.org')
                ->replyTo($email)
                ->to('zonal.ch@gmail.com')
                ->subject("[Contact ZONAL] $subject")
                ->html($this->renderView('emails/contact.html.twig', [
                    'name' => $name,
                    'email' => $email,
                    'subject' => $subject,
                    'message' => nl2br(htmlspecialchars($message)),
                ]));
            $mailer->send($email);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Erreur lors de l\'envoi du message.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return $this->json(['message' => 'Message envoyé avec succès.']);
    }
}
