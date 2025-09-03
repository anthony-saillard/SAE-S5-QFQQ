<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250321123322 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE annotations ADD id_user_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE annotations ADD created_at DATE NOT NULL');
        $this->addSql('ALTER TABLE annotations ADD CONSTRAINT FK_4893180579F37AE5 FOREIGN KEY (id_user_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_4893180579F37AE5 ON annotations (id_user_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE annotations DROP CONSTRAINT FK_4893180579F37AE5');
        $this->addSql('DROP INDEX IDX_4893180579F37AE5');
        $this->addSql('ALTER TABLE annotations DROP id_user_id');
        $this->addSql('ALTER TABLE annotations DROP created_at');
    }
}
