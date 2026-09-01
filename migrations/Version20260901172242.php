<?php

declare(strict_types=1);

namespace ForumifyMilhqPluginMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260901172242 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'move equipment permissions under organization';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            UPDATE role
            SET permissions = REPLACE(permissions, 'milhq.admin.equipment.', 'milhq.admin.organization.equipment.')
            WHERE permissions LIKE '%milhq.admin.equipment.%'
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            UPDATE role
            SET permissions = REPLACE(permissions, 'milhq.admin.organization.equipment.', 'milhq.admin.equipment.')
            WHERE permissions LIKE '%milhq.admin.organization.equipment.%'
        SQL);
    }
}
