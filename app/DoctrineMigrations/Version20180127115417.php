<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180127115417 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE video DROP FOREIGN KEY FK_7CC7DA2C1C27B4C0');
        $this->addSql('DROP INDEX IDX_7CC7DA2C1C27B4C0 ON video');
        $this->addSql('ALTER TABLE video DROP id_series, DROP video240, DROP video360, DROP video480, DROP video720, DROP video1080, DROP user_login, DROP is_public, DROP time_update, DROP hash, DROP number, DROP static_server, DROP oro_id, DROP season, DROP plot, DROP is_downloaded, DROP yandex_disk_number');
        $this->addSql('ALTER TABLE video_clip DROP FOREIGN KEY FK_AAB749BB1C27B4C0');
        $this->addSql('DROP INDEX IDX_AAB749BB1C27B4C0 ON video_clip');
        $this->addSql('ALTER TABLE video_clip DROP id_series, DROP static_server, DROP yandex_disk_number, DROP video_url, DROP no_hardsub_video_url');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE video ADD id_series INT DEFAULT NULL, ADD video240 TINYINT(1) NOT NULL, ADD video360 TINYINT(1) NOT NULL, ADD video480 TINYINT(1) NOT NULL, ADD video720 TINYINT(1) NOT NULL, ADD video1080 TINYINT(1) NOT NULL, ADD user_login VARCHAR(255) DEFAULT NULL, ADD is_public TINYINT(1) NOT NULL, ADD time_update DATETIME NOT NULL, ADD hash VARCHAR(50) NOT NULL, ADD number INT NOT NULL, ADD static_server VARCHAR(50) NOT NULL, ADD oro_id INT DEFAULT NULL, ADD season INT DEFAULT NULL, ADD plot LONGTEXT DEFAULT NULL, ADD is_downloaded TINYINT(1) NOT NULL, ADD yandex_disk_number INT DEFAULT NULL');
        $this->addSql('ALTER TABLE video ADD CONSTRAINT FK_7CC7DA2C1C27B4C0 FOREIGN KEY (id_series) REFERENCES series (id)');
        $this->addSql('CREATE INDEX IDX_7CC7DA2C1C27B4C0 ON video (id_series)');
        $this->addSql('ALTER TABLE video_clip ADD id_series INT DEFAULT NULL, ADD static_server VARCHAR(50) NOT NULL, ADD yandex_disk_number INT DEFAULT NULL, ADD video_url VARCHAR(255) NOT NULL, ADD no_hardsub_video_url VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE video_clip ADD CONSTRAINT FK_AAB749BB1C27B4C0 FOREIGN KEY (id_series) REFERENCES series (id)');
        $this->addSql('CREATE INDEX IDX_AAB749BB1C27B4C0 ON video_clip (id_series)');
    }
}
