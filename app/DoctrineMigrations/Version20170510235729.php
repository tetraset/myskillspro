<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170510235729 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE book (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, code VARCHAR(255) NOT NULL, author VARCHAR(255) DEFAULT NULL, genre VARCHAR(255) DEFAULT NULL, level VARCHAR(255) DEFAULT NULL, length VARCHAR(255) DEFAULT NULL, english VARCHAR(255) DEFAULT NULL, poster_url VARCHAR(255) DEFAULT NULL, content LONGTEXT NOT NULL, is_public TINYINT(1) NOT NULL, time_add DATETIME NOT NULL, time_update DATETIME NOT NULL, date_publish DATE DEFAULT NULL, source VARCHAR(255) DEFAULT NULL, INDEX title (is_public), INDEX isPublicDatePublish (is_public, date_publish), INDEX datePublish (date_publish), INDEX author (author), INDEX genre (genre), INDEX level (level), INDEX length (length), INDEX english (english), UNIQUE INDEX code (code), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE book');
    }
}
