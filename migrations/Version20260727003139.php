<?php

declare(strict_types=1);

namespace ForumifyMilhqPluginMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260727003139 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'add rank group';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE milhq_rank_group (title VARCHAR(255) NOT NULL, id INT AUTO_INCREMENT NOT NULL, position INT NOT NULL, created_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, INDEX IDX_EDC34667462CE4F5 (position), INDEX IDX_EDC346678B8E8428 (created_at), INDEX IDX_EDC3466743625D9F (updated_at), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE milhq_rank ADD group_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE milhq_rank ADD CONSTRAINT FK_DB4ED6B8FE54D947 FOREIGN KEY (group_id) REFERENCES milhq_rank_group (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_DB4ED6B8FE54D947 ON milhq_rank (group_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE milhq_rank_group');
        $this->addSql('ALTER TABLE milhq_rank DROP FOREIGN KEY FK_DB4ED6B8FE54D947');
        $this->addSql('DROP INDEX IDX_DB4ED6B8FE54D947 ON milhq_rank');
        $this->addSql('ALTER TABLE milhq_rank DROP group_id');
    }
}
