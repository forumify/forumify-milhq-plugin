<?php

declare(strict_types=1);

namespace ForumifyMilhqPluginMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Forumify\Milhq\Entity\Form;

final class Version20260503000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'grant view_submissions and manage_submissions ACL to user role for all existing forms';
    }

    public function up(Schema $schema): void
    {
        $userId = $this->connection->executeQuery('SELECT id FROM role WHERE slug = "user"')->fetchOne();
        if ($userId === false) {
            return;
        }

        $formIds = $this->connection->executeQuery('SELECT id FROM milhq_form')->fetchFirstColumn();
        foreach ($formIds as $formId) {
            foreach (['view_submissions', 'manage_submissions'] as $permission) {
                $this->addSql(
                    'INSERT INTO acl (entity, entity_id, permission) VALUES (?, ?, ?)',
                    [Form::class, $formId, $permission]
                );
                $this->addSql(
                    'INSERT INTO acl_role (acl, role) VALUES (LAST_INSERT_ID(), ?)',
                    [$userId]
                );
            }
        }
    }

    public function down(Schema $schema): void
    {
        $formIds = $this->connection->executeQuery('SELECT id FROM milhq_form')->fetchFirstColumn();
        foreach ($formIds as $formId) {
            foreach (['view_submissions', 'manage_submissions'] as $permission) {
                $aclId = $this->connection->executeQuery(
                    'SELECT id FROM acl WHERE entity = ? AND entity_id = ? AND permission = ?',
                    [Form::class, (string)$formId, $permission]
                )->fetchOne();

                if ($aclId === false) {
                    continue;
                }

                $this->connection->executeStatement('DELETE FROM acl_role WHERE acl = ?', [$aclId]);
                $this->connection->executeStatement('DELETE FROM acl WHERE id = ?', [$aclId]);
            }
        }
    }
}
