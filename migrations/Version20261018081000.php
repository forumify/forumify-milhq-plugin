<?php

declare(strict_types=1);

namespace ForumifyMilhqPluginMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261018081000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'move after action report attendance from a JSON column into milhq_after_action_report_attendance';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE milhq_after_action_report_attendance (state VARCHAR(255) NOT NULL, id INT AUTO_INCREMENT NOT NULL, after_action_report_id INT NOT NULL, soldier_id INT NOT NULL, INDEX IDX_F2DBCD13FFAB6233 (after_action_report_id), INDEX IDX_F2DBCD13A38C1700 (soldier_id), UNIQUE INDEX UNIQ_F2DBCD13FFAB6233A38C1700 (after_action_report_id, soldier_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE milhq_after_action_report_attendance ADD CONSTRAINT FK_F2DBCD13FFAB6233 FOREIGN KEY (after_action_report_id) REFERENCES milhq_after_action_report (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE milhq_after_action_report_attendance ADD CONSTRAINT FK_F2DBCD13A38C1700 FOREIGN KEY (soldier_id) REFERENCES milhq_soldier (id) ON DELETE CASCADE');

        $this->addSql(<<<'SQL'
            INSERT INTO milhq_after_action_report_attendance (after_action_report_id, soldier_id, state)
            SELECT aar.id, s.id, MIN(states.state)
            FROM milhq_after_action_report aar
            CROSS JOIN JSON_TABLE(JSON_KEYS(aar.attendance), '$[*]' COLUMNS (state VARCHAR(255) PATH '$')) states
            CROSS JOIN JSON_TABLE(JSON_EXTRACT(aar.attendance, CONCAT('$."', states.state, '"')), '$[*]' COLUMNS (soldier_id INT PATH '$')) soldiers
            JOIN milhq_soldier s ON s.id = soldiers.soldier_id
            GROUP BY aar.id, s.id
        SQL);

        $this->addSql('ALTER TABLE milhq_after_action_report DROP attendance');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE milhq_after_action_report ADD attendance JSON NOT NULL');
        $this->addSql(<<<'SQL'
            UPDATE milhq_after_action_report aar
            SET aar.attendance = (
                SELECT COALESCE(JSON_OBJECTAGG(grouped.state, grouped.soldier_ids), JSON_OBJECT())
                FROM (
                    SELECT a.state, JSON_ARRAYAGG(a.soldier_id) AS soldier_ids
                    FROM milhq_after_action_report_attendance a
                    WHERE a.after_action_report_id = aar.id
                    GROUP BY a.state
                ) grouped
            )
        SQL);

        $this->addSql('ALTER TABLE milhq_after_action_report_attendance DROP FOREIGN KEY FK_F2DBCD13FFAB6233');
        $this->addSql('ALTER TABLE milhq_after_action_report_attendance DROP FOREIGN KEY FK_F2DBCD13A38C1700');
        $this->addSql('DROP TABLE milhq_after_action_report_attendance');
    }
}
