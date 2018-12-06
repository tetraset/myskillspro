<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170413090321 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE video_clip CHANGE flesch_kincaid_reading_ease flesch_kincaid_reading_ease NUMERIC(6, 3) NOT NULL, CHANGE gunning_fog_score gunning_fog_score NUMERIC(6, 3) NOT NULL');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE video_clip CHANGE flesch_kincaid_reading_ease flesch_kincaid_reading_ease NUMERIC(16, 13) NOT NULL, CHANGE gunning_fog_score gunning_fog_score NUMERIC(16, 13) NOT NULL');
    }
}
