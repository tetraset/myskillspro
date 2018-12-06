<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170127120949 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE dict_word_en ADD public_html_translation LONGTEXT DEFAULT NULL, ADD public_source VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE dict_word_ru ADD public_html_translation LONGTEXT DEFAULT NULL, ADD public_source VARCHAR(255) DEFAULT NULL');
        $this->addSql('
            UPDATE `dict_word_en` INNER JOIN (
            SELECT p.* FROM (
            SELECT 
            t.`id_word`, 
            t.html_translation, 
            IF(t.login_user IS NULL, IF(s.link IS NULL, s.source, CONCAT(\'<a target="_blank" href="\', s.link, \'">\', s.source, \'</a>\')), CONCAT(\'Translated by \', t.login_user)) as psource, 
            s.priority as source_priority
            FROM `dict_translation_en_ru` t
            INNER JOIN `dict_source` s USING(id_source)
            ORDER BY t.id_word ASC, s.`priority` DESC
            ) p
            GROUP BY `id_word`
            ) p2 USING (id_word)
            SET public_html_translation = p2.html_translation, public_source = p2.psource
        ');
        $this->addSql('
            UPDATE `dict_word_ru` INNER JOIN (
            SELECT p.* FROM (
            SELECT 
            t.`id_word`, 
            t.html_translation, 
            IF(t.login_user IS NULL, IF(s.link IS NULL, s.source, CONCAT(\'<a target="_blank" href="\', s.link, \'">\', s.source, \'</a>\')), CONCAT(\'Translated by \', t.login_user)) as psource, 
            s.priority as source_priority
            FROM `dict_translation_ru_en` t
            INNER JOIN `dict_source` s USING(id_source)
            ORDER BY t.id_word ASC, s.`priority` DESC
            ) p
            GROUP BY `id_word`
            ) p2 USING (id_word)
            SET public_html_translation = p2.html_translation, public_source = p2.psource
        ');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE dict_word_en DROP public_html_translation, DROP public_source');
        $this->addSql('ALTER TABLE dict_word_ru DROP public_html_translation, DROP public_source');
    }
}
