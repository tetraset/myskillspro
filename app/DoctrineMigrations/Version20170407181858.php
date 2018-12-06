<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170407181858 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE video_free_vtt');
        $this->addSql('DROP TABLE video_vtt');
        $this->addSql('ALTER TABLE video DROP FOREIGN KEY FK_7CC7DA2C15E7B24');
        $this->addSql('ALTER TABLE video DROP FOREIGN KEY FK_7CC7DA2C583ABE3E');
        $this->addSql('ALTER TABLE video DROP FOREIGN KEY FK_7CC7DA2C7BF2A12');
        $this->addSql('DROP INDEX UNIQ_7CC7DA2C7BF2A12 ON video');
        $this->addSql('DROP INDEX UNIQ_7CC7DA2C15E7B24 ON video');
        $this->addSql('DROP INDEX UNIQ_7CC7DA2C583ABE3E ON video');
        $this->addSql('ALTER TABLE video DROP id_subtitle, DROP id_free_subtitle, DROP id_file, DROP sub_lang, DROP is_for_free, CHANGE cut_type cut_type INT DEFAULT NULL');
        $this->addSql('ALTER TABLE series DROP for_free, DROP no_subs, DROP sub_lang, DROP lucktv_url, DROP subtitle_url');
        $this->addSql('UPDATE video SET cut_type = NULL');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE video_free_vtt (video_id INT NOT NULL, vtt_id INT NOT NULL, INDEX IDX_7611E1FE29C1004E (video_id), INDEX IDX_7611E1FE1DB62299 (vtt_id), PRIMARY KEY(video_id, vtt_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE video_vtt (video_id INT NOT NULL, vtt_id INT NOT NULL, INDEX IDX_499F012329C1004E (video_id), INDEX IDX_499F01231DB62299 (vtt_id), PRIMARY KEY(video_id, vtt_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE video_free_vtt ADD CONSTRAINT FK_7611E1FE1DB62299 FOREIGN KEY (vtt_id) REFERENCES subtitle_file (id)');
        $this->addSql('ALTER TABLE video_free_vtt ADD CONSTRAINT FK_7611E1FE29C1004E FOREIGN KEY (video_id) REFERENCES video (id)');
        $this->addSql('ALTER TABLE video_vtt ADD CONSTRAINT FK_499F01231DB62299 FOREIGN KEY (vtt_id) REFERENCES subtitle_file (id)');
        $this->addSql('ALTER TABLE video_vtt ADD CONSTRAINT FK_499F012329C1004E FOREIGN KEY (video_id) REFERENCES video (id)');
        $this->addSql('ALTER TABLE series ADD for_free TINYINT(1) NOT NULL, ADD no_subs TINYINT(1) NOT NULL, ADD sub_lang VARCHAR(2) NOT NULL, ADD lucktv_url VARCHAR(255) DEFAULT NULL, ADD subtitle_url VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE video ADD id_subtitle INT DEFAULT NULL, ADD id_free_subtitle INT DEFAULT NULL, ADD id_file INT DEFAULT NULL, ADD sub_lang VARCHAR(2) NOT NULL, ADD is_for_free TINYINT(1) NOT NULL, CHANGE cut_type cut_type INT NOT NULL');
        $this->addSql('ALTER TABLE video ADD CONSTRAINT FK_7CC7DA2C15E7B24 FOREIGN KEY (id_subtitle) REFERENCES subtitle_file (id)');
        $this->addSql('ALTER TABLE video ADD CONSTRAINT FK_7CC7DA2C583ABE3E FOREIGN KEY (id_free_subtitle) REFERENCES subtitle_file (id)');
        $this->addSql('ALTER TABLE video ADD CONSTRAINT FK_7CC7DA2C7BF2A12 FOREIGN KEY (id_file) REFERENCES video_file (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_7CC7DA2C7BF2A12 ON video (id_file)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_7CC7DA2C15E7B24 ON video (id_subtitle)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_7CC7DA2C583ABE3E ON video (id_free_subtitle)');
    }
}
