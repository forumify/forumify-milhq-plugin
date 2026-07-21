<?php

declare(strict_types=1);

namespace ForumifyMilhqPluginMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260721083951 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'add award/qualification tiers';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE milhq_award_tier (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, image VARCHAR(255) DEFAULT NULL, position INT NOT NULL, created_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, parent_id INT NOT NULL, INDEX IDX_AF20D8FC462CE4F5 (position), INDEX IDX_AF20D8FC8B8E8428 (created_at), INDEX IDX_AF20D8FC43625D9F (updated_at), INDEX IDX_AF20D8FC727ACA70 (parent_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE milhq_qualification_tier (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, image VARCHAR(255) DEFAULT NULL, position INT NOT NULL, created_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, parent_id INT NOT NULL, INDEX IDX_F15DD61A462CE4F5 (position), INDEX IDX_F15DD61A8B8E8428 (created_at), INDEX IDX_F15DD61A43625D9F (updated_at), INDEX IDX_F15DD61A727ACA70 (parent_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE milhq_award_tier ADD CONSTRAINT FK_AF20D8FC727ACA70 FOREIGN KEY (parent_id) REFERENCES milhq_award (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE milhq_qualification_tier ADD CONSTRAINT FK_F15DD61A727ACA70 FOREIGN KEY (parent_id) REFERENCES milhq_qualification (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE milhq_award ADD auto_advance_tiers TINYINT NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE milhq_award_tier');
        $this->addSql('DROP TABLE milhq_qualification_tier');
        $this->addSql('ALTER TABLE milhq_award DROP auto_advance_tiers');
    }
}
