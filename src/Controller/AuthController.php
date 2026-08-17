<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Firebase\JWT\JWT;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class AuthController extends AbstractController
{
    private const ALGORITHM = 'HS256';

    #[Route('/api/login', name: 'api_login', methods: ['POST'])]
    public function login(
        Request $request,
        UserRepository $userRepository,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $em,
        #[Autowire(service: 'limiter.login')] RateLimiterFactory $loginLimiter,
    ): JsonResponse {
        $limit = $loginLimiter->create($request->getClientIp())->consume(1);
        if (!$limit->isAccepted()) {
            return $this->json(
                ['error' => 'Trop de tentatives. Réessayez plus tard.'],
                Response::HTTP_TOO_MANY_REQUESTS,
                ['Retry-After' => $limit->getRetryAfter()->getTimestamp() - time()],
            );
        }

        $data = json_decode($request->getContent(), true);

        if (!$data || empty($data['email']) || empty($data['password'])) {
            return $this->json(['error' => 'Email et mot de passe requis.'], Response::HTTP_BAD_REQUEST);
        }

        $user = $userRepository->findByEmail($data['email']);

        if (!$user || !$passwordHasher->isPasswordValid($user, $data['password'])) {
            return $this->json(['error' => 'Identifiants invalides.'], Response::HTTP_UNAUTHORIZED);
        }

        if (!$user->isActive()) {
            return $this->json(['error' => 'Compte désactivé.'], Response::HTTP_FORBIDDEN);
        }

        $user->setLastLoginAt(new \DateTimeImmutable());
        $em->flush();

        $token = $this->generateToken($user);

        $role = $user->getRole();

        return $this->json([
            'token' => $token,
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'roles' => $user->getRoles(),
                'firstName' => $user->getFirstName(),
                'lastName' => $user->getLastName(),
                'avatar' => $user->getAvatar(),
                'roleEntity' => $role ? [
                    'id' => $role->getId(),
                    'name' => $role->getName(),
                    'permissions' => $role->getPermissions(),
                ] : null,
            ],
        ]);
    }

    #[Route('/api/register', name: 'api_register', methods: ['POST'])]
    public function register(
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
        // Un utilisateur qui s'auto-enregistre ne peut jamais s'attribuer de rôle :
        // le rôle de base est toujours ROLE_USER, l'élévation se fait uniquement
        // via le back-office par un ROLE_SUPER_ADMIN (Admin\UserController).
        $user->setRoles(['ROLE_USER']);
        $user->setFirstName($data['firstName'] ?? null);
        $user->setLastName($data['lastName'] ?? null);

        $errors = $validator->validate($user);
        if (count($errors) > 0) {
            return $this->json(['error' => (string) $errors], Response::HTTP_BAD_REQUEST);
        }

        $em->persist($user);
        $em->flush();

        $role = $user->getRole();
        $token = $this->generateToken($user);

        return $this->json([
            'token' => $token,
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'roles' => $user->getRoles(),
                'firstName' => $user->getFirstName(),
                'lastName' => $user->getLastName(),
                'avatar' => $user->getAvatar(),
                'roleEntity' => $role ? [
                    'id' => $role->getId(),
                    'name' => $role->getName(),
                    'permissions' => $role->getPermissions(),
                ] : null,
            ],
        ], Response::HTTP_CREATED);
    }

    #[Route('/api/me', name: 'api_me', methods: ['GET'])]
    public function me(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$user) {
            return $this->json(['error' => 'Non authentifié.'], Response::HTTP_UNAUTHORIZED);
        }

        $role = $user->getRole();

        return $this->json([
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'roles' => $user->getRoles(),
            'firstName' => $user->getFirstName(),
            'lastName' => $user->getLastName(),
            'avatar' => $user->getAvatar(),
            'roleEntity' => $role ? [
                'id' => $role->getId(),
                'name' => $role->getName(),
                'permissions' => $role->getPermissions(),
            ] : null,
        ]);
    }

    private function generateToken(User $user): string
    {
        $now = new \DateTimeImmutable();
        $payload = [
            'iat' => $now->getTimestamp(),
            'exp' => $now->modify('+24 hours')->getTimestamp(),
            'email' => $user->getEmail(),
            'roles' => $user->getRoles(),
        ];

        return JWT::encode($payload, $this->getParameter('jwt_secret'), self::ALGORITHM);
    }
}
