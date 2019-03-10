<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170325163615 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP INDEX isReadyForTimeSubtitle ON video');
        $this->addSql('ALTER TABLE video ADD cut_type INT NOT NULL, DROP is_ready_for_time_subtitle');
        $this->addSql('CREATE INDEX cutType ON video (cut_type)');
        $this->addSql('ALTER TABLE video_clip ADD yandex_disk_number INT DEFAULT NULL, DROP hardsub_url, DROP raw_url');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP INDEX cutType ON video');
        $this->addSql('ALTER TABLE video ADD is_ready_for_time_subtitle TINYINT(1) NOT NULL, DROP cut_type');
        $this->addSql('CREATE INDEX isReadyForTimeSubtitle ON video (is_ready_for_time_subtitle)');
        $this->addSql('ALTER TABLE video_clip ADD hardsub_url VARCHAR(255) DEFAULT NULL, ADD raw_url VARCHAR(255) NOT NULL, DROP yandex_disk_number');
    }
}
