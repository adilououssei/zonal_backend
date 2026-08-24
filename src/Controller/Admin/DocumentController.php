<?php

namespace App\Controller\Admin;

use App\Entity\Document;
use App\Repository\DocumentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

// CRUD admin des documents téléchargeables (le fichier lui-même est envoyé au
// préalable via UploadController, seul son chemin est stocké ici).
#[Route('/api/admin/documents')]
class DocumentController extends AbstractController
{
    #[Route('', name: 'admin_documents_list', methods: ['GET'])]
    public function index(DocumentRepository $documentRepository): JsonResponse
    {
        $documents = $documentRepository->findAllOrdered();
        $data = array_map(fn (Document $d) => $this->serialize($d), $documents);
        return $this->json($data);
    }

    #[Route('/{id}', name: 'admin_documents_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(Document $document): JsonResponse
    {
        return $this->json($this->serialize($document));
    }

    #[Route('', name: 'admin_documents_create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!$data || empty($data['name']) || empty($data['file'])) {
            return $this->json(['error' => 'Le nom et le fichier sont requis.'], Response::HTTP_BAD_REQUEST);
        }

        $document = new Document();
        // Trace quel admin a créé ce document (affiché dans la colonne "Ajouté par")
        $document->setCreatedBy($this->getUser());
        $document->setName($data['name']);
        $document->setType($data['type'] ?? 'PDF');
        $document->setSize($data['size'] ?? null);
        $document->setFile($data['file']);
        $document->setCategory($data['category'] ?? null);
        if (isset($data['date'])) {
            $document->setDate(new \DateTimeImmutable($data['date']));
        }

        $em->persist($document);
        $em->flush();

        return $this->json($this->serialize($document), Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'admin_documents_update', methods: ['PUT'], requirements: ['id' => '\d+'])]
    public function update(Request $request, Document $document, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return $this->json(['error' => 'Données requises.'], Response::HTTP_BAD_REQUEST);
        }

        // Seuls les champs présents dans la requête sont modifiés (mise à jour partielle)
        if (isset($data['name'])) $document->setName($data['name']);
        if (isset($data['type'])) $document->setType($data['type']);
        if (isset($data['size'])) $document->setSize($data['size']);
        if (isset($data['file'])) $document->setFile($data['file']);
        if (isset($data['category'])) $document->setCategory($data['category']);
        if (isset($data['date'])) $document->setDate(new \DateTimeImmutable($data['date']));

        $em->flush();

        return $this->json($this->serialize($document));
    }

    #[Route('/{id}', name: 'admin_documents_delete', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function delete(Document $document, EntityManagerInterface $em): JsonResponse
    {
        $em->remove($document);
        $em->flush();
        return $this->json(['message' => 'Document supprimé.']);
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
            'createdAt' => $document->getCreatedAt()?->format('c'),
            'updatedAt' => $document->getUpdatedAt()?->format('c'),
            'createdBy' => $document->getCreatedBy() ? [
                'id' => $document->getCreatedBy()->getId(),
                'name' => trim(($document->getCreatedBy()->getFirstName() ?? '') . ' ' . ($document->getCreatedBy()->getLastName() ?? '')),
            ] : null,
        ];
    }
}
