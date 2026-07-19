<?php

declare(strict_types=1);

namespace ForumifyMilhqPluginMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260624161215 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'add awards to course class student';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE milhq_course_class_student ADD awards LONGTEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE milhq_course_class_student DROP awards');
    }
}
