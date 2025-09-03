<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250304182925 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE assignements DROP CONSTRAINT fk_b68e15fe8f0281e8');
        $this->addSql('DROP INDEX idx_b68e15fe8f0281e8');
        $this->addSql('ALTER TABLE assignements RENAME COLUMN id_groups_id TO id_course_types_id');
        $this->addSql('ALTER TABLE assignements ADD CONSTRAINT FK_B68E15FECE589D89 FOREIGN KEY (id_course_types_id) REFERENCES course_types (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_B68E15FECE589D89 ON assignements (id_course_types_id)');
        $this->addSql('ALTER INDEX idx_b835a3398b4c7cd RENAME TO IDX_B835A33985952B33');
        $this->addSql('ALTER TABLE groups DROP CONSTRAINT FK_F06D39708F0281E8');
        $this->addSql('ALTER TABLE groups ADD CONSTRAINT FK_F06D39708F0281E8 FOREIGN KEY (id_groups_id) REFERENCES groups (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE assignements ALTER allocated_hours TYPE DOUBLE PRECISION');

    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER INDEX idx_b835a33985952b33 RENAME TO idx_b835a3398b4c7cd');
        $this->addSql('ALTER TABLE groups DROP CONSTRAINT fk_f06d39708f0281e8');
        $this->addSql('ALTER TABLE groups ADD CONSTRAINT fk_f06d39708f0281e8 FOREIGN KEY (id_groups_id) REFERENCES groups (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE assignements DROP CONSTRAINT FK_B68E15FECE589D89');
        $this->addSql('DROP INDEX IDX_B68E15FECE589D89');
        $this->addSql('ALTER TABLE assignements RENAME COLUMN id_course_types_id TO id_groups_id');
        $this->addSql('ALTER TABLE assignements ADD CONSTRAINT fk_b68e15fe8f0281e8 FOREIGN KEY (id_groups_id) REFERENCES groups (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX idx_b68e15fe8f0281e8 ON assignements (id_groups_id)');
        $this->addSql('ALTER TABLE assignements ALTER allocated_hours TYPE INT');
    }
}
