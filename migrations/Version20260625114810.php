<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260625114810 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE `settings` (id INT AUTO_INCREMENT NOT NULL, org_name VARCHAR(255) DEFAULT NULL, logo VARCHAR(500) DEFAULT NULL, slogan VARCHAR(500) DEFAULT NULL, description LONGTEXT DEFAULT NULL, email VARCHAR(255) DEFAULT NULL, phone VARCHAR(50) DEFAULT NULL, contact_email VARCHAR(255) DEFAULT NULL, contact_phone VARCHAR(50) DEFAULT NULL, contact_phone_secondary VARCHAR(50) DEFAULT NULL, address VARCHAR(500) DEFAULT NULL, whatsapp VARCHAR(50) DEFAULT NULL, google_maps_iframe LONGTEXT DEFAULT NULL, facebook VARCHAR(500) DEFAULT NULL, linkedin VARCHAR(500) DEFAULT NULL, youtube VARCHAR(500) DEFAULT NULL, whatsapp_url VARCHAR(500) DEFAULT NULL, footer_presentation LONGTEXT DEFAULT NULL, copyright VARCHAR(500) DEFAULT NULL, opening_hours JSON DEFAULT NULL, legal_link VARCHAR(500) DEFAULT NULL, privacy_link VARCHAR(500) DEFAULT NULL, hero_image VARCHAR(500) DEFAULT NULL, about_image VARCHAR(500) DEFAULT NULL, programs_image VARCHAR(500) DEFAULT NULL, events_image VARCHAR(500) DEFAULT NULL, contact_image VARCHAR(500) DEFAULT NULL, meta_title VARCHAR(255) DEFAULT NULL, meta_description LONGTEXT DEFAULT NULL, meta_keywords VARCHAR(500) DEFAULT NULL, og_image VARCHAR(500) DEFAULT NULL, google_analytics_id VARCHAR(100) DEFAULT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE `settings`');
    }
}
