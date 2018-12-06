<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20181206155628 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE series_countries DROP FOREIGN KEY FK_CD8A1DA4F92F3E70');
        $this->addSql('ALTER TABLE series_countries DROP FOREIGN KEY FK_CD8A1DA45278319C');
        $this->addSql('ALTER TABLE series_genres DROP FOREIGN KEY FK_CB98062B5278319C');
        $this->addSql('ALTER TABLE series_tags DROP FOREIGN KEY FK_8AFB15E45278319C');
        $this->addSql('DROP TABLE country');
        $this->addSql('DROP TABLE payment');
        $this->addSql('DROP TABLE promo');
        $this->addSql('DROP TABLE series');
        $this->addSql('DROP TABLE series_countries');
        $this->addSql('DROP TABLE series_genres');
        $this->addSql('DROP TABLE series_tags');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE country (id INT AUTO_INCREMENT NOT NULL, ru_title VARCHAR(255) DEFAULT NULL, en_title VARCHAR(255) NOT NULL, UNIQUE INDEX en_title (en_title), UNIQUE INDEX ru_title (ru_title), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE payment (id INT AUTO_INCREMENT NOT NULL, id_user INT NOT NULL, id_promo INT DEFAULT NULL, time_add DATETIME NOT NULL, operation_ps VARCHAR(255) DEFAULT NULL, m_operation_date DATETIME DEFAULT NULL, m_operation_pay_date DATETIME DEFAULT NULL, m_orderid VARCHAR(255) NOT NULL, m_amount NUMERIC(10, 0) NOT NULL, m_curr VARCHAR(3) NOT NULL, m_desc VARCHAR(255) NOT NULL, m_status VARCHAR(255) NOT NULL, subscription_term INT DEFAULT NULL, test TINYINT(1) NOT NULL, INDEX timeAdd (time_add), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE promo (id INT AUTO_INCREMENT NOT NULL, code VARCHAR(255) NOT NULL, is_payment TINYINT(1) NOT NULL, id_user INT NOT NULL, time_add DATETIME NOT NULL, time_update DATETIME NOT NULL, is_active TINYINT(1) NOT NULL, comment VARCHAR(255) DEFAULT NULL, discount_percent INT NOT NULL, UNIQUE INDEX code (code), INDEX timeAdd (time_add), INDEX idUserIsDeleted (id_user, is_active), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE series (id INT AUTO_INCREMENT NOT NULL, poster_id INT DEFAULT NULL, code VARCHAR(255) NOT NULL, ru_title VARCHAR(255) DEFAULT NULL, en_title VARCHAR(255) NOT NULL, en_description LONGTEXT DEFAULT NULL, ru_description LONGTEXT DEFAULT NULL, id_user INT DEFAULT NULL, is_public TINYINT(1) DEFAULT NULL, time_add DATETIME NOT NULL, time_update DATETIME NOT NULL, date_publish DATE DEFAULT NULL, votes_cnt INT NOT NULL, episodes_cnt INT NOT NULL, start_year INT DEFAULT NULL, finish_year INT DEFAULT NULL, type VARCHAR(255) NOT NULL, number INT NOT NULL, trailer_id VARCHAR(50) DEFAULT NULL, complexity NUMERIC(16, 13) DEFAULT NULL, oro_last_updated DATETIME DEFAULT NULL, oro_last_added DATETIME DEFAULT NULL, popularity INT DEFAULT NULL, rating NUMERIC(3, 1) DEFAULT NULL, poster_url VARCHAR(255) DEFAULT NULL, duration INT DEFAULT NULL, big_poster_url VARCHAR(255) DEFAULT NULL, UNIQUE INDEX code (code), INDEX IDX_3A10012D5BB66C05 (poster_id), INDEX isPublic (is_public), INDEX isPublicDateUpdated (is_public, time_update), INDEX datePublish (date_publish), INDEX startYear (start_year), INDEX type (type), INDEX enTitle (en_title), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE series_countries (series_id INT NOT NULL, country_id INT NOT NULL, INDEX IDX_CD8A1DA45278319C (series_id), INDEX IDX_CD8A1DA4F92F3E70 (country_id), PRIMARY KEY(series_id, country_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE series_genres (series_id INT NOT NULL, genre_id INT NOT NULL, INDEX IDX_CB98062B5278319C (series_id), INDEX IDX_CB98062B4296D31F (genre_id), PRIMARY KEY(series_id, genre_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE series_tags (series_id INT NOT NULL, tag_id INT NOT NULL, INDEX IDX_8AFB15E45278319C (series_id), INDEX IDX_8AFB15E4BAD26311 (tag_id), PRIMARY KEY(series_id, tag_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE series ADD CONSTRAINT FK_3A10012D5BB66C05 FOREIGN KEY (poster_id) REFERENCES media__media (id)');
        $this->addSql('ALTER TABLE series_countries ADD CONSTRAINT FK_CD8A1DA45278319C FOREIGN KEY (series_id) REFERENCES series (id)');
        $this->addSql('ALTER TABLE series_countries ADD CONSTRAINT FK_CD8A1DA4F92F3E70 FOREIGN KEY (country_id) REFERENCES country (id)');
        $this->addSql('ALTER TABLE series_genres ADD CONSTRAINT FK_CB98062B4296D31F FOREIGN KEY (genre_id) REFERENCES genre (id)');
        $this->addSql('ALTER TABLE series_genres ADD CONSTRAINT FK_CB98062B5278319C FOREIGN KEY (series_id) REFERENCES series (id)');
        $this->addSql('ALTER TABLE series_tags ADD CONSTRAINT FK_8AFB15E45278319C FOREIGN KEY (series_id) REFERENCES series (id)');
        $this->addSql('ALTER TABLE series_tags ADD CONSTRAINT FK_8AFB15E4BAD26311 FOREIGN KEY (tag_id) REFERENCES tag (id)');
    }
}
