<?php

declare(strict_types=1);

namespace ForumifyMilhqPluginMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260724120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'add achievable awards to courses; store class student qualifications/awards as tier maps + notes';
    }

    public function up(Schema $schema): void
    {
        $rows = $this->connection
            ->executeQuery('SELECT id, qualifications, awards FROM milhq_course_class_student')
            ->fetchAllAssociative();

        $this->addSql('ALTER TABLE milhq_course ADD awards LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE milhq_course_class_student ADD notes LONGTEXT DEFAULT NULL, CHANGE service_record_text_override service_record_text_override LONGTEXT DEFAULT NULL');

        foreach ($rows as $row) {
            $this->addSql(
                'UPDATE milhq_course_class_student SET qualifications = ?, awards = ? WHERE id = ?',
                [self::listToTierMap($row['qualifications']), self::listToTierMap($row['awards']), $row['id']],
            );
        }

        $this->addSql('ALTER TABLE milhq_course_class_student CHANGE qualifications qualifications JSON DEFAULT NULL, CHANGE awards awards JSON DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $rows = $this->connection
            ->executeQuery('SELECT id, qualifications, awards FROM milhq_course_class_student')
            ->fetchAllAssociative();

        $this->addSql('ALTER TABLE milhq_course DROP awards');
        $this->addSql('ALTER TABLE milhq_course_class_student CHANGE qualifications qualifications LONGTEXT DEFAULT NULL, CHANGE awards awards LONGTEXT DEFAULT NULL');

        foreach ($rows as $row) {
            $this->addSql(
                'UPDATE milhq_course_class_student SET qualifications = ?, awards = ? WHERE id = ?',
                [self::tierMapToList($row['qualifications']), self::tierMapToList($row['awards']), $row['id']],
            );
        }

        $this->addSql('ALTER TABLE milhq_course_class_student DROP notes, CHANGE service_record_text_override service_record_text_override VARCHAR(255) DEFAULT NULL');
    }

    private static function listToTierMap(?string $list): ?string
    {
        if ($list === null || $list === '') {
            return null;
        }

        $map = [];
        foreach (explode(',', $list) as $id) {
            $map[(string)(int)$id] = null;
        }

        return json_encode($map);
    }

    private static function tierMapToList(?string $json): ?string
    {
        if ($json === null || $json === '') {
            return null;
        }

        /** @var array<int|string, mixed>|null $map */
        $map = json_decode($json, true);
        if (empty($map)) {
            return null;
        }

        return implode(',', array_keys($map));
    }
}
