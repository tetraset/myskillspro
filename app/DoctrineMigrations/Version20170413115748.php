<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170413115748 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE fos_user_user ADD without_hardsub TINYINT(1) DEFAULT \'0\' NOT NULL');
        $this->addSql('ALTER TABLE video_clip ADD no_hardsub_video_url VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE game ADD without_hardsub TINYINT(1) NOT NULL');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE fos_user_user DROP without_hardsub');
        $this->addSql('ALTER TABLE game DROP without_hardsub');
        $this->addSql('ALTER TABLE video_clip DROP no_hardsub_video_url');
    }
}
