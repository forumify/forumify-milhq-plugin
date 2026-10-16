<?php

declare(strict_types=1);

namespace ForumifyMilhqPluginMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261013182845 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'add qualification group';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE milhq_qualification_group (title VARCHAR(255) NOT NULL, id INT AUTO_INCREMENT NOT NULL, position INT NOT NULL, created_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, INDEX IDX_9D1A14C0462CE4F5 (position), INDEX IDX_9D1A14C08B8E8428 (created_at), INDEX IDX_9D1A14C043625D9F (updated_at), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE milhq_qualification ADD group_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE milhq_qualification ADD CONSTRAINT FK_4396F625FE54D947 FOREIGN KEY (group_id) REFERENCES milhq_qualification_group (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_4396F625FE54D947 ON milhq_qualification (group_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE milhq_qualification_group');
        $this->addSql('ALTER TABLE milhq_qualification DROP FOREIGN KEY FK_4396F625FE54D947');
        $this->addSql('DROP INDEX IDX_4396F625FE54D947 ON milhq_qualification');
        $this->addSql('ALTER TABLE milhq_qualification DROP group_id');
    }
}
