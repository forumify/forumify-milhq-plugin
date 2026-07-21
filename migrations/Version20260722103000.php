<?php

declare(strict_types=1);

namespace ForumifyMilhqPluginMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260722103000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'add tier to award records';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE milhq_record_award ADD tier_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE milhq_record_award ADD CONSTRAINT FK_5BB335ADA354F9DC FOREIGN KEY (tier_id) REFERENCES milhq_award_tier (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_5BB335ADA354F9DC ON milhq_record_award (tier_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE milhq_record_award DROP FOREIGN KEY FK_5BB335ADA354F9DC');
        $this->addSql('DROP INDEX IDX_5BB335ADA354F9DC ON milhq_record_award');
        $this->addSql('ALTER TABLE milhq_record_award DROP tier_id');
    }
}
