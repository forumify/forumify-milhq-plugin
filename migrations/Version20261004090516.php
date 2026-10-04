<?php

declare(strict_types=1);

namespace ForumifyMilhqPluginMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261004090516 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'split form statuses from soldier statuses, copying every status to every form';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE milhq_form_status (name VARCHAR(255) NOT NULL, color CHAR(7) NOT NULL, id INT AUTO_INCREMENT NOT NULL, position INT NOT NULL, created_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, form_id INT NOT NULL, INDEX IDX_CF259BC2462CE4F5 (position), INDEX IDX_CF259BC28B8E8428 (created_at), INDEX IDX_CF259BC243625D9F (updated_at), INDEX IDX_CF259BC25FF69B7D (form_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE milhq_form_status ADD CONSTRAINT FK_CF259BC25FF69B7D FOREIGN KEY (form_id) REFERENCES milhq_form (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE milhq_form_status ADD legacy_status_id INT DEFAULT NULL');

        $this->addSql('
            INSERT INTO milhq_form_status (form_id, name, color, position, created_at, updated_at, legacy_status_id)
            SELECT f.id, s.name, s.color, s.position, s.created_at, s.updated_at, s.id
            FROM milhq_form f
            CROSS JOIN milhq_status s
        ');

        $this->addSql('ALTER TABLE milhq_form DROP FOREIGN KEY `FK_1BFC312FA95281A`');
        $this->addSql('ALTER TABLE milhq_form_submission DROP FOREIGN KEY `FK_862B6DF16BF700BD`');

        $this->addSql('
            UPDATE milhq_form f
            LEFT JOIN milhq_form_status fs ON fs.form_id = f.id AND fs.legacy_status_id = f.default_status_id
            SET f.default_status_id = fs.id
        ');
        $this->addSql('
            UPDATE milhq_form_submission s
            LEFT JOIN milhq_form_status fs ON fs.form_id = s.form_id AND fs.legacy_status_id = s.status_id
            SET s.status_id = fs.id
        ');

        $this->addSql('DELETE FROM milhq_form_submission WHERE form_id IS NULL OR soldier_id IS NULL');
        $this->addSql('ALTER TABLE milhq_form_submission CHANGE form_id form_id INT NOT NULL, CHANGE soldier_id soldier_id INT NOT NULL');

        $this->addSql('ALTER TABLE milhq_form ADD CONSTRAINT FK_1BFC312FA95281A FOREIGN KEY (default_status_id) REFERENCES milhq_form_status (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE milhq_form_submission ADD CONSTRAINT FK_862B6DF16BF700BD FOREIGN KEY (status_id) REFERENCES milhq_form_status (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE milhq_form_status DROP legacy_status_id');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE milhq_form DROP FOREIGN KEY FK_1BFC312FA95281A');
        $this->addSql('ALTER TABLE milhq_form_submission DROP FOREIGN KEY FK_862B6DF16BF700BD');

        $this->addSql('
            INSERT INTO milhq_status (name, color, position, created_at, updated_at)
            SELECT fs.name, MIN(fs.color), MIN(fs.position), MIN(fs.created_at), MAX(fs.updated_at)
            FROM milhq_form_status fs
            WHERE NOT EXISTS (SELECT 1 FROM milhq_status s WHERE s.name = fs.name)
            GROUP BY fs.name
        ');
        $this->addSql('
            UPDATE milhq_form f
            LEFT JOIN milhq_form_status fs ON fs.id = f.default_status_id
            LEFT JOIN (SELECT name, MIN(id) AS id FROM milhq_status GROUP BY name) s ON s.name = fs.name
            SET f.default_status_id = s.id
        ');
        $this->addSql('
            UPDATE milhq_form_submission sub
            LEFT JOIN milhq_form_status fs ON fs.id = sub.status_id
            LEFT JOIN (SELECT name, MIN(id) AS id FROM milhq_status GROUP BY name) s ON s.name = fs.name
            SET sub.status_id = s.id
        ');

        $this->addSql('ALTER TABLE milhq_form_submission CHANGE form_id form_id INT DEFAULT NULL, CHANGE soldier_id soldier_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE milhq_form_status DROP FOREIGN KEY FK_CF259BC25FF69B7D');
        $this->addSql('DROP TABLE milhq_form_status');

        $this->addSql('ALTER TABLE milhq_form ADD CONSTRAINT `FK_1BFC312FA95281A` FOREIGN KEY (default_status_id) REFERENCES milhq_status (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE milhq_form_submission ADD CONSTRAINT `FK_862B6DF16BF700BD` FOREIGN KEY (status_id) REFERENCES milhq_status (id) ON DELETE SET NULL');
    }
}
