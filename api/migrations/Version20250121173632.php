<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250121173632 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DROP SEQUENCE IF EXISTS oauth2_access_token_seq CASCADE');
        $this->addSql('CREATE SEQUENCE oauth2_access_token_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('DROP SEQUENCE IF EXISTS oauth2_refresh_token_seq CASCADE');
        $this->addSql('CREATE SEQUENCE oauth2_refresh_token_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE oauth2_access_token (id INT NOT NULL, user_id INT NOT NULL, token VARCHAR(255) NOT NULL, expires_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_454D96735F37A13B ON oauth2_access_token (token)');
        $this->addSql('CREATE INDEX IDX_454D9673A76ED395 ON oauth2_access_token (user_id)');
        $this->addSql('COMMENT ON COLUMN oauth2_access_token.expires_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE oauth2_refresh_token (id INT NOT NULL, user_id INT NOT NULL, token VARCHAR(255) NOT NULL, expires_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_4DD907325F37A13B ON oauth2_refresh_token (token)');
        $this->addSql('CREATE INDEX IDX_4DD90732A76ED395 ON oauth2_refresh_token (user_id)');
        $this->addSql('COMMENT ON COLUMN oauth2_refresh_token.expires_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE users (id SERIAL NOT NULL, login VARCHAR(255) NOT NULL, password VARCHAR(255) NOT NULL, last_name VARCHAR(255) DEFAULT NULL, first_name VARCHAR(255) DEFAULT NULL, role VARCHAR(255) NOT NULL, phone VARCHAR(50) DEFAULT NULL, email VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('ALTER TABLE oauth2_access_token ADD CONSTRAINT FK_454D9673A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE oauth2_refresh_token ADD CONSTRAINT FK_4DD90732A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('DROP SEQUENCE IF EXISTS users_id_seq CASCADE');
        $this->addSql('CREATE SEQUENCE users_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('ALTER TABLE users ALTER id SET DEFAULT nextval(\'users_id_seq\')');
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE annotations_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE assignements_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE course_types_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE formation_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE groups_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE notifications_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE pedagogical_interruptions_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE resources_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE school_year_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE semesters_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE sub_resources_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE annotations (id INT NOT NULL, id_resources_id INT DEFAULT NULL, description TEXT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_489318058F3D2E8D ON annotations (id_resources_id)');
        $this->addSql('CREATE TABLE assignements (id INT NOT NULL, id_sub_resources_id INT DEFAULT NULL, id_users_id INT DEFAULT NULL, id_groups_id INT DEFAULT NULL, allocated_hours INT DEFAULT NULL, assignement_date INT DEFAULT NULL, annotation TEXT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_B68E15FE58244918 ON assignements (id_sub_resources_id)');
        $this->addSql('CREATE INDEX IDX_B68E15FE376858A8 ON assignements (id_users_id)');
        $this->addSql('CREATE INDEX IDX_B68E15FE8F0281E8 ON assignements (id_groups_id)');
        $this->addSql('CREATE TABLE course_types (id INT NOT NULL, id_school_year_id INT DEFAULT NULL, name VARCHAR(255) DEFAULT NULL, hourly_rate DOUBLE PRECISION DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_B09B8ED8EF887139 ON course_types (id_school_year_id)');
        $this->addSql('CREATE TABLE formation (id INT NOT NULL, id_school_year_id INT DEFAULT NULL, label VARCHAR(255) DEFAULT NULL, order_number INT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_404021BFEF887139 ON formation (id_school_year_id)');
        $this->addSql('CREATE TABLE groups (id INT NOT NULL, id_groups_id INT DEFAULT NULL, id_course_types_id INT DEFAULT NULL, name VARCHAR(255) DEFAULT NULL, description VARCHAR(255) DEFAULT NULL, order_number INT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_F06D39708F0281E8 ON groups (id_groups_id)');
        $this->addSql('CREATE INDEX IDX_F06D3970CE589D89 ON groups (id_course_types_id)');
        $this->addSql('CREATE TABLE groups_formation (groups_id INT NOT NULL, formation_id INT NOT NULL, PRIMARY KEY(groups_id, formation_id))');
        $this->addSql('CREATE INDEX IDX_5697FC00F373DCF ON groups_formation (groups_id)');
        $this->addSql('CREATE INDEX IDX_5697FC005200282E ON groups_formation (formation_id)');
        $this->addSql('CREATE TABLE notifications (id INT NOT NULL, id_annotations_id INT DEFAULT NULL, id_ressources_id INT DEFAULT NULL, id_sub_resources_id INT DEFAULT NULL, id_assignements_id INT DEFAULT NULL, modification_date DATE DEFAULT NULL, status INT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_6000B0D3B6F1E032 ON notifications (id_annotations_id)');
        $this->addSql('CREATE INDEX IDX_6000B0D3821EC943 ON notifications (id_ressources_id)');
        $this->addSql('CREATE INDEX IDX_6000B0D358244918 ON notifications (id_sub_resources_id)');
        $this->addSql('CREATE INDEX IDX_6000B0D3DF010E7 ON notifications (id_assignements_id)');
        $this->addSql('CREATE TABLE pedagogical_interruptions (id INT NOT NULL, id_formation_id INT DEFAULT NULL, name VARCHAR(255) DEFAULT NULL, start_date DATE DEFAULT NULL, end_date DATE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_F1B254D971C15D5C ON pedagogical_interruptions (id_formation_id)');
        $this->addSql('CREATE TABLE resources (id INT NOT NULL, id_semesters_id INT DEFAULT NULL, id_users_id INT DEFAULT NULL, identifier VARCHAR(255) DEFAULT NULL, name VARCHAR(255) DEFAULT NULL, description TEXT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_EF66EBAE19F7CD15 ON resources (id_semesters_id)');
        $this->addSql('CREATE INDEX IDX_EF66EBAE376858A8 ON resources (id_users_id)');
        $this->addSql('CREATE TABLE school_year (id INT NOT NULL, label VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE semesters (id INT NOT NULL, id_formation_id INT DEFAULT NULL, name VARCHAR(255) DEFAULT NULL, start_date DATE DEFAULT NULL, end_date DATE DEFAULT NULL, order_number INT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_C542694071C15D5C ON semesters (id_formation_id)');
        $this->addSql('CREATE TABLE sub_resources (id INT NOT NULL, id_resources_id INT DEFAULT NULL, id_users_id INT DEFAULT NULL, name VARCHAR(255) DEFAULT NULL, total_hours INT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_FD6BAD108F3D2E8D ON sub_resources (id_resources_id)');
        $this->addSql('CREATE INDEX IDX_FD6BAD10376858A8 ON sub_resources (id_users_id)');
        $this->addSql('ALTER TABLE annotations ADD CONSTRAINT FK_489318058F3D2E8D FOREIGN KEY (id_resources_id) REFERENCES resources (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE assignements ADD CONSTRAINT FK_B68E15FE58244918 FOREIGN KEY (id_sub_resources_id) REFERENCES sub_resources (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE assignements ADD CONSTRAINT FK_B68E15FE376858A8 FOREIGN KEY (id_users_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE assignements ADD CONSTRAINT FK_B68E15FE8F0281E8 FOREIGN KEY (id_groups_id) REFERENCES groups (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE course_types ADD CONSTRAINT FK_B09B8ED8EF887139 FOREIGN KEY (id_school_year_id) REFERENCES school_year (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE formation ADD CONSTRAINT FK_404021BFEF887139 FOREIGN KEY (id_school_year_id) REFERENCES school_year (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE groups ADD CONSTRAINT FK_F06D39708F0281E8 FOREIGN KEY (id_groups_id) REFERENCES groups (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE groups ADD CONSTRAINT FK_F06D3970CE589D89 FOREIGN KEY (id_course_types_id) REFERENCES course_types (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE groups_formation ADD CONSTRAINT FK_5697FC00F373DCF FOREIGN KEY (groups_id) REFERENCES groups (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE groups_formation ADD CONSTRAINT FK_5697FC005200282E FOREIGN KEY (formation_id) REFERENCES formation (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE notifications ADD CONSTRAINT FK_6000B0D3B6F1E032 FOREIGN KEY (id_annotations_id) REFERENCES annotations (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE notifications ADD CONSTRAINT FK_6000B0D3821EC943 FOREIGN KEY (id_ressources_id) REFERENCES resources (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE notifications ADD CONSTRAINT FK_6000B0D358244918 FOREIGN KEY (id_sub_resources_id) REFERENCES sub_resources (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE notifications ADD CONSTRAINT FK_6000B0D3DF010E7 FOREIGN KEY (id_assignements_id) REFERENCES assignements (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE pedagogical_interruptions ADD CONSTRAINT FK_F1B254D971C15D5C FOREIGN KEY (id_formation_id) REFERENCES formation (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE resources ADD CONSTRAINT FK_EF66EBAE19F7CD15 FOREIGN KEY (id_semesters_id) REFERENCES semesters (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE resources ADD CONSTRAINT FK_EF66EBAE376858A8 FOREIGN KEY (id_users_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE semesters ADD CONSTRAINT FK_C542694071C15D5C FOREIGN KEY (id_formation_id) REFERENCES formation (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE sub_resources ADD CONSTRAINT FK_FD6BAD108F3D2E8D FOREIGN KEY (id_resources_id) REFERENCES resources (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE sub_resources ADD CONSTRAINT FK_FD6BAD10376858A8 FOREIGN KEY (id_users_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('DROP SEQUENCE annotations_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE assignements_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE course_types_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE formation_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE groups_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE notifications_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE pedagogical_interruptions_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE resources_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE school_year_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE semesters_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE sub_resources_id_seq CASCADE');
        $this->addSql('CREATE SEQUENCE users_id_seq1 INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('ALTER TABLE annotations DROP CONSTRAINT FK_489318058F3D2E8D');
        $this->addSql('ALTER TABLE assignements DROP CONSTRAINT FK_B68E15FE58244918');
        $this->addSql('ALTER TABLE assignements DROP CONSTRAINT FK_B68E15FE376858A8');
        $this->addSql('ALTER TABLE assignements DROP CONSTRAINT FK_B68E15FE8F0281E8');
        $this->addSql('ALTER TABLE course_types DROP CONSTRAINT FK_B09B8ED8EF887139');
        $this->addSql('ALTER TABLE formation DROP CONSTRAINT FK_404021BFEF887139');
        $this->addSql('ALTER TABLE groups DROP CONSTRAINT FK_F06D39708F0281E8');
        $this->addSql('ALTER TABLE groups DROP CONSTRAINT FK_F06D3970CE589D89');
        $this->addSql('ALTER TABLE groups_formation DROP CONSTRAINT FK_5697FC00F373DCF');
        $this->addSql('ALTER TABLE groups_formation DROP CONSTRAINT FK_5697FC005200282E');
        $this->addSql('ALTER TABLE notifications DROP CONSTRAINT FK_6000B0D3B6F1E032');
        $this->addSql('ALTER TABLE notifications DROP CONSTRAINT FK_6000B0D3821EC943');
        $this->addSql('ALTER TABLE notifications DROP CONSTRAINT FK_6000B0D358244918');
        $this->addSql('ALTER TABLE notifications DROP CONSTRAINT FK_6000B0D3DF010E7');
        $this->addSql('ALTER TABLE pedagogical_interruptions DROP CONSTRAINT FK_F1B254D971C15D5C');
        $this->addSql('ALTER TABLE resources DROP CONSTRAINT FK_EF66EBAE19F7CD15');
        $this->addSql('ALTER TABLE resources DROP CONSTRAINT FK_EF66EBAE376858A8');
        $this->addSql('ALTER TABLE semesters DROP CONSTRAINT FK_C542694071C15D5C');
        $this->addSql('ALTER TABLE sub_resources DROP CONSTRAINT FK_FD6BAD108F3D2E8D');
        $this->addSql('ALTER TABLE sub_resources DROP CONSTRAINT FK_FD6BAD10376858A8');
        $this->addSql('DROP TABLE annotations');
        $this->addSql('DROP TABLE assignements');
        $this->addSql('DROP TABLE course_types');
        $this->addSql('DROP TABLE formation');
        $this->addSql('DROP TABLE groups');
        $this->addSql('DROP TABLE groups_formation');
        $this->addSql('DROP TABLE notifications');
        $this->addSql('DROP TABLE pedagogical_interruptions');
        $this->addSql('DROP TABLE resources');
        $this->addSql('DROP TABLE school_year');
        $this->addSql('DROP TABLE semesters');
        $this->addSql('DROP TABLE sub_resources');
    }
}
