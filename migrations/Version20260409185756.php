<?php

declare(strict_types=1);

namespace ForumifyMilhqPluginMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260409185756 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'add equipment';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE milhq_equipment (name VARCHAR(255) NOT NULL, type VARCHAR(255) NOT NULL, id INT AUTO_INCREMENT NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE milhq_equipment');
    }
}
