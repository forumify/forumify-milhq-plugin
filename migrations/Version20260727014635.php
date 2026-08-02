<?php

declare(strict_types=1);

namespace ForumifyMilhqPluginMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;


final class Version20260727014635 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'make abbreviation and paygrade nullable';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE milhq_rank CHANGE abbreviation abbreviation VARCHAR(8) DEFAULT NULL, CHANGE paygrade paygrade VARCHAR(16) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE milhq_rank CHANGE abbreviation abbreviation VARCHAR(8) NOT NULL, CHANGE paygrade paygrade VARCHAR(16) NOT NULL');
    }
}
