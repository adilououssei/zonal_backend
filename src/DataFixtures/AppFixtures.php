<?php

namespace App\DataFixtures;

use App\Entity\Event;
use App\Entity\Role;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $admin = new User();
        $admin->setEmail('admin@zonalong.org');
        $admin->setPassword(
            $this->passwordHasher->hashPassword($admin, 'admin123')
        );
        $admin->setRoles(['ROLE_SUPER_ADMIN', 'ROLE_ADMIN']);
        $admin->setFirstName('Super');
        $admin->setLastName('Admin');
        $admin->setPhone('+235 66 00 00 00');
        $admin->setIsActive(true);
        $admin->setLastLoginAt(new \DateTimeImmutable('2024-06-02 08:30:00'));
        $manager->persist($admin);

        $editorUser = new User();
        $editorUser->setEmail('editeur@zonalong.org');
        $editorUser->setPassword(
            $this->passwordHasher->hashPassword($editorUser, 'editor123')
        );
        $editorUser->setRoles(['ROLE_ADMIN']);
        $editorUser->setFirstName('Jean');
        $editorUser->setLastName('Martin');
        $editorUser->setPhone('+235 66 11 23 33');
        $editorUser->setIsActive(true);
        $editorUser->setLastLoginAt(new \DateTimeImmutable('2024-06-01 14:20:00'));
        $manager->persist($editorUser);

        $viewerUser = new User();
        $viewerUser->setEmail('lecteur@zonalong.org');
        $viewerUser->setPassword(
            $this->passwordHasher->hashPassword($viewerUser, 'lecteur123')
        );
        $viewerUser->setRoles(['ROLE_USER']);
        $viewerUser->setFirstName('Aïssata');
        $viewerUser->setLastName('Brahim');
        $viewerUser->setPhone('+235 66 22 33 44');
        $viewerUser->setIsActive(true);
        $manager->persist($viewerUser);

        $superAdminRole = new Role();
        $superAdminRole->setName('Super Administrateur');
        $superAdminRole->setPermissions([
            'dashboard' => true, 'events' => true, 'news' => true,
            'projects' => true, 'gallery' => true, 'partners' => true,
            'testimonials' => true, 'documents' => true, 'users' => true,
            'roles' => true, 'settings' => true,
        ]);
        $manager->persist($superAdminRole);

        $adminRole = new Role();
        $adminRole->setName('Administrateur');
        $adminRole->setPermissions([
            'dashboard' => true, 'events' => true, 'news' => true,
            'projects' => true, 'gallery' => true, 'partners' => true,
            'testimonials' => true, 'documents' => true, 'users' => false,
            'roles' => false, 'settings' => false,
        ]);
        $manager->persist($adminRole);

        $editorRole = new Role();
        $editorRole->setName('Éditeur');
        $editorRole->setPermissions([
            'dashboard' => true, 'events' => false, 'news' => false,
            'projects' => false, 'gallery' => false, 'partners' => false,
            'testimonials' => false, 'documents' => false, 'users' => false,
            'roles' => false, 'settings' => false,
        ]);
        $manager->persist($editorRole);

        $event1 = new Event();
        $event1->setTitle("Journée mondiale de l'environnement");
        $event1->setDescription("Activités de sensibilisation, plantation d'arbres et nettoyage des espaces publics.");
        $event1->setDate(new \DateTimeImmutable('2026-06-05'));
        $event1->setTime('08h00 - 14h00');
        $event1->setLocation("N'Djamena, Tchad");
        $event1->setStatus('À venir');
        $event1->setCoverImage('https://images.unsplash.com/photo-1466692476868-aef1dfb1e735?w=600&h=450&fit=crop');
        $event1->setGallery(['https://images.unsplash.com/photo-1466692476868-aef1dfb1e735?w=400']);
        $manager->persist($event1);

        $event2 = new Event();
        $event2->setTitle('Atelier sur la gouvernance locale');
        $event2->setDescription('Renforcement des capacités des leaders communautaires sur la bonne gouvernance.');
        $event2->setDate(new \DateTimeImmutable('2026-06-12'));
        $event2->setTime('09h00 - 16h00');
        $event2->setLocation('Abéché, Tchad');
        $event2->setStatus('À venir');
        $event2->setCoverImage('https://images.unsplash.com/photo-1577896851231-70ef18881754?w=600&h=450&fit=crop');
        $event2->setGallery([]);
        $manager->persist($event2);

        $event3 = new Event();
        $event3->setTitle('Campagne de reboisement');
        $event3->setDescription('Mission de plantation d\'arbres dans les zones dégradées.');
        $event3->setDate(new \DateTimeImmutable('2026-04-25'));
        $event3->setTime(null);
        $event3->setLocation('Moundou, Tchad');
        $event3->setStatus('Terminé');
        $event3->setCoverImage('https://images.unsplash.com/photo-1542601906990-b4d3fb778b09?w=600&h=450&fit=crop');
        $event3->setGallery([]);
        $manager->persist($event3);

        $manager->flush();
    }
}
