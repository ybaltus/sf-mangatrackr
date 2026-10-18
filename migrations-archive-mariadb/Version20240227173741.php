<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240227173741 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create MangaMangaUpdateApi entity';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE manga_manga_updates_api (id INT AUTO_INCREMENT NOT NULL, manga_id INT NOT NULL, mu_series_id VARCHAR(255) DEFAULT NULL, mu_description LONGTEXT DEFAULT NULL, mu_url VARCHAR(255) DEFAULT NULL, mu_img_jpg VARCHAR(255) DEFAULT NULL, mu_thumb_jpg VARCHAR(255) DEFAULT NULL, mu_year INT DEFAULT NULL, mu_genres JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX UNIQ_6D68FF7A7B6461 (manga_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE manga_manga_updates_api ADD CONSTRAINT FK_6D68FF7A7B6461 FOREIGN KEY (manga_id) REFERENCES manga (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE manga_manga_updates_api DROP FOREIGN KEY FK_6D68FF7A7B6461');
        $this->addSql('DROP TABLE manga_manga_updates_api');
    }
}
