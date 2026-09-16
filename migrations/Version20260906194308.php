<?php

declare(strict_types=1);

namespace ForumifyMilhqPluginMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260906194308 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'add award group';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE milhq_award_group (title VARCHAR(255) NOT NULL, id INT AUTO_INCREMENT NOT NULL, position INT NOT NULL, created_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, INDEX IDX_D42D2E83462CE4F5 (position), INDEX IDX_D42D2E838B8E8428 (created_at), INDEX IDX_D42D2E8343625D9F (updated_at), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE milhq_award ADD group_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE milhq_award ADD CONSTRAINT FK_9FD23490FE54D947 FOREIGN KEY (group_id) REFERENCES milhq_award_group (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_9FD23490FE54D947 ON milhq_award (group_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE milhq_award_group');
        $this->addSql('ALTER TABLE milhq_award DROP FOREIGN KEY FK_9FD23490FE54D947');
        $this->addSql('DROP INDEX IDX_9FD23490FE54D947 ON milhq_award');
        $this->addSql('ALTER TABLE milhq_award DROP group_id');
    }
}
