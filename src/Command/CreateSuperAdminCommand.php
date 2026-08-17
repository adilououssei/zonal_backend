<?php

namespace App\Command;

use App\Entity\Role;
use App\Entity\User;
use App\Repository\RoleRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[AsCommand(
    name: 'app:create-super-admin',
    description: 'Crée (ou promeut) le compte super administrateur par défaut, sans passer par les fixtures.',
)]
class CreateSuperAdminCommand extends Command
{
    private const ROLE_NAME = 'Super Administrateur';
    private const ROLE_PERMISSIONS = [
        'dashboard' => true, 'events' => true, 'news' => true,
        'projects' => true, 'gallery' => true, 'partners' => true,
        'testimonials' => true, 'documents' => true, 'users' => true,
        'roles' => true, 'settings' => true,
    ];

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UserRepository $userRepository,
        private readonly RoleRepository $roleRepository,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly ValidatorInterface $validator,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('email', null, InputOption::VALUE_REQUIRED, 'Email du super administrateur')
            ->addOption('password', null, InputOption::VALUE_REQUIRED, 'Mot de passe (min. 8 caractères)')
            ->addOption('first-name', null, InputOption::VALUE_REQUIRED, 'Prénom', 'Super')
            ->addOption('last-name', null, InputOption::VALUE_REQUIRED, 'Nom', 'Admin')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Réinitialise le mot de passe / re-promeut le compte s\'il existe déjà')
            ->setHelp(<<<'EOT'
Crée le compte super administrateur par défaut de l'application.

Utilisation interactive (recommandée) :
  <info>php bin/console app:create-super-admin</info>

Utilisation non interactive (scripts de déploiement, CI) :
  <info>php bin/console app:create-super-admin --email=admin@zonalong.org --password="..." --no-interaction</info>

Si un compte existe déjà pour cet email, la commande le promeut super administrateur
sans toucher au mot de passe, sauf si <comment>--force</comment> est fourni.
EOT
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Création du super administrateur');

        $email = $input->getOption('email');
        if (!$email) {
            if (!$input->isInteractive()) {
                $io->error('L\'option --email est requise en mode non interactif.');
                return Command::FAILURE;
            }
            $email = $io->ask('Email du super administrateur', 'admin@zonalong.org');
        }

        if (!filter_var($email, \FILTER_VALIDATE_EMAIL)) {
            $io->error("Adresse email invalide : $email");
            return Command::FAILURE;
        }

        $user = $this->userRepository->findByEmail($email);
        $force = (bool) $input->getOption('force');

        if ($user && \in_array('ROLE_SUPER_ADMIN', $user->getRoles(), true) && !$force) {
            $io->warning("Un super administrateur existe déjà pour \"$email\". Relancez avec --force pour réinitialiser son mot de passe.");
            return Command::SUCCESS;
        }

        $password = $input->getOption('password');
        $needsPassword = !$user || $force;

        if ($needsPassword && !$password) {
            if (!$input->isInteractive()) {
                $io->error('L\'option --password est requise en mode non interactif.');
                return Command::FAILURE;
            }
            $password = $io->askHidden('Mot de passe (min. 8 caractères, saisie masquée)');
            $confirm = $io->askHidden('Confirmez le mot de passe');
            if ($password !== $confirm) {
                $io->error('Les deux mots de passe ne correspondent pas.');
                return Command::FAILURE;
            }
        }

        if ($needsPassword && strlen((string) $password) < 8) {
            $io->error('Le mot de passe doit contenir au moins 8 caractères.');
            return Command::FAILURE;
        }

        $role = $this->roleRepository->findOneBy(['name' => self::ROLE_NAME]);
        if (!$role) {
            $role = new Role();
            $role->setName(self::ROLE_NAME);
            $role->setPermissions(self::ROLE_PERMISSIONS);
            $this->em->persist($role);
            $io->note('Rôle métier "' . self::ROLE_NAME . '" créé (toutes permissions activées).');
        }

        $isNew = $user === null;
        if ($isNew) {
            $user = new User();
            $user->setEmail($email);
            $user->setFirstName($input->getOption('first-name'));
            $user->setLastName($input->getOption('last-name'));
            $user->setIsActive(true);
        }

        $user->setRoles(['ROLE_SUPER_ADMIN', 'ROLE_ADMIN']);
        $user->setRole($role);

        if ($needsPassword) {
            $user->setPassword($this->passwordHasher->hashPassword($user, $password));
        }

        $errors = $this->validator->validate($user);
        if (count($errors) > 0) {
            foreach ($errors as $error) {
                $io->error($error->getMessage());
            }
            return Command::FAILURE;
        }

        if ($isNew) {
            $this->em->persist($user);
        }
        $this->em->flush();

        $io->success(sprintf(
            $isNew ? 'Super administrateur créé : %s' : 'Compte "%s" promu/mis à jour en super administrateur.',
            $email,
        ));

        return Command::SUCCESS;
    }
}
