<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231122101428 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create MangaJikanAPI entity';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE manga_jikan_api (id INT AUTO_INCREMENT NOT NULL, manga_id INT NOT NULL, mal_id INT DEFAULT NULL, mal_description LONGTEXT DEFAULT NULL, mal_url VARCHAR(255) DEFAULT NULL, mal_img_jpg VARCHAR(255) DEFAULT NULL, mal_img_jpg_large VARCHAR(255) DEFAULT NULL, mal_img_webp VARCHAR(255) DEFAULT NULL, mal_img_webp_large VARCHAR(255) DEFAULT NULL, mal_chapters INT DEFAULT NULL, mal_volume INT DEFAULT NULL, mal_start_published_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', mal_end_published_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', mal_demographics JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', mal_genres JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', mal_serializations JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', mal_authors JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', mal_scored DOUBLE PRECISION DEFAULT NULL, mal_scrored_by INT DEFAULT NULL, mal_rank INT DEFAULT NULL, updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX UNIQ_A2733E8C7B6461 (manga_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE manga_jikan_api ADD CONSTRAINT FK_A2733E8C7B6461 FOREIGN KEY (manga_id) REFERENCES manga (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE manga_jikan_api DROP FOREIGN KEY FK_A2733E8C7B6461');
        $this->addSql('DROP TABLE manga_jikan_api');
    }
}
