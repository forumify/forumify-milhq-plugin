<?php

declare(strict_types=1);

namespace ForumifyMilhqPluginMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261018083412 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'link combat records to the after action report that created them';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE milhq_record_combat ADD after_action_report_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE milhq_record_combat ADD CONSTRAINT FK_1B89A30DFFAB6233 FOREIGN KEY (after_action_report_id) REFERENCES milhq_after_action_report (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_1B89A30DFFAB6233 ON milhq_record_combat (after_action_report_id)');

        $this->addSql(<<<'SQL'
            UPDATE milhq_record_combat cr
            SET cr.after_action_report_id = (
                SELECT aar.id
                FROM milhq_after_action_report aar
                JOIN milhq_after_action_report_attendance a ON a.after_action_report_id = aar.id
                JOIN milhq_mission m ON m.id = aar.mission_id
                JOIN milhq_operation o ON o.id = m.operation_id
                WHERE a.soldier_id = cr.soldier_id
                    AND a.state = 'present'
                    AND (
                        ABS(TIMESTAMPDIFF(SECOND, aar.created_at, cr.created_at)) <= 60
                        OR (
                            ABS(TIMESTAMPDIFF(SECOND, aar.created_at, cr.created_at)) <= 86400
                            AND cr.text IN (m.combat_record_text, CONCAT('Operation ', o.title, ': Mission ', m.title))
                        )
                    )
                ORDER BY ABS(TIMESTAMPDIFF(SECOND, aar.created_at, cr.created_at)), aar.id
                LIMIT 1
            )
            WHERE cr.after_action_report_id IS NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE milhq_record_combat DROP FOREIGN KEY FK_1B89A30DFFAB6233');
        $this->addSql('DROP INDEX IDX_1B89A30DFFAB6233 ON milhq_record_combat');
        $this->addSql('ALTER TABLE milhq_record_combat DROP after_action_report_id');
    }
}
