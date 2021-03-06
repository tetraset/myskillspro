<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170415102148 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP INDEX complexity ON video_clip');
        $this->addSql('CREATE INDEX flesch_complexity ON video_clip (flesch_kincaid_reading_ease)');
        $this->addSql('CREATE INDEX complexity ON video_clip (symbols_count, gunning_fog_score)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP INDEX flesch_complexity ON video_clip');
        $this->addSql('DROP INDEX complexity ON video_clip');
        $this->addSql('CREATE INDEX complexity ON video_clip (flesch_kincaid_reading_ease, gunning_fog_score)');
    }
}
