<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260627000159 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE event ADD title_en VARCHAR(255) DEFAULT NULL, ADD description_en LONGTEXT DEFAULT NULL, ADD location_en VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE gallery ADD title_en VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE news ADD title_en VARCHAR(255) DEFAULT NULL, ADD excerpt_en LONGTEXT DEFAULT NULL, ADD content_en LONGTEXT DEFAULT NULL, ADD author_en VARCHAR(100) DEFAULT NULL, ADD category_en VARCHAR(100) DEFAULT NULL');
        $this->addSql('ALTER TABLE partner ADD name_en VARCHAR(255) DEFAULT NULL, ADD domain_en VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE project ADD title_en VARCHAR(255) DEFAULT NULL, ADD description_en LONGTEXT DEFAULT NULL, ADD location_en VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE settings ADD org_name_en VARCHAR(255) DEFAULT NULL, ADD slogan_en VARCHAR(500) DEFAULT NULL, ADD description_en LONGTEXT DEFAULT NULL, ADD footer_presentation_en LONGTEXT DEFAULT NULL, ADD copyright_en VARCHAR(500) DEFAULT NULL');
        $this->addSql('ALTER TABLE testimonial ADD author_en VARCHAR(255) DEFAULT NULL, ADD role_en VARCHAR(255) DEFAULT NULL, ADD content_en LONGTEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE `event` DROP title_en, DROP description_en, DROP location_en');
        $this->addSql('ALTER TABLE `gallery` DROP title_en');
        $this->addSql('ALTER TABLE `news` DROP title_en, DROP excerpt_en, DROP content_en, DROP author_en, DROP category_en');
        $this->addSql('ALTER TABLE `partner` DROP name_en, DROP domain_en');
        $this->addSql('ALTER TABLE `project` DROP title_en, DROP description_en, DROP location_en');
        $this->addSql('ALTER TABLE `settings` DROP org_name_en, DROP slogan_en, DROP description_en, DROP footer_presentation_en, DROP copyright_en');
        $this->addSql('ALTER TABLE `testimonial` DROP author_en, DROP role_en, DROP content_en');
    }
}
