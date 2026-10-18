<?php

declare(strict_types=1);

namespace Forumify\Milhq\Repository;

use Forumify\Core\Repository\AbstractRepository;
use Forumify\Milhq\Entity\Record\QualificationRecord;

/**
 * @extends AbstractRepository<QualificationRecord>
 */
class QualificationRecordRepository extends AbstractRepository
{
    public static function getEntityClass(): string
    {
        return QualificationRecord::class;
    }

    public function linkUnlinkedToCourseClasses(): int
    {
        return (int)$this->getEntityManager()->getConnection()->executeStatement(<<<'SQL'
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
}
