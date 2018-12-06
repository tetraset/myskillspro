<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170106210238 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE video CHANGE video240 video240 TINYINT(1) NOT NULL, CHANGE video360 video360 TINYINT(1) NOT NULL, CHANGE video480 video480 TINYINT(1) NOT NULL, CHANGE video720 video720 TINYINT(1) NOT NULL, CHANGE video1080 video1080 TINYINT(1) NOT NULL');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE video CHANGE video240 video240 VARCHAR(255) DEFAULT NULL, CHANGE video360 video360 VARCHAR(255) DEFAULT NULL, CHANGE video480 video480 VARCHAR(255) DEFAULT NULL, CHANGE video720 video720 VARCHAR(255) DEFAULT NULL, CHANGE video1080 video1080 VARCHAR(255) DEFAULT NULL');
    }
}
