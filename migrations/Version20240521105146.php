<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240521105146 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE greeting (id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\', author_user_id CHAR(36) DEFAULT NULL COMMENT \'(DC2Type:guid)\', text VARCHAR(255) NOT NULL, created DATETIME NOT NULL, variant_name VARCHAR(255) NOT NULL, INDEX IDX_46E3A4ABE2544CD6 (author_user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE greeting ADD CONSTRAINT FK_46E3A4ABE2544CD6 FOREIGN KEY (author_user_id) REFERENCES user (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE greeting DROP FOREIGN KEY FK_46E3A4ABE2544CD6');
        $this->addSql('DROP TABLE greeting');
    }
}
