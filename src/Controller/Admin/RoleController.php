<?php

namespace App\Controller\Admin;

use App\Entity\Role;
use App\Repository\RoleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

// Gestion des rôles métier (page "Rôles" de l'admin) : création de rôles
// personnalisés et attribution des permissions par module. Accès piloté par
// la matrice de permissions comme les autres modules (voir Entity/Role et
// ModulePermissionVoter) : le super administrateur y accède toujours, et
// tout autre utilisateur seulement si son rôle métier a la permission
// "roles". À noter : accorder la permission "roles" à un rôle revient à
// autoriser quiconque l'ayant à modifier la matrice de n'importe quel rôle,
// y compris le sien — un choix de confiance assumé par le super
// administrateur qui l'accorde, comme pour toute autre permission.
//
// Important : la liste des modules ci-dessous (permissions()) est la seule
// source de vérité de ce qui est cochable dans la page Rôles. Quand une
// nouvelle fonctionnalité admin est ajoutée, il faut penser à l'ajouter ici
// pour qu'elle apparaisse dans la matrice de permissions - sinon le module
// existera dans le code mais restera invisible sur cette page.
#[Route('/api/admin/roles')]
#[IsGranted('MODULE_ROLES')]
class RoleController extends AbstractController
{
    #[Route('', name: 'admin_roles_list', methods: ['GET'])]
    public function index(RoleRepository $roleRepository): JsonResponse
    {
        $roles = $roleRepository->findAll();
        $data = array_map(fn (Role $r) => $this->serializeRole($r), $roles);
        return $this->json($data);
    }

    #[Route('/{id}', name: 'admin_roles_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(Role $role): JsonResponse
    {
        return $this->json($this->serializeRole($role));
    }

    #[Route('', name: 'admin_roles_create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!$data || empty($data['name'])) {
            return $this->json(['error' => 'Le nom du rôle est requis.'], Response::HTTP_BAD_REQUEST);
        }

        $existing = $em->getRepository(Role::class)->findOneBy(['name' => $data['name']]);
        if ($existing) {
            return $this->json(['error' => 'Ce rôle existe déjà.'], Response::HTTP_CONFLICT);
        }

        $role = new Role();
        $role->setName($data['name']);
        $role->setPermissions($data['permissions'] ?? []);

        $em->persist($role);
        $em->flush();

        return $this->json($this->serializeRole($role), Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'admin_roles_update', methods: ['PUT'], requirements: ['id' => '\d+'])]
    public function update(Request $request, Role $role, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return $this->json(['error' => 'Données requises.'], Response::HTTP_BAD_REQUEST);
        }

        if (isset($data['name'])) {
            $existing = $em->getRepository(Role::class)->findOneBy(['name' => $data['name']]);
            if ($existing && $existing->getId() !== $role->getId()) {
                return $this->json(['error' => 'Ce nom de rôle existe déjà.'], Response::HTTP_CONFLICT);
            }
            $role->setName($data['name']);
        }

        if (isset($data['permissions'])) {
            $role->setPermissions($data['permissions']);
        }

        $em->flush();

        return $this->json($this->serializeRole($role));
    }

    #[Route('/{id}', name: 'admin_roles_delete', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function delete(Role $role, EntityManagerInterface $em): JsonResponse
    {
        $em->remove($role);
        $em->flush();

        return $this->json(['message' => 'Rôle supprimé.']);
    }

    // Liste figée des modules affichables dans la matrice de permissions.
    // Le compte Super Administrateur contourne toujours cette matrice côté front
    // (voir AdminLayout.tsx) : il voit tout, même un module pas encore coché ici.
    #[Route('/permissions', name: 'admin_roles_permissions', methods: ['GET'])]
    public function permissions(): JsonResponse
    {
        $modules = [
            ['key' => 'dashboard', 'label' => 'Tableau de bord', 'icon' => 'LayoutDashboard'],
            ['key' => 'events', 'label' => 'Événements', 'icon' => 'Calendar'],
            ['key' => 'news', 'label' => 'Actualités', 'icon' => 'Newspaper'],
            ['key' => 'projects', 'label' => 'Projets', 'icon' => 'FolderOpen'],
            ['key' => 'gallery', 'label' => 'Galerie', 'icon' => 'ImageIcon'],
            ['key' => 'partners', 'label' => 'Partenaires', 'icon' => 'Handshake'],
            ['key' => 'testimonials', 'label' => 'Témoignages', 'icon' => 'MessageSquare'],
            ['key' => 'documents', 'label' => 'Documents', 'icon' => 'FileText'],
            ['key' => 'newsletter', 'label' => 'Newsletter', 'icon' => 'Mail'],
            ['key' => 'users', 'label' => 'Utilisateurs', 'icon' => 'Users'],
            ['key' => 'roles', 'label' => 'Rôles', 'icon' => 'Shield'],
            ['key' => 'settings', 'label' => 'Paramètres', 'icon' => 'Settings'],
        ];

        return $this->json($modules);
    }

    private function serializeRole(Role $role): array
    {
        return [
            'id' => $role->getId(),
            'name' => $role->getName(),
            'permissions' => $role->getPermissions(),
            'createdAt' => $role->getCreatedAt()?->format('c'),
            'updatedAt' => $role->getUpdatedAt()?->format('c'),
        ];
    }
}
