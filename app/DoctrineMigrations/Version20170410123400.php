<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170410123400 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP INDEX isPublic ON video_clip');
        $this->addSql('ALTER TABLE video_clip ADD long_clip TINYINT(1) NOT NULL');
        $this->addSql('CREATE INDEX isPublicShortVideoCLips ON video_clip (is_public, long_clip)');
        $this->addSql('UPDATE video_clip SET long_clip = 0');
        $this->addSql('UPDATE video_clip SET long_clip = 1 WHERE id_parent_videoclip IS NULL');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP INDEX isPublicShortVideoCLips ON video_clip');
        $this->addSql('ALTER TABLE video_clip DROP long_clip');
        $this->addSql('CREATE INDEX isPublic ON video_clip (is_public)');
    }
}
