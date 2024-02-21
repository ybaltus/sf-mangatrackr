<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240221203803 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create MangaReleaseConfig and ReleaseMangaUpdatesAPI entities';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE manga_release_config (id INT AUTO_INCREMENT NOT NULL, manga_id INT DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX UNIQ_194912A7B6461 (manga_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE release_manga_updates_api (id INT AUTO_INCREMENT NOT NULL, manga_id INT NOT NULL, chapter_nb DOUBLE PRECISION NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', released_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_C9D845747B6461 (manga_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE manga_release_config ADD CONSTRAINT FK_194912A7B6461 FOREIGN KEY (manga_id) REFERENCES manga (id)');
        $this->addSql('ALTER TABLE release_manga_updates_api ADD CONSTRAINT FK_C9D845747B6461 FOREIGN KEY (manga_id) REFERENCES manga (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE manga_release_config DROP FOREIGN KEY FK_194912A7B6461');
        $this->addSql('ALTER TABLE release_manga_updates_api DROP FOREIGN KEY FK_C9D845747B6461');
        $this->addSql('DROP TABLE manga_release_config');
        $this->addSql('DROP TABLE release_manga_updates_api');
    }
}
