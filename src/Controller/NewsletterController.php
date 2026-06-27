<?php

namespace App\Controller;

use App\Entity\Newsletter;
use App\Repository\NewsletterRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/newsletter')]
class NewsletterController extends AbstractController
{
    #[Route('/subscribe', name: 'newsletter_subscribe', methods: ['POST'])]
    public function subscribe(
        Request $request,
        NewsletterRepository $repo,
        EntityManagerInterface $em,
        MailerInterface $mailer,
    ): JsonResponse {
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
            $existing->setIsActive(true);
            $existing->setUnsubscribeToken(bin2hex(random_bytes(32)));
            if (!empty($data['name'])) {
                $existing->setName($data['name']);
            }
            $em->flush();
            $this->sendConfirmationEmail($mailer, $existing);

            return $this->json(['message' => 'Abonnement réactivé.'], Response::HTTP_OK);
        }

        $subscriber = new Newsletter();
        $subscriber->setEmail($email);
        $subscriber->setName($data['name'] ?? null);
        $subscriber->setIsActive(true);

        $em->persist($subscriber);
        $em->flush();

        $this->sendConfirmationEmail($mailer, $subscriber);

        return $this->json(['message' => 'Merci de vous être abonné à notre newsletter !'], Response::HTTP_CREATED);
    }

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

    private function sendConfirmationEmail(MailerInterface $mailer, Newsletter $subscriber): void
    {
        try {
            $email = (new Email())
                ->from('noreply@zonalong.org')
                ->to($subscriber->getEmail())
                ->subject('Confirmation d\'abonnement à la newsletter – ZONAL')
                ->html($this->renderView('emails/newsletter_confirmation.html.twig', [
                    'name' => $subscriber->getName(),
                    'unsubscribeUrl' => $this->generateUrl('newsletter_unsubscribe', [
                        'token' => $subscriber->getUnsubscribeToken(),
                    ], \Symfony\Component\Routing\Generator\UrlGeneratorInterface::ABSOLUTE_URL),
                ]));

            $mailer->send($email);
        } catch (\Exception) {
            // Silent fail — subscription is saved regardless
        }
    }
}
