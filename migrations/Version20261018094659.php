<?php

declare(strict_types=1);

namespace ForumifyMilhqPluginMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261018094659 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'make required many-to-one associations non-nullable';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DELETE FROM milhq_course_class WHERE course_id IS NULL');
        $this->addSql('DELETE FROM milhq_form_field WHERE form_id IS NULL');
        $this->addSql('DELETE FROM milhq_mission WHERE operation_id IS NULL');
        $this->addSql('DELETE FROM milhq_record_award WHERE award_id IS NULL');
        $this->addSql('DELETE FROM milhq_record_qualification WHERE qualification_id IS NULL');
        $this->addSql('DELETE FROM milhq_record_rank WHERE rank_id IS NULL');

        $this->addSql('ALTER TABLE milhq_course_class CHANGE course_id course_id INT NOT NULL');
        $this->addSql('ALTER TABLE milhq_form_field CHANGE form_id form_id INT NOT NULL');
        $this->addSql('ALTER TABLE milhq_mission CHANGE operation_id operation_id INT NOT NULL');
        $this->addSql('ALTER TABLE milhq_record_award CHANGE award_id award_id INT NOT NULL');
        $this->addSql('ALTER TABLE milhq_record_qualification CHANGE qualification_id qualification_id INT NOT NULL');
        $this->addSql('ALTER TABLE milhq_record_rank CHANGE rank_id rank_id INT NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE milhq_course_class CHANGE course_id course_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE milhq_form_field CHANGE form_id form_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE milhq_mission CHANGE operation_id operation_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE milhq_record_award CHANGE award_id award_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE milhq_record_qualification CHANGE qualification_id qualification_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE milhq_record_rank CHANGE rank_id rank_id INT DEFAULT NULL');
    }
}
