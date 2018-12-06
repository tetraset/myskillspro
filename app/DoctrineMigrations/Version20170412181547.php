<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170412181547 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP INDEX anonimGameStart ON game');
        $this->addSql('ALTER TABLE game ADD finger_print VARCHAR(100) DEFAULT NULL, DROP anonim_ip');
        $this->addSql('CREATE INDEX anonimGameStart ON game (finger_print, game_start)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP INDEX anonimGameStart ON game');
        $this->addSql('ALTER TABLE game ADD anonim_ip VARCHAR(50) DEFAULT NULL, DROP finger_print');
        $this->addSql('CREATE INDEX anonimGameStart ON game (anonim_ip, game_start)');
    }
}
