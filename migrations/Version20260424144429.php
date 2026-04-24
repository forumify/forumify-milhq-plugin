<?php

declare(strict_types=1);

namespace ForumifyMilhqPluginMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260424144429 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'add vehicles to units';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE milhq_unit_vehicles (unit_id INT NOT NULL, equipment_id INT NOT NULL, INDEX IDX_9E748583F8BD700D (unit_id), INDEX IDX_9E748583517FE9FE (equipment_id), PRIMARY KEY (unit_id, equipment_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE milhq_unit_vehicles ADD CONSTRAINT FK_9E748583F8BD700D FOREIGN KEY (unit_id) REFERENCES milhq_unit (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE milhq_unit_vehicles ADD CONSTRAINT FK_9E748583517FE9FE FOREIGN KEY (equipment_id) REFERENCES milhq_equipment (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE milhq_unit_vehicles DROP FOREIGN KEY FK_9E748583F8BD700D');
        $this->addSql('ALTER TABLE milhq_unit_vehicles DROP FOREIGN KEY FK_9E748583517FE9FE');
        $this->addSql('DROP TABLE milhq_unit_vehicles');
    }
}
