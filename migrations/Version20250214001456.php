<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250214001456 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE join_request (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, community_id INT DEFAULT NULL, join_date DATETIME NOT NULL, status VARCHAR(255) NOT NULL, INDEX IDX_E932E4FFA76ED395 (user_id), INDEX IDX_E932E4FFFDA7B0BF (community_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE join_request ADD CONSTRAINT FK_E932E4FFA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE join_request ADD CONSTRAINT FK_E932E4FFFDA7B0BF FOREIGN KEY (community_id) REFERENCES community (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE join_request DROP FOREIGN KEY FK_E932E4FFA76ED395');
        $this->addSql('ALTER TABLE join_request DROP FOREIGN KEY FK_E932E4FFFDA7B0BF');
        $this->addSql('DROP TABLE join_request');
    }
}
