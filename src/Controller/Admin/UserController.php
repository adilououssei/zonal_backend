<?php

namespace App\Controller\Admin;

use App\Entity\Role;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/admin/users')]
#[IsGranted('ROLE_SUPER_ADMIN')]
class UserController extends AbstractController
{
    private const ASSIGNABLE_ROLES = ['ROLE_USER', 'ROLE_ADMIN', 'ROLE_SUPER_ADMIN'];

    /**
     * Ne garde que les rôles connus de l'application : empêche l'injection
     * d'un rôle arbitraire même par un ROLE_SUPER_ADMIN via un payload malformé.
     */
    private function sanitizeRoles(mixed $roles): array
    {
        if (!is_array($roles)) {
            return ['ROLE_USER'];
        }
        $roles = array_values(array_intersect(self::ASSIGNABLE_ROLES, $roles));
        return $roles ?: ['ROLE_USER'];
    }
    #[Route('', name: 'admin_users_list', methods: ['GET'])]
    public function index(UserRepository $userRepository): JsonResponse
    {
        $users = $userRepository->findAll();
        $data = array_map(fn (User $u) => $this->serializeUser($u), $users);
        return $this->json($data);
    }

    #[Route('/{id}', name: 'admin_users_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(User $user): JsonResponse
    {
        return $this->json($this->serializeUser($user));
    }

    #[Route('', name: 'admin_users_create', methods: ['POST'])]
    public function create(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $em,
        ValidatorInterface $validator,
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        if (!$data || empty($data['email']) || empty($data['password'])) {
            return $this->json(['error' => 'Email et mot de passe requis.'], Response::HTTP_BAD_REQUEST);
        }

        $existing = $em->getRepository(User::class)->findOneBy(['email' => $data['email']]);
        if ($existing) {
            return $this->json(['error' => 'Cet email est déjà utilisé.'], Response::HTTP_CONFLICT);
        }

        $user = new User();
        $user->setEmail($data['email']);
        $user->setPassword($passwordHasher->hashPassword($user, $data['password']));
        $user->setRoles($this->sanitizeRoles($data['roles'] ?? ['ROLE_USER']));
        $user->setFirstName($data['firstName'] ?? null);
        $user->setLastName($data['lastName'] ?? null);
        $user->setPhone($data['phone'] ?? null);
        $user->setIsActive($data['isActive'] ?? true);

        if (!empty($data['roleId'])) {
            $role = $em->getRepository(Role::class)->find($data['roleId']);
            if ($role) $user->setRole($role);
        }

        $errors = $validator->validate($user);
        if (count($errors) > 0) {
            return $this->json(['error' => (string) $errors], Response::HTTP_BAD_REQUEST);
        }

        $em->persist($user);
        $em->flush();

        return $this->json($this->serializeUser($user), Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'admin_users_update', methods: ['PUT'], requirements: ['id' => '\d+'])]
    public function update(
        Request $request,
        User $user,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $em,
        ValidatorInterface $validator,
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return $this->json(['error' => 'Données requises.'], Response::HTTP_BAD_REQUEST);
        }

        if (isset($data['email'])) {
            $existing = $em->getRepository(User::class)->findOneBy(['email' => $data['email']]);
            if ($existing && $existing->getId() !== $user->getId()) {
                return $this->json(['error' => 'Cet email est déjà utilisé.'], Response::HTTP_CONFLICT);
            }
            $user->setEmail($data['email']);
        }

        if (isset($data['password']) && !empty($data['password'])) {
            $user->setPassword($passwordHasher->hashPassword($user, $data['password']));
        }

        if (isset($data['roles'])) $user->setRoles($this->sanitizeRoles($data['roles']));
        if (isset($data['firstName'])) $user->setFirstName($data['firstName']);
        if (isset($data['lastName'])) $user->setLastName($data['lastName']);
        if (isset($data['phone'])) $user->setPhone($data['phone']);
        if (isset($data['isActive'])) $user->setIsActive((bool) $data['isActive']);

        if (isset($data['roleId'])) {
            $role = $data['roleId'] ? $em->getRepository(Role::class)->find($data['roleId']) : null;
            $user->setRole($role);
        }

        $errors = $validator->validate($user);
        if (count($errors) > 0) {
            return $this->json(['error' => (string) $errors], Response::HTTP_BAD_REQUEST);
        }

        $em->flush();

        return $this->json($this->serializeUser($user));
    }

    #[Route('/{id}', name: 'admin_users_delete', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function delete(User $user, EntityManagerInterface $em): JsonResponse
    {
        if ($user->getEmail() === 'admin@zonalong.org') {
            return $this->json(['error' => 'Le super administrateur ne peut pas être supprimé.'], Response::HTTP_FORBIDDEN);
        }

        $em->remove($user);
        $em->flush();

        return $this->json(['message' => 'Utilisateur supprimé.']);
    }

    #[Route('/{id}/status', name: 'admin_users_toggle_status', methods: ['PATCH'], requirements: ['id' => '\d+'])]
    public function toggleStatus(User $user, EntityManagerInterface $em): JsonResponse
    {
        if ($user->getEmail() === 'admin@zonalong.org') {
            return $this->json(['error' => 'Le super administrateur ne peut pas être désactivé.'], Response::HTTP_FORBIDDEN);
        }

        $user->setIsActive(!$user->isActive());
        $em->flush();

        return $this->json($this->serializeUser($user));
    }

    private function serializeUser(User $user): array
    {
        return [
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'firstName' => $user->getFirstName(),
            'lastName' => $user->getLastName(),
            'name' => trim(($user->getFirstName() ?? '') . ' ' . ($user->getLastName() ?? '')),
            'phone' => $user->getPhone(),
            'avatar' => $user->getAvatar(),
            'roles' => $user->getRoles(),
            'role' => $this->formatRoleName($user->getRoles()),
            'roleEntity' => $user->getRole() ? [
                'id' => $user->getRole()->getId(),
                'name' => $user->getRole()->getName(),
                'permissions' => $user->getRole()->getPermissions(),
            ] : null,
            'isActive' => $user->isActive(),
            'status' => $user->isActive() ? 'Actif' : 'Inactif',
            'lastLogin' => $user->getLastLoginAt()?->format('d/m/Y H:i') ?? null,
            'createdAt' => $user->getCreatedAt()?->format('c'),
            'updatedAt' => $user->getUpdatedAt()?->format('c'),
        ];
    }

    private function formatRoleName(array $roles): string
    {
        if (in_array('ROLE_SUPER_ADMIN', $roles)) return 'Super Admin';
        if (in_array('ROLE_ADMIN', $roles)) return 'Administrateur';
        return 'Éditeur';
    }
}
