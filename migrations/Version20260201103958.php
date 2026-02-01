<?php

declare(strict_types=1);

namespace ForumifyMilhqPluginMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260201103958 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'add calendar mappings';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE milhq_course_class ADD calendar_id INT DEFAULT NULL, ADD calendar_event_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE milhq_course_class ADD CONSTRAINT FK_5453A461A40A2C8 FOREIGN KEY (calendar_id) REFERENCES calendar (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE milhq_course_class ADD CONSTRAINT FK_5453A4617495C8E3 FOREIGN KEY (calendar_event_id) REFERENCES calendar_event (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_5453A461A40A2C8 ON milhq_course_class (calendar_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_5453A4617495C8E3 ON milhq_course_class (calendar_event_id)');
        $this->addSql('ALTER TABLE milhq_mission ADD calendar_id INT DEFAULT NULL, ADD calendar_event_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE milhq_mission ADD CONSTRAINT FK_D7BF1FEA40A2C8 FOREIGN KEY (calendar_id) REFERENCES calendar (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE milhq_mission ADD CONSTRAINT FK_D7BF1FE7495C8E3 FOREIGN KEY (calendar_event_id) REFERENCES calendar_event (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_D7BF1FEA40A2C8 ON milhq_mission (calendar_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_D7BF1FE7495C8E3 ON milhq_mission (calendar_event_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE milhq_course_class DROP FOREIGN KEY FK_5453A461A40A2C8');
        $this->addSql('ALTER TABLE milhq_course_class DROP FOREIGN KEY FK_5453A4617495C8E3');
        $this->addSql('DROP INDEX IDX_5453A461A40A2C8 ON milhq_course_class');
        $this->addSql('DROP INDEX UNIQ_5453A4617495C8E3 ON milhq_course_class');
        $this->addSql('ALTER TABLE milhq_course_class DROP calendar_id, DROP calendar_event_id');
        $this->addSql('ALTER TABLE milhq_mission DROP FOREIGN KEY FK_D7BF1FEA40A2C8');
        $this->addSql('ALTER TABLE milhq_mission DROP FOREIGN KEY FK_D7BF1FE7495C8E3');
        $this->addSql('DROP INDEX IDX_D7BF1FEA40A2C8 ON milhq_mission');
        $this->addSql('DROP INDEX UNIQ_D7BF1FE7495C8E3 ON milhq_mission');
        $this->addSql('ALTER TABLE milhq_mission DROP calendar_id, DROP calendar_event_id');
    }
}
