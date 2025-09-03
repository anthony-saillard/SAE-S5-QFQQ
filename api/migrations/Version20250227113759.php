<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250227113759 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE assignements ALTER assignement_date TYPE INT');
        $this->addSql('DROP INDEX uniq_f06d39708f0281e8');
        $this->addSql('CREATE INDEX IDX_F06D39708F0281E8 ON groups (id_groups_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('DROP INDEX IDX_F06D39708F0281E8');
        $this->addSql('CREATE UNIQUE INDEX uniq_f06d39708f0281e8 ON groups (id_groups_id)');
        $this->addSql('ALTER TABLE assignements ALTER assignement_date TYPE DATE');
    }
}
