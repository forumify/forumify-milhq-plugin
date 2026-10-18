<?php

declare(strict_types=1);

namespace Forumify\Milhq\Repository;

use Forumify\Core\Repository\AbstractRepository;
use Forumify\Milhq\Entity\Record\CombatRecord;

/**
 * @extends AbstractRepository<CombatRecord>
 */
class CombatRecordRepository extends AbstractRepository
{
    public static function getEntityClass(): string
    {
        return CombatRecord::class;
    }

    public function linkUnlinkedToAfterActionReports(): int
    {
        return (int)$this->getEntityManager()->getConnection()->executeStatement(<<<'SQL'
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
}
