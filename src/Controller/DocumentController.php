<?php

namespace App\Controller;

use App\Entity\Document;
use App\Repository\DocumentRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

// API publique (accès libre, voir security.yaml) : liste des documents téléchargeables du site.
#[Route('/api/documents')]
class DocumentController extends AbstractController
{
    #[Route('', name: 'public_documents_list', methods: ['GET'])]
    public function index(DocumentRepository $documentRepository): JsonResponse
    {
        $documents = $documentRepository->findAllOrdered();
        $data = array_map(fn (Document $d) => $this->serialize($d), $documents);
        return $this->json($data);
    }

    private function serialize(Document $document): array
    {
        return [
            'id' => $document->getId(),
            'name' => $document->getName(),
            'type' => $document->getType(),
            'size' => $document->getSize(),
            'file' => $document->getFile(),
            'category' => $document->getCategory(),
            'date' => $document->getDate()?->format('Y-m-d'),
        ];
    }
}
