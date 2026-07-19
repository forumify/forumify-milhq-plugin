<?php

declare(strict_types=1);

namespace ForumifyMilhqPluginMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260507194430 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'add combined index on assignment records';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE INDEX IDX_9A4CA18AA38C17008CDE5729 ON milhq_record_assignment (soldier_id, type)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IDX_9A4CA18AA38C17008CDE5729 ON milhq_record_assignment');
    }
}
