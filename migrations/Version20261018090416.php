<?php

declare(strict_types=1);

namespace ForumifyMilhqPluginMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261018090416 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'link qualification records to the course class that created them';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE milhq_record_qualification ADD course_class_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE milhq_record_qualification ADD CONSTRAINT FK_241429D943B46646 FOREIGN KEY (course_class_id) REFERENCES milhq_course_class (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_241429D943B46646 ON milhq_record_qualification (course_class_id)');

        $this->addSql(<<<'SQL'
            UPDATE milhq_record_qualification qr
            SET qr.course_class_id = (
                SELECT c.id
                FROM milhq_course_class_student s
                JOIN milhq_course_class c ON c.id = s.class_id
                WHERE s.soldier_id = qr.soldier_id
                    AND s.result = 'passed'
                    AND c.result = 1
                    AND JSON_CONTAINS_PATH(s.qualifications, 'one', CONCAT('$."', qr.qualification_id, '"'))
                    AND (
                        ABS(TIMESTAMPDIFF(SECOND, c.updated_at, qr.created_at)) <= 60
                        OR EXISTS (
                            SELECT 1
                            FROM milhq_record_service sr
                            WHERE sr.soldier_id = qr.soldier_id
                                AND sr.text = COALESCE(NULLIF(s.service_record_text_override, ''), CONCAT('Graduated ', c.title))
                                AND ABS(TIMESTAMPDIFF(SECOND, sr.created_at, qr.created_at)) <= 60
                        )
                    )
                ORDER BY ABS(TIMESTAMPDIFF(SECOND, c.end, qr.created_at)), c.id
                LIMIT 1
            )
            WHERE qr.course_class_id IS NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE milhq_record_qualification DROP FOREIGN KEY FK_241429D943B46646');
        $this->addSql('DROP INDEX IDX_241429D943B46646 ON milhq_record_qualification');
        $this->addSql('ALTER TABLE milhq_record_qualification DROP course_class_id');
    }
}
