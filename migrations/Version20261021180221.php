<?php

declare(strict_types=1);

namespace ForumifyMilhqPluginMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Forumify\Milhq\Entity\AwardGroup;

final class Version20261021180221 extends AbstractMigration
{
    private const TABLES = [
        AwardGroup::class => 'milhq_award_group',
    ];

    public function getDescription(): string
    {
        return 'grant view ACL to guest and user roles for all existing award groups';
    }

    public function up(Schema $schema): void
    {
        $roleIds = $this->connection
            ->executeQuery('SELECT id FROM role WHERE slug IN ("guest", "user")')
            ->fetchFirstColumn();
        if (empty($roleIds)) {
            return;
        }

        foreach (self::TABLES as $entity => $table) {
            $existing = $this->connection
                ->executeQuery('SELECT entity_id FROM acl WHERE entity = ? AND permission = ?', [$entity, 'view'])
                ->fetchFirstColumn();
            $ids = $this->connection->executeQuery("SELECT id FROM $table")->fetchFirstColumn();
            foreach (array_diff(array_map('strval', $ids), $existing) as $id) {
                $this->addSql(
                    'INSERT INTO acl (entity, entity_id, permission) VALUES (?, ?, ?)',
                    [$entity, $id, 'view'],
                );
                foreach ($roleIds as $roleId) {
                    $this->addSql('INSERT INTO acl_role (acl, role) VALUES (LAST_INSERT_ID(), ?)', [$roleId]);
                }
            }
        }
    }

    public function down(Schema $schema): void
    {
        foreach (array_keys(self::TABLES) as $entity) {
            $this->addSql(
                'DELETE acl_role FROM acl_role INNER JOIN acl ON acl.id = acl_role.acl WHERE acl.entity = ? AND acl.permission = ?',
                [$entity, 'view'],
            );
            $this->addSql('DELETE FROM acl WHERE entity = ? AND permission = ?', [$entity, 'view']);
        }
    }
}
