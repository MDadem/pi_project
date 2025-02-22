<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250222000034 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE post_comment DROP FOREIGN KEY FK_A99CE55FA76ED395');
        $this->addSql('DROP INDEX IDX_A99CE55FA76ED395 ON post_comment');
        $this->addSql('ALTER TABLE post_comment DROP user_id, DROP status, CHANGE modification_date user DATETIME NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE post_comment ADD user_id INT DEFAULT NULL, ADD status TINYINT(1) NOT NULL, CHANGE user modification_date DATETIME NOT NULL');
        $this->addSql('ALTER TABLE post_comment ADD CONSTRAINT FK_A99CE55FA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_A99CE55FA76ED395 ON post_comment (user_id)');
    }
}
