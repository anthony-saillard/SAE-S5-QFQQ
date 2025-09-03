<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250313132131 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE notifications DROP CONSTRAINT fk_6000b0d3df010e7');
        $this->addSql('DROP SEQUENCE assignements_id_seq CASCADE');
        $this->addSql('CREATE SEQUENCE assignments_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE assignments (id INT NOT NULL, id_sub_resources_id INT DEFAULT NULL, id_users_id INT DEFAULT NULL, id_course_types_id INT DEFAULT NULL, allocated_hours DOUBLE PRECISION DEFAULT NULL, assignment_date INT DEFAULT NULL, annotation TEXT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_308A50DD58244918 ON assignments (id_sub_resources_id)');
        $this->addSql('CREATE INDEX IDX_308A50DD376858A8 ON assignments (id_users_id)');
        $this->addSql('CREATE INDEX IDX_308A50DDCE589D89 ON assignments (id_course_types_id)');
        $this->addSql('ALTER TABLE assignments ADD CONSTRAINT FK_308A50DD58244918 FOREIGN KEY (id_sub_resources_id) REFERENCES sub_resources (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE assignments ADD CONSTRAINT FK_308A50DD376858A8 FOREIGN KEY (id_users_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE assignments ADD CONSTRAINT FK_308A50DDCE589D89 FOREIGN KEY (id_course_types_id) REFERENCES course_types (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE assignements DROP CONSTRAINT fk_b68e15fe58244918');
        $this->addSql('ALTER TABLE assignements DROP CONSTRAINT fk_b68e15fe376858a8');
        $this->addSql('ALTER TABLE assignements DROP CONSTRAINT fk_b68e15fece589d89');
        $this->addSql('DROP TABLE assignements');
        $this->addSql('DROP INDEX idx_6000b0d3df010e7');
        $this->addSql('ALTER TABLE notifications RENAME COLUMN id_assignements_id TO id_assignments_id');
        $this->addSql('ALTER TABLE notifications ADD CONSTRAINT FK_6000B0D369A0E90F FOREIGN KEY (id_assignments_id) REFERENCES assignments (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_6000B0D369A0E90F ON notifications (id_assignments_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE notifications DROP CONSTRAINT FK_6000B0D369A0E90F');
        $this->addSql('DROP SEQUENCE assignments_id_seq CASCADE');
        $this->addSql('CREATE SEQUENCE assignements_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE assignements (id INT NOT NULL, id_sub_resources_id INT DEFAULT NULL, id_users_id INT DEFAULT NULL, id_course_types_id INT DEFAULT NULL, allocated_hours DOUBLE PRECISION DEFAULT NULL, assignement_date INT DEFAULT NULL, annotation TEXT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX idx_b68e15fece589d89 ON assignements (id_course_types_id)');
        $this->addSql('CREATE INDEX idx_b68e15fe376858a8 ON assignements (id_users_id)');
        $this->addSql('CREATE INDEX idx_b68e15fe58244918 ON assignements (id_sub_resources_id)');
        $this->addSql('ALTER TABLE assignements ADD CONSTRAINT fk_b68e15fe58244918 FOREIGN KEY (id_sub_resources_id) REFERENCES sub_resources (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE assignements ADD CONSTRAINT fk_b68e15fe376858a8 FOREIGN KEY (id_users_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE assignements ADD CONSTRAINT fk_b68e15fece589d89 FOREIGN KEY (id_course_types_id) REFERENCES course_types (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE assignments DROP CONSTRAINT FK_308A50DD58244918');
        $this->addSql('ALTER TABLE assignments DROP CONSTRAINT FK_308A50DD376858A8');
        $this->addSql('ALTER TABLE assignments DROP CONSTRAINT FK_308A50DDCE589D89');
        $this->addSql('DROP TABLE assignments');
        $this->addSql('DROP INDEX IDX_6000B0D369A0E90F');
        $this->addSql('ALTER TABLE notifications RENAME COLUMN id_assignments_id TO id_assignements_id');
        $this->addSql('ALTER TABLE notifications ADD CONSTRAINT fk_6000b0d3df010e7 FOREIGN KEY (id_assignements_id) REFERENCES assignements (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX idx_6000b0d3df010e7 ON notifications (id_assignements_id)');
    }
}
