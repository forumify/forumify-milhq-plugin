<?php

declare(strict_types=1);

namespace ForumifyMilhqPluginMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261004080100 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'add specialty image';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE milhq_specialty ADD image VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE milhq_specialty DROP image');
    }
}
