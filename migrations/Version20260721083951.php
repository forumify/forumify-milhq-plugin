<?php

declare(strict_types=1);

namespace ForumifyMilhqPluginMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260721083951 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'add award/qualification tiers, tier records, and course achievable-awards/tier-map support';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE milhq_award_tier (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, image VARCHAR(255) DEFAULT NULL, position INT NOT NULL, created_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, parent_id INT NOT NULL, INDEX IDX_AF20D8FC462CE4F5 (position), INDEX IDX_AF20D8FC8B8E8428 (created_at), INDEX IDX_AF20D8FC43625D9F (updated_at), INDEX IDX_AF20D8FC727ACA70 (parent_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE milhq_qualification_tier (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, image VARCHAR(255) DEFAULT NULL, position INT NOT NULL, created_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, parent_id INT NOT NULL, INDEX IDX_F15DD61A462CE4F5 (position), INDEX IDX_F15DD61A8B8E8428 (created_at), INDEX IDX_F15DD61A43625D9F (updated_at), INDEX IDX_F15DD61A727ACA70 (parent_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE milhq_award_tier ADD CONSTRAINT FK_AF20D8FC727ACA70 FOREIGN KEY (parent_id) REFERENCES milhq_award (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE milhq_qualification_tier ADD CONSTRAINT FK_F15DD61A727ACA70 FOREIGN KEY (parent_id) REFERENCES milhq_qualification (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE milhq_award ADD auto_advance_tiers TINYINT NOT NULL');

        $this->addSql('ALTER TABLE milhq_record_qualification ADD tier_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE milhq_record_qualification ADD CONSTRAINT FK_241429D9A354F9DC FOREIGN KEY (tier_id) REFERENCES milhq_qualification_tier (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_241429D9A354F9DC ON milhq_record_qualification (tier_id)');

        $this->addSql('ALTER TABLE milhq_record_award ADD tier_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE milhq_record_award ADD CONSTRAINT FK_5BB335ADA354F9DC FOREIGN KEY (tier_id) REFERENCES milhq_award_tier (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_5BB335ADA354F9DC ON milhq_record_award (tier_id)');

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

        $this->addSql('ALTER TABLE milhq_record_award DROP FOREIGN KEY FK_5BB335ADA354F9DC');
        $this->addSql('DROP INDEX IDX_5BB335ADA354F9DC ON milhq_record_award');
        $this->addSql('ALTER TABLE milhq_record_award DROP tier_id');

        $this->addSql('ALTER TABLE milhq_record_qualification DROP FOREIGN KEY FK_241429D9A354F9DC');
        $this->addSql('DROP INDEX IDX_241429D9A354F9DC ON milhq_record_qualification');
        $this->addSql('ALTER TABLE milhq_record_qualification DROP tier_id');

        $this->addSql('DROP TABLE milhq_award_tier');
        $this->addSql('DROP TABLE milhq_qualification_tier');
        $this->addSql('ALTER TABLE milhq_award DROP auto_advance_tiers');
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
