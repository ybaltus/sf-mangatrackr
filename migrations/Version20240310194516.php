<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240310194516 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '[Manga] Extend title length to 255 + add titleSynonym and titleEnglish fields';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE manga ADD title_english VARCHAR(255) DEFAULT NULL, ADD title_synonym VARCHAR(255) DEFAULT NULL, CHANGE title title VARCHAR(255) NOT NULL, CHANGE title_slug title_slug VARCHAR(255) NOT NULL, CHANGE title_alternative title_alternative VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE manga DROP title_english, DROP title_synonym, CHANGE title title VARCHAR(180) NOT NULL, CHANGE title_slug title_slug VARCHAR(180) NOT NULL, CHANGE title_alternative title_alternative VARCHAR(180) DEFAULT NULL');
    }
}
