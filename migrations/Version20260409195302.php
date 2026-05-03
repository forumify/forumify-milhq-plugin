<?php

declare(strict_types=1);

namespace ForumifyMilhqPluginMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260409195302 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'add weapons to positions';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE milhq_position_primary_weapons (position_id INT NOT NULL, equipment_id INT NOT NULL, INDEX IDX_2820F32DDD842E46 (position_id), INDEX IDX_2820F32D517FE9FE (equipment_id), PRIMARY KEY (position_id, equipment_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE milhq_position_secondary_weapons (position_id INT NOT NULL, equipment_id INT NOT NULL, INDEX IDX_42C3DB08DD842E46 (position_id), INDEX IDX_42C3DB08517FE9FE (equipment_id), PRIMARY KEY (position_id, equipment_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE milhq_position_primary_weapons ADD CONSTRAINT FK_2820F32DDD842E46 FOREIGN KEY (position_id) REFERENCES milhq_position (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE milhq_position_primary_weapons ADD CONSTRAINT FK_2820F32D517FE9FE FOREIGN KEY (equipment_id) REFERENCES milhq_equipment (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE milhq_position_secondary_weapons ADD CONSTRAINT FK_42C3DB08DD842E46 FOREIGN KEY (position_id) REFERENCES milhq_position (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE milhq_position_secondary_weapons ADD CONSTRAINT FK_42C3DB08517FE9FE FOREIGN KEY (equipment_id) REFERENCES milhq_equipment (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE milhq_position_primary_weapons DROP FOREIGN KEY FK_2820F32DDD842E46');
        $this->addSql('ALTER TABLE milhq_position_primary_weapons DROP FOREIGN KEY FK_2820F32D517FE9FE');
        $this->addSql('ALTER TABLE milhq_position_secondary_weapons DROP FOREIGN KEY FK_42C3DB08DD842E46');
        $this->addSql('ALTER TABLE milhq_position_secondary_weapons DROP FOREIGN KEY FK_42C3DB08517FE9FE');
        $this->addSql('DROP TABLE milhq_position_primary_weapons');
        $this->addSql('DROP TABLE milhq_position_secondary_weapons');
    }
}
