<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250228074829 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE course_teacher_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE course_teacher (id INT NOT NULL, id_sub_resource_id INT DEFAULT NULL, id_user_id INT DEFAULT NULL, id_groups_id INT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_B835A3398B4C7CD ON course_teacher (id_sub_resource_id)');
        $this->addSql('CREATE INDEX IDX_B835A33979F37AE5 ON course_teacher (id_user_id)');
        $this->addSql('CREATE INDEX IDX_B835A3398F0281E8 ON course_teacher (id_groups_id)');
        $this->addSql('ALTER TABLE course_teacher ADD CONSTRAINT FK_B835A3398B4C7CD FOREIGN KEY (id_sub_resource_id) REFERENCES sub_resources (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE course_teacher ADD CONSTRAINT FK_B835A33979F37AE5 FOREIGN KEY (id_user_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE course_teacher ADD CONSTRAINT FK_B835A3398F0281E8 FOREIGN KEY (id_groups_id) REFERENCES groups (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('DROP SEQUENCE course_teacher_id_seq CASCADE');
        $this->addSql('ALTER TABLE course_teacher DROP CONSTRAINT FK_B835A3398B4C7CD');
        $this->addSql('ALTER TABLE course_teacher DROP CONSTRAINT FK_B835A33979F37AE5');
        $this->addSql('ALTER TABLE course_teacher DROP CONSTRAINT FK_B835A3398F0281E8');
        $this->addSql('DROP TABLE course_teacher');
    }
}
