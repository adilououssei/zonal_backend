<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260626073319 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE document ADD created_by_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE document ADD CONSTRAINT FK_D8698A76B03A8386 FOREIGN KEY (created_by_id) REFERENCES `user` (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_D8698A76B03A8386 ON document (created_by_id)');
        $this->addSql('ALTER TABLE event ADD created_by_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE event ADD CONSTRAINT FK_3BAE0AA7B03A8386 FOREIGN KEY (created_by_id) REFERENCES `user` (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_3BAE0AA7B03A8386 ON event (created_by_id)');
        $this->addSql('ALTER TABLE gallery ADD created_by_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE gallery ADD CONSTRAINT FK_472B783AB03A8386 FOREIGN KEY (created_by_id) REFERENCES `user` (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_472B783AB03A8386 ON gallery (created_by_id)');
        $this->addSql('ALTER TABLE news ADD created_by_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE news ADD CONSTRAINT FK_1DD39950B03A8386 FOREIGN KEY (created_by_id) REFERENCES `user` (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_1DD39950B03A8386 ON news (created_by_id)');
        $this->addSql('ALTER TABLE partner ADD created_by_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE partner ADD CONSTRAINT FK_312B3E16B03A8386 FOREIGN KEY (created_by_id) REFERENCES `user` (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_312B3E16B03A8386 ON partner (created_by_id)');
        $this->addSql('ALTER TABLE project ADD created_by_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE project ADD CONSTRAINT FK_2FB3D0EEB03A8386 FOREIGN KEY (created_by_id) REFERENCES `user` (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_2FB3D0EEB03A8386 ON project (created_by_id)');
        $this->addSql('ALTER TABLE testimonial ADD created_by_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE testimonial ADD CONSTRAINT FK_E6BDCDF7B03A8386 FOREIGN KEY (created_by_id) REFERENCES `user` (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_E6BDCDF7B03A8386 ON testimonial (created_by_id)');
        $this->addSql('ALTER TABLE user ADD role_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_8D93D649D60322AC FOREIGN KEY (role_id) REFERENCES `role` (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_8D93D649D60322AC ON user (role_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE `document` DROP FOREIGN KEY FK_D8698A76B03A8386');
        $this->addSql('DROP INDEX IDX_D8698A76B03A8386 ON `document`');
        $this->addSql('ALTER TABLE `document` DROP created_by_id');
        $this->addSql('ALTER TABLE `event` DROP FOREIGN KEY FK_3BAE0AA7B03A8386');
        $this->addSql('DROP INDEX IDX_3BAE0AA7B03A8386 ON `event`');
        $this->addSql('ALTER TABLE `event` DROP created_by_id');
        $this->addSql('ALTER TABLE `gallery` DROP FOREIGN KEY FK_472B783AB03A8386');
        $this->addSql('DROP INDEX IDX_472B783AB03A8386 ON `gallery`');
        $this->addSql('ALTER TABLE `gallery` DROP created_by_id');
        $this->addSql('ALTER TABLE `news` DROP FOREIGN KEY FK_1DD39950B03A8386');
        $this->addSql('DROP INDEX IDX_1DD39950B03A8386 ON `news`');
        $this->addSql('ALTER TABLE `news` DROP created_by_id');
        $this->addSql('ALTER TABLE `partner` DROP FOREIGN KEY FK_312B3E16B03A8386');
        $this->addSql('DROP INDEX IDX_312B3E16B03A8386 ON `partner`');
        $this->addSql('ALTER TABLE `partner` DROP created_by_id');
        $this->addSql('ALTER TABLE `project` DROP FOREIGN KEY FK_2FB3D0EEB03A8386');
        $this->addSql('DROP INDEX IDX_2FB3D0EEB03A8386 ON `project`');
        $this->addSql('ALTER TABLE `project` DROP created_by_id');
        $this->addSql('ALTER TABLE `testimonial` DROP FOREIGN KEY FK_E6BDCDF7B03A8386');
        $this->addSql('DROP INDEX IDX_E6BDCDF7B03A8386 ON `testimonial`');
        $this->addSql('ALTER TABLE `testimonial` DROP created_by_id');
        $this->addSql('ALTER TABLE `user` DROP FOREIGN KEY FK_8D93D649D60322AC');
        $this->addSql('DROP INDEX IDX_8D93D649D60322AC ON `user`');
        $this->addSql('ALTER TABLE `user` DROP role_id');
    }
}
