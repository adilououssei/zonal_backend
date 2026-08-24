<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

// Endpoint générique d'upload de fichiers utilisé par tous les formulaires admin
// (images de couverture, logos, documents PDF...). Le fichier est stocké dans
// public/uploads et l'URL publique est renvoyée pour être enregistrée sur
// l'entité concernée (Event, News, Document, etc.).
#[Route('/api/upload')]
class UploadController extends AbstractController
{
    #[Route('', name: 'api_upload', methods: ['POST'])]
    public function upload(Request $request): JsonResponse
    {
        $file = $request->files->get('file');

        if (!$file instanceof UploadedFile) {
            return $this->json(['error' => 'Aucun fichier envoyé.'], Response::HTTP_BAD_REQUEST);
        }

        // Liste blanche des types de fichiers autorisés (images + documents bureautiques)
        $allowedMimeTypes = [
            'image/jpeg', 'image/png', 'image/webp', 'image/gif',
            'application/pdf',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'application/msword',
            'application/vnd.ms-excel',
            'application/vnd.ms-powerpoint',
        ];

        if (!in_array($file->getMimeType(), $allowedMimeTypes)) {
            return $this->json(['error' => 'Type de fichier non autorisé (jpeg, png, webp, gif, pdf, docx, xlsx, pptx).'], Response::HTTP_BAD_REQUEST);
        }

        // Limite de taille plus stricte pour les images (5 Mo) que pour les documents (15 Mo)
        $maxSize = str_starts_with($file->getMimeType(), 'image/') ? 5 * 1024 * 1024 : 15 * 1024 * 1024;

        if ($file->getSize() > $maxSize) {
            $limit = $maxSize / 1024 / 1024;
            return $this->json(['error' => "Fichier trop volumineux (max {$limit} Mo)."], Response::HTTP_BAD_REQUEST);
        }

        $uploadsDir = $this->getParameter('kernel.project_dir') . '/public/uploads';
        if (!is_dir($uploadsDir)) {
            mkdir($uploadsDir, 0777, true);
        }

        // Nom de fichier unique (date + suffixe aléatoire) pour éviter tout écrasement entre deux uploads
        $extension = $file->guessExtension() ?? 'jpg';
        $filename = sprintf('%s_%s.%s', date('Ymd'), bin2hex(random_bytes(8)), $extension);

        try {
            $file->move($uploadsDir, $filename);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Erreur lors de l\'upload.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return $this->json([
            'url' => $request->getSchemeAndHttpHost() . '/uploads/' . $filename,
        ], Response::HTTP_CREATED);
    }
}
