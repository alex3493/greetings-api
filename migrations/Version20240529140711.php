<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240529140711 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE greeting ADD updated_by CHAR(36) DEFAULT NULL COMMENT \'(DC2Type:guid)\', ADD updated DATETIME DEFAULT NULL, CHANGE author_user_id author_user_id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\'');
        $this->addSql('ALTER TABLE greeting ADD CONSTRAINT FK_46E3A4AB16FE72E1 FOREIGN KEY (updated_by) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_46E3A4AB16FE72E1 ON greeting (updated_by)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE greeting DROP FOREIGN KEY FK_46E3A4AB16FE72E1');
        $this->addSql('DROP INDEX IDX_46E3A4AB16FE72E1 ON greeting');
        $this->addSql('ALTER TABLE greeting DROP updated_by, DROP updated, CHANGE author_user_id author_user_id CHAR(36) DEFAULT NULL COMMENT \'(DC2Type:guid)\'');
    }
}
