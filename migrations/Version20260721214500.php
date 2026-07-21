<?php

declare(strict_types=1);

namespace ForumifyMilhqPluginMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260721214500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'add tier to qualification records';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE milhq_record_qualification ADD tier_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE milhq_record_qualification ADD CONSTRAINT FK_241429D9A354F9DC FOREIGN KEY (tier_id) REFERENCES milhq_qualification_tier (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_241429D9A354F9DC ON milhq_record_qualification (tier_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE milhq_record_qualification DROP FOREIGN KEY FK_241429D9A354F9DC');
        $this->addSql('DROP INDEX IDX_241429D9A354F9DC ON milhq_record_qualification');
        $this->addSql('ALTER TABLE milhq_record_qualification DROP tier_id');
    }
}
