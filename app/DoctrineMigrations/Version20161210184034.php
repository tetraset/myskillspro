<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20161210184034 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE video DROP FOREIGN KEY FK_7CC7DA2CBA2A65F1');
        $this->addSql('ALTER TABLE video_clip DROP FOREIGN KEY FK_AAB749BBBA2A65F1');
        $this->addSql('CREATE TABLE video_vtt (video_id INT NOT NULL, vtt_id INT NOT NULL, INDEX IDX_499F012329C1004E (video_id), INDEX IDX_499F01231DB62299 (vtt_id), PRIMARY KEY(video_id, vtt_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE series_countries (series_id INT NOT NULL, country_id INT NOT NULL, INDEX IDX_CD8A1DA45278319C (series_id), INDEX IDX_CD8A1DA4F92F3E70 (country_id), PRIMARY KEY(series_id, country_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE country (id INT AUTO_INCREMENT NOT NULL, ru_title VARCHAR(255) NOT NULL, en_title VARCHAR(255) DEFAULT NULL, UNIQUE INDEX ru_title (ru_title), UNIQUE INDEX en_title (en_title), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE video_vtt ADD CONSTRAINT FK_499F012329C1004E FOREIGN KEY (video_id) REFERENCES video (id)');
        $this->addSql('ALTER TABLE video_vtt ADD CONSTRAINT FK_499F01231DB62299 FOREIGN KEY (vtt_id) REFERENCES subtitle_file (id)');
        $this->addSql('ALTER TABLE series_countries ADD CONSTRAINT FK_CD8A1DA45278319C FOREIGN KEY (series_id) REFERENCES series (id)');
        $this->addSql('ALTER TABLE series_countries ADD CONSTRAINT FK_CD8A1DA4F92F3E70 FOREIGN KEY (country_id) REFERENCES country (id)');
        $this->addSql('DROP TABLE dictionary_file');
        $this->addSql('DROP INDEX UNIQ_7CC7DA2CBA2A65F1 ON video');
        $this->addSql('ALTER TABLE video ADD is_for_free TINYINT(1) NOT NULL, ADD oro_id INT DEFAULT NULL, ADD season INT DEFAULT NULL, ADD plot LONGTEXT DEFAULT NULL, CHANGE id_dictionary id_free_subtitle INT DEFAULT NULL');
        $this->addSql('ALTER TABLE video ADD CONSTRAINT FK_7CC7DA2C583ABE3E FOREIGN KEY (id_free_subtitle) REFERENCES subtitle_file (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_7CC7DA2C583ABE3E ON video (id_free_subtitle)');
        $this->addSql('DROP INDEX ruTitle ON series');
        $this->addSql('ALTER TABLE series ADD trailer_id VARCHAR(50) DEFAULT NULL, ADD complexity NUMERIC(16, 13) DEFAULT NULL, ADD oro_last_updated DATETIME DEFAULT NULL, ADD oro_last_added DATETIME DEFAULT NULL, ADD popularity INT DEFAULT NULL, ADD rating NUMERIC(3, 1) DEFAULT NULL, ADD poster_url VARCHAR(255) DEFAULT NULL, ADD duration INT DEFAULT NULL');
        $this->addSql('CREATE INDEX enTitle ON series (en_title)');
        $this->addSql('ALTER TABLE subtitle_file ADD lang VARCHAR(4) DEFAULT NULL, CHANGE filename filename VARCHAR(255) DEFAULT NULL');
        $this->addSql('DROP INDEX IDX_AAB749BBBA2A65F1 ON video_clip');
        $this->addSql('ALTER TABLE video_clip DROP id_dictionary');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE series_countries DROP FOREIGN KEY FK_CD8A1DA4F92F3E70');
        $this->addSql('CREATE TABLE dictionary_file (id INT AUTO_INCREMENT NOT NULL, filename LONGTEXT DEFAULT NULL, updated DATETIME DEFAULT NULL, in_bd TINYINT(1) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('DROP TABLE video_vtt');
        $this->addSql('DROP TABLE series_countries');
        $this->addSql('DROP TABLE country');
        $this->addSql('DROP INDEX enTitle ON series');
        $this->addSql('ALTER TABLE series DROP trailer_id, DROP complexity, DROP oro_last_updated, DROP oro_last_added, DROP popularity, DROP rating, DROP poster_url, DROP duration');
        $this->addSql('CREATE INDEX ruTitle ON series (ru_title)');
        $this->addSql('ALTER TABLE subtitle_file DROP lang, CHANGE filename filename LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE video DROP FOREIGN KEY FK_7CC7DA2C583ABE3E');
        $this->addSql('DROP INDEX UNIQ_7CC7DA2C583ABE3E ON video');
        $this->addSql('ALTER TABLE video ADD id_dictionary INT DEFAULT NULL, DROP id_free_subtitle, DROP is_for_free, DROP oro_id, DROP season, DROP plot');
        $this->addSql('ALTER TABLE video ADD CONSTRAINT FK_7CC7DA2CBA2A65F1 FOREIGN KEY (id_dictionary) REFERENCES dictionary_file (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_7CC7DA2CBA2A65F1 ON video (id_dictionary)');
        $this->addSql('ALTER TABLE video_clip ADD id_dictionary INT DEFAULT NULL');
        $this->addSql('ALTER TABLE video_clip ADD CONSTRAINT FK_AAB749BBBA2A65F1 FOREIGN KEY (id_dictionary) REFERENCES dictionary_file (id)');
        $this->addSql('CREATE INDEX IDX_AAB749BBBA2A65F1 ON video_clip (id_dictionary)');
    }
}
