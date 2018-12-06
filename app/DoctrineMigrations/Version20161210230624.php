<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20161210230624 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE video_free_vtt (video_id INT NOT NULL, vtt_id INT NOT NULL, INDEX IDX_7611E1FE29C1004E (video_id), INDEX IDX_7611E1FE1DB62299 (vtt_id), PRIMARY KEY(video_id, vtt_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE video_free_vtt ADD CONSTRAINT FK_7611E1FE29C1004E FOREIGN KEY (video_id) REFERENCES video (id)');
        $this->addSql('ALTER TABLE video_free_vtt ADD CONSTRAINT FK_7611E1FE1DB62299 FOREIGN KEY (vtt_id) REFERENCES subtitle_file (id)');
        $this->addSql('ALTER TABLE series CHANGE ru_title ru_title VARCHAR(255) DEFAULT NULL, CHANGE en_title en_title VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE genre CHANGE ru_title ru_title VARCHAR(255) DEFAULT NULL, CHANGE en_title en_title VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE tag CHANGE ru_title ru_title VARCHAR(255) DEFAULT NULL, CHANGE en_title en_title VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE country CHANGE ru_title ru_title VARCHAR(255) DEFAULT NULL, CHANGE en_title en_title VARCHAR(255) NOT NULL');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE video_free_vtt');
        $this->addSql('ALTER TABLE country CHANGE ru_title ru_title VARCHAR(255) DEFAULT \'\', CHANGE en_title en_title VARCHAR(255) DEFAULT \'\' NOT NULL');
        $this->addSql('ALTER TABLE genre CHANGE ru_title ru_title VARCHAR(255) DEFAULT \'\', CHANGE en_title en_title VARCHAR(255) DEFAULT \'\' NOT NULL');
        $this->addSql('ALTER TABLE series CHANGE ru_title ru_title VARCHAR(255) DEFAULT \'\', CHANGE en_title en_title VARCHAR(255) DEFAULT \'\' NOT NULL');
        $this->addSql('ALTER TABLE tag CHANGE ru_title ru_title VARCHAR(255) DEFAULT \'\', CHANGE en_title en_title VARCHAR(255) DEFAULT \'\' NOT NULL');
    }
}
