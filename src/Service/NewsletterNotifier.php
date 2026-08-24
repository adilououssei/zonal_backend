<?php

namespace App\Service;

use App\Repository\NewsletterRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Twig\Environment;

/**
 * Notifie tous les abonnés actifs de la newsletter à chaque publication
 * d'un événement, d'une actualité ou d'un projet.
 *
 * Envoi synchrone, un email par abonné (nécessaire pour un lien de
 * désabonnement personnalisé) : adapté à un volume d'abonnés modeste
 * (jusqu'à quelques centaines). À revoir si la liste grossit beaucoup.
 */
class NewsletterNotifier
{
    public function __construct(
        private readonly NewsletterRepository $newsletterRepository,
        private readonly MailerInterface $mailer,
        private readonly Environment $twig,
        private readonly LoggerInterface $logger,
        #[Autowire('%env(FRONTEND_URL)%')] private readonly string $frontendUrl,
    ) {
    }

    /**
     * @param string $contentType Libellé affiché dans l'email, ex. "Nouvel événement"
     * @param string $path Chemin relatif côté frontend, ex. "/events/12"
     */
    public function notifyNewContent(
        string $contentType,
        string $title,
        ?string $excerpt,
        string $path,
        ?string $coverImage = null,
    ): void {
        $subscribers = $this->newsletterRepository->findActive();
        if (!$subscribers) {
            return;
        }

        $contentUrl = rtrim($this->frontendUrl, '/') . $path;
        $sent = 0;
        $failed = 0;

        foreach ($subscribers as $subscriber) {
            try {
                $email = (new Email())
                    ->from('noreply@zonalong.org')
                    ->to($subscriber->getEmail())
                    ->subject("$contentType : $title – ZONAL")
                    ->html($this->twig->render('emails/newsletter_notification.html.twig', [
                        'contentType' => $contentType,
                        'title' => $title,
                        'excerpt' => $excerpt,
                        'contentUrl' => $contentUrl,
                        'coverImage' => $coverImage,
                        'name' => $subscriber->getName(),
                        'unsubscribeUrl' => rtrim($this->frontendUrl, '/') . '/newsletter/unsubscribe/' . $subscriber->getUnsubscribeToken(),
                    ]));

                $this->mailer->send($email);
                $sent++;
            } catch (\Exception $e) {
                $failed++;
                $this->logger->error('Échec de l\'envoi de la notification newsletter à un abonné', [
                    'email' => $subscriber->getEmail(),
                    'exception' => $e,
                ]);
            }
        }

        $this->logger->info(sprintf(
            'Notification newsletter "%s" envoyée : %d réussi(s), %d échoué(s).',
            $title,
            $sent,
            $failed,
        ));
    }
}
