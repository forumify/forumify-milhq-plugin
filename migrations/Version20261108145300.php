<?php

declare(strict_types=1);

namespace ForumifyMilhqPluginMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261108145300 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'add time in grade and time in service freeze flags to statuses';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE milhq_status ADD freeze_time_in_grade TINYINT NOT NULL, ADD freeze_time_in_service TINYINT NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE milhq_status DROP freeze_time_in_grade, DROP freeze_time_in_service');
    }
}
