<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250302231616 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE cart ADD user_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE cart ADD CONSTRAINT FK_BA388B7A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('CREATE INDEX IDX_BA388B7A76ED395 ON cart (user_id)');
        $this->addSql('ALTER TABLE order_item CHANGE price price DOUBLE PRECISION NOT NULL, CHANGE total total DOUBLE PRECISION NOT NULL');
        $this->addSql('ALTER TABLE post_comment DROP FOREIGN KEY FK_A99CE55FA76ED395');
        $this->addSql('ALTER TABLE post_comment DROP FOREIGN KEY FK_A99CE55F4B89032C');
        $this->addSql('DROP INDEX IDX_A99CE55FA76ED395 ON post_comment');
        $this->addSql('DROP INDEX IDX_A99CE55F4B89032C ON post_comment');
        $this->addSql('ALTER TABLE post_comment ADD modification_date DATETIME NOT NULL, ADD status TINYINT(1) NOT NULL, DROP post_id, DROP user_id');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE cart DROP FOREIGN KEY FK_BA388B7A76ED395');
        $this->addSql('DROP INDEX IDX_BA388B7A76ED395 ON cart');
        $this->addSql('ALTER TABLE cart DROP user_id');
        $this->addSql('ALTER TABLE order_item CHANGE price price NUMERIC(10, 2) NOT NULL, CHANGE total total NUMERIC(10, 2) NOT NULL');
        $this->addSql('ALTER TABLE post_comment ADD post_id INT DEFAULT NULL, ADD user_id INT NOT NULL, DROP modification_date, DROP status');
        $this->addSql('ALTER TABLE post_comment ADD CONSTRAINT FK_A99CE55FA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE post_comment ADD CONSTRAINT FK_A99CE55F4B89032C FOREIGN KEY (post_id) REFERENCES post (id)');
        $this->addSql('CREATE INDEX IDX_A99CE55FA76ED395 ON post_comment (user_id)');
        $this->addSql('CREATE INDEX IDX_A99CE55F4B89032C ON post_comment (post_id)');
    }
}
