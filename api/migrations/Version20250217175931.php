<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250217175931 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE groups_formation DROP CONSTRAINT fk_5697fc00f373dcf');
        $this->addSql('ALTER TABLE groups_formation DROP CONSTRAINT fk_5697fc005200282e');
        $this->addSql('DROP TABLE groups_formation');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('CREATE TABLE groups_formation (groups_id INT NOT NULL, formation_id INT NOT NULL, PRIMARY KEY(groups_id, formation_id))');
        $this->addSql('CREATE INDEX idx_5697fc005200282e ON groups_formation (formation_id)');
        $this->addSql('CREATE INDEX idx_5697fc00f373dcf ON groups_formation (groups_id)');
        $this->addSql('ALTER TABLE groups_formation ADD CONSTRAINT fk_5697fc00f373dcf FOREIGN KEY (groups_id) REFERENCES groups (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE groups_formation ADD CONSTRAINT fk_5697fc005200282e FOREIGN KEY (formation_id) REFERENCES formation (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }
}
