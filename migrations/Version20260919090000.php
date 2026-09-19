<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260919090000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Newsletter : double opt-in (jeton, date d\'envoi et date de confirmation)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE newsletter ADD confirmation_token VARCHAR(64) DEFAULT NULL, ADD confirmation_sent_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', ADD confirmed_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_7E8585C8C05FB297 ON newsletter (confirmation_token)');
        // Les abonnés existants ont été inscrits avant le double opt-in : on les considère confirmés
        $this->addSql('UPDATE newsletter SET confirmed_at = subscribed_at');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_7E8585C8C05FB297 ON newsletter');
        $this->addSql('ALTER TABLE newsletter DROP confirmation_token, DROP confirmation_sent_at, DROP confirmed_at');
    }
}
