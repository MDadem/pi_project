<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250214142009 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE post ADD community_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE post ADD CONSTRAINT FK_5A8A6C8DFDA7B0BF FOREIGN KEY (community_id) REFERENCES community (id)');
        $this->addSql('CREATE INDEX IDX_5A8A6C8DFDA7B0BF ON post (community_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE post DROP FOREIGN KEY FK_5A8A6C8DFDA7B0BF');
        $this->addSql('DROP INDEX IDX_5A8A6C8DFDA7B0BF ON post');
        $this->addSql('ALTER TABLE post DROP community_id');
    }
}
