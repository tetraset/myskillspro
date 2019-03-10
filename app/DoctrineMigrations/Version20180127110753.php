<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180127110753 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('SET foreign_key_checks = 0');
        $this->addSql('TRUNCATE video');
        $this->addSql('TRUNCATE video_clip');
        $this->addSql('TRUNCATE subtitle_file');
        $this->addSql('TRUNCATE game');
        $this->addSql('SET foreign_key_checks = 1');
        $this->addSql('DROP INDEX series_ep_season ON video');
        $this->addSql('DROP INDEX number ON video');
        $this->addSql('DROP INDEX cutType ON video');
        $this->addSql('ALTER TABLE video ADD youtube_id VARCHAR(255) NOT NULL, ADD description LONGTEXT DEFAULT NULL, ADD time_publish DATETIME NOT NULL, CHANGE cut_type cut_type INT NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX youtube ON video (youtube_id)');
        $this->addSql('ALTER TABLE video_clip ADD youtube_id VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE book ADD poster_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE book ADD CONSTRAINT FK_CBE5A3315BB66C05 FOREIGN KEY (poster_id) REFERENCES media__media (id)');
        $this->addSql('CREATE INDEX IDX_CBE5A3315BB66C05 ON book (poster_id)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE book DROP FOREIGN KEY FK_CBE5A3315BB66C05');
        $this->addSql('DROP INDEX IDX_CBE5A3315BB66C05 ON book');
        $this->addSql('ALTER TABLE book DROP poster_id');
        $this->addSql('DROP INDEX youtube ON video');
        $this->addSql('ALTER TABLE video DROP youtube_id, DROP description, DROP time_publish, CHANGE cut_type cut_type INT DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX series_ep_season ON video (id_series, number, season)');
        $this->addSql('CREATE INDEX number ON video (number)');
        $this->addSql('CREATE INDEX cutType ON video (cut_type)');
        $this->addSql('ALTER TABLE video_clip DROP youtube_id');
    }
}
