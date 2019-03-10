<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170305214115 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE game (id INT AUTO_INCREMENT NOT NULL, id_user INT DEFAULT NULL, id_videoclip INT DEFAULT NULL, game_start DATETIME NOT NULL, time_update DATETIME DEFAULT NULL, game_finish DATETIME DEFAULT NULL, level INT NOT NULL, attempt_number INT NOT NULL, score INT NOT NULL, game_type INT NOT NULL, correct_percent INT NOT NULL, hash VARCHAR(100) NOT NULL, video_watched TINYINT(1) NOT NULL, INDEX IDX_232B318C6B3CA4B (id_user), INDEX IDX_232B318C9AE774BE (id_videoclip), INDEX userGameStart (id_user, game_start), INDEX userVideoClipGameStart (id_user, id_videoclip, game_start), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user_result (id INT AUTO_INCREMENT NOT NULL, id_user INT DEFAULT NULL, id_game INT DEFAULT NULL, id_videoclip INT DEFAULT NULL, time_add DATETIME NOT NULL, sub_text LONGTEXT NOT NULL, correct_percent INT NOT NULL, hash VARCHAR(100) NOT NULL, video_watched TINYINT(1) NOT NULL, INDEX IDX_7638DA2E6B3CA4B (id_user), INDEX IDX_7638DA2EA80B2D8E (id_game), INDEX IDX_7638DA2E9AE774BE (id_videoclip), INDEX userGameStart (id_user, id_game), INDEX userVideoClipGameStart (id_user, id_game, id_videoclip, time_add), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE game ADD CONSTRAINT FK_232B318C6B3CA4B FOREIGN KEY (id_user) REFERENCES fos_user_user (id)');
        $this->addSql('ALTER TABLE game ADD CONSTRAINT FK_232B318C9AE774BE FOREIGN KEY (id_videoclip) REFERENCES video_clip (id)');
        $this->addSql('ALTER TABLE user_result ADD CONSTRAINT FK_7638DA2E6B3CA4B FOREIGN KEY (id_user) REFERENCES fos_user_user (id)');
        $this->addSql('ALTER TABLE user_result ADD CONSTRAINT FK_7638DA2EA80B2D8E FOREIGN KEY (id_game) REFERENCES game (id)');
        $this->addSql('ALTER TABLE user_result ADD CONSTRAINT FK_7638DA2E9AE774BE FOREIGN KEY (id_videoclip) REFERENCES video_clip (id)');
        $this->addSql('DROP INDEX pub_series ON video');
        $this->addSql('ALTER TABLE video_clip ADD id_parent_videoclip INT DEFAULT NULL, ADD hardsub_url VARCHAR(255) DEFAULT NULL, ADD raw_url VARCHAR(255) NOT NULL, ADD symbols_count INT NOT NULL, DROP video_url, DROP short_video_url');
        $this->addSql('ALTER TABLE video_clip ADD CONSTRAINT FK_AAB749BB813B2A6 FOREIGN KEY (id_parent_videoclip) REFERENCES video_clip (id)');
        $this->addSql('CREATE INDEX IDX_AAB749BB813B2A6 ON video_clip (id_parent_videoclip)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE user_result DROP FOREIGN KEY FK_7638DA2EA80B2D8E');
        $this->addSql('DROP TABLE game');
        $this->addSql('DROP TABLE user_result');
        $this->addSql('CREATE INDEX pub_series ON video (is_public, id_series)');
        $this->addSql('ALTER TABLE video_clip DROP FOREIGN KEY FK_AAB749BB813B2A6');
        $this->addSql('DROP INDEX IDX_AAB749BB813B2A6 ON video_clip');
        $this->addSql('ALTER TABLE video_clip ADD short_video_url VARCHAR(255) NOT NULL, DROP id_parent_videoclip, DROP hardsub_url, DROP symbols_count, CHANGE raw_url video_url VARCHAR(255) NOT NULL');
    }
}
