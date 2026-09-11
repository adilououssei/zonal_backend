<?php

namespace App\Security;

use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

// Vote sur les attributs "MODULE_<CLE>" (ex: MODULE_EVENTS, MODULE_NEWS...),
// posés en #[IsGranted(...)] sur les contrôleurs admin. L'accès est accordé si :
// - l'utilisateur est ROLE_SUPER_ADMIN (contourne toujours la matrice, voir
//   Entity/Role.php et AdminLayout.tsx qui appliquent la même règle) ;
// - ou si le rôle métier (User::$role) de l'utilisateur a la permission
//   correspondante à true dans sa matrice de permissions (page "Rôles").
//
// C'est ce qui fait le lien entre la page "Rôles" (qui ne servait jusque-là
// qu'à afficher/masquer les menus côté front) et l'accès réel aux routes
// /api/admin/* : un utilisateur sans la permission du module reçoit un 403,
// même s'il est authentifié.
class ModulePermissionVoter extends Voter
{
    private const PREFIX = 'MODULE_';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return str_starts_with($attribute, self::PREFIX);
    }

    // $vote (ajouté par les versions récentes de symfony/security-core) est typé
    // "mixed" plutôt que la classe Vote pour rester compatible quelle que soit la
    // version exacte installée : on ne s'en sert pas.
    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, mixed $vote = null): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        if (in_array('ROLE_SUPER_ADMIN', $user->getRoles(), true)) {
            return true;
        }

        $module = strtolower(substr($attribute, strlen(self::PREFIX)));
        $role = $user->getRole();

        return $role !== null && ($role->getPermissions()[$module] ?? false) === true;
    }
}
