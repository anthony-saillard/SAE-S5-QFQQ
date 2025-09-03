<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250317231445 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Convert assignment_date from integer to date type';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE assignments ALTER assignment_date TYPE DATE USING to_timestamp(assignment_date)::date');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE assignments ALTER assignment_date TYPE INT USING extract(epoch from assignment_date)::integer');
    }
}