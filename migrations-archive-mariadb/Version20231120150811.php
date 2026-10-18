<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231120150811 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Init migration';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE editor (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(150) NOT NULL, name_slug VARCHAR(150) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', is_activated TINYINT(1) NOT NULL, UNIQUE INDEX UNIQ_CCF1F1BADF2B4115 (name_slug), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE fantrad (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(150) NOT NULL, name_slug VARCHAR(150) NOT NULL, url VARCHAR(255) NOT NULL, language VARCHAR(10) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', is_activated TINYINT(1) NOT NULL, UNIQUE INDEX UNIQ_15B1C720DF2B4115 (name_slug), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE manga (id INT AUTO_INCREMENT NOT NULL, manga_status_id INT DEFAULT NULL, manga_statistic_id INT NOT NULL, title VARCHAR(180) NOT NULL, title_slug VARCHAR(180) NOT NULL, title_alternative VARCHAR(180) DEFAULT NULL, description LONGTEXT DEFAULT NULL, nb_chapters DOUBLE PRECISION DEFAULT NULL, author VARCHAR(150) NOT NULL, designer VARCHAR(150) DEFAULT NULL, is_adult TINYINT(1) NOT NULL, published_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', is_activated TINYINT(1) NOT NULL, UNIQUE INDEX UNIQ_765A9E03D347411D (title_slug), INDEX IDX_765A9E0384558910 (manga_status_id), UNIQUE INDEX UNIQ_765A9E03508F0995 (manga_statistic_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE manga_fantrad (manga_id INT NOT NULL, fantrad_id INT NOT NULL, INDEX IDX_8B0612217B6461 (manga_id), INDEX IDX_8B06122166D14156 (fantrad_id), PRIMARY KEY(manga_id, fantrad_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE manga_editor (manga_id INT NOT NULL, editor_id INT NOT NULL, INDEX IDX_1FB153BD7B6461 (manga_id), INDEX IDX_1FB153BD6995AC4C (editor_id), PRIMARY KEY(manga_id, editor_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE manga_manga_type (manga_id INT NOT NULL, manga_type_id INT NOT NULL, INDEX IDX_7B02C3BF7B6461 (manga_id), INDEX IDX_7B02C3BF5BFB5992 (manga_type_id), PRIMARY KEY(manga_id, manga_type_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE manga_statistic (id INT AUTO_INCREMENT NOT NULL, rating DOUBLE PRECISION DEFAULT NULL, nb_track INT NOT NULL, nb_view INT NOT NULL, updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE manga_status (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(180) NOT NULL, title_slug VARCHAR(180) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', is_activated TINYINT(1) NOT NULL, UNIQUE INDEX UNIQ_A840C71BD347411D (title_slug), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE manga_type (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(180) NOT NULL, name_slug VARCHAR(180) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', is_activated TINYINT(1) NOT NULL, UNIQUE INDEX UNIQ_D38ABE63DF2B4115 (name_slug), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE manga_user_track (id INT AUTO_INCREMENT NOT NULL, manga_id INT NOT NULL, user_track_list_id INT NOT NULL, status_track_id INT NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', is_activated TINYINT(1) NOT NULL, nb_chapters DOUBLE PRECISION DEFAULT NULL, INDEX IDX_9CA97E227B6461 (manga_id), INDEX IDX_9CA97E22ABE23C6 (user_track_list_id), INDEX IDX_9CA97E22C3089B9B (status_track_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE status_track (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(50) NOT NULL, name_slug VARCHAR(50) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', is_activated TINYINT(1) NOT NULL, UNIQUE INDEX UNIQ_21B9A956DF2B4115 (name_slug), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, username VARCHAR(50) NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL COMMENT \'(DC2Type:json)\', password VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', is_activated TINYINT(1) NOT NULL, UNIQUE INDEX UNIQ_8D93D649E7927C74 (email), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user_news (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, is_activated TINYINT(1) NOT NULL, updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX UNIQ_6752D599A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user_track_list (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX UNIQ_7EF916F1A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', available_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', delivered_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_75EA56E0FB7336F0 (queue_name), INDEX IDX_75EA56E0E3BD61CE (available_at), INDEX IDX_75EA56E016BA31DB (delivered_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE manga ADD CONSTRAINT FK_765A9E0384558910 FOREIGN KEY (manga_status_id) REFERENCES manga_status (id)');
        $this->addSql('ALTER TABLE manga ADD CONSTRAINT FK_765A9E03508F0995 FOREIGN KEY (manga_statistic_id) REFERENCES manga_statistic (id)');
        $this->addSql('ALTER TABLE manga_fantrad ADD CONSTRAINT FK_8B0612217B6461 FOREIGN KEY (manga_id) REFERENCES manga (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE manga_fantrad ADD CONSTRAINT FK_8B06122166D14156 FOREIGN KEY (fantrad_id) REFERENCES fantrad (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE manga_editor ADD CONSTRAINT FK_1FB153BD7B6461 FOREIGN KEY (manga_id) REFERENCES manga (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE manga_editor ADD CONSTRAINT FK_1FB153BD6995AC4C FOREIGN KEY (editor_id) REFERENCES editor (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE manga_manga_type ADD CONSTRAINT FK_7B02C3BF7B6461 FOREIGN KEY (manga_id) REFERENCES manga (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE manga_manga_type ADD CONSTRAINT FK_7B02C3BF5BFB5992 FOREIGN KEY (manga_type_id) REFERENCES manga_type (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE manga_user_track ADD CONSTRAINT FK_9CA97E227B6461 FOREIGN KEY (manga_id) REFERENCES manga (id)');
        $this->addSql('ALTER TABLE manga_user_track ADD CONSTRAINT FK_9CA97E22ABE23C6 FOREIGN KEY (user_track_list_id) REFERENCES user_track_list (id)');
        $this->addSql('ALTER TABLE manga_user_track ADD CONSTRAINT FK_9CA97E22C3089B9B FOREIGN KEY (status_track_id) REFERENCES status_track (id)');
        $this->addSql('ALTER TABLE user_news ADD CONSTRAINT FK_6752D599A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE user_track_list ADD CONSTRAINT FK_7EF916F1A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE manga DROP FOREIGN KEY FK_765A9E0384558910');
        $this->addSql('ALTER TABLE manga DROP FOREIGN KEY FK_765A9E03508F0995');
        $this->addSql('ALTER TABLE manga_fantrad DROP FOREIGN KEY FK_8B0612217B6461');
        $this->addSql('ALTER TABLE manga_fantrad DROP FOREIGN KEY FK_8B06122166D14156');
        $this->addSql('ALTER TABLE manga_editor DROP FOREIGN KEY FK_1FB153BD7B6461');
        $this->addSql('ALTER TABLE manga_editor DROP FOREIGN KEY FK_1FB153BD6995AC4C');
        $this->addSql('ALTER TABLE manga_manga_type DROP FOREIGN KEY FK_7B02C3BF7B6461');
        $this->addSql('ALTER TABLE manga_manga_type DROP FOREIGN KEY FK_7B02C3BF5BFB5992');
        $this->addSql('ALTER TABLE manga_user_track DROP FOREIGN KEY FK_9CA97E227B6461');
        $this->addSql('ALTER TABLE manga_user_track DROP FOREIGN KEY FK_9CA97E22ABE23C6');
        $this->addSql('ALTER TABLE manga_user_track DROP FOREIGN KEY FK_9CA97E22C3089B9B');
        $this->addSql('ALTER TABLE user_news DROP FOREIGN KEY FK_6752D599A76ED395');
        $this->addSql('ALTER TABLE user_track_list DROP FOREIGN KEY FK_7EF916F1A76ED395');
        $this->addSql('DROP TABLE editor');
        $this->addSql('DROP TABLE fantrad');
        $this->addSql('DROP TABLE manga');
        $this->addSql('DROP TABLE manga_fantrad');
        $this->addSql('DROP TABLE manga_editor');
        $this->addSql('DROP TABLE manga_manga_type');
        $this->addSql('DROP TABLE manga_statistic');
        $this->addSql('DROP TABLE manga_status');
        $this->addSql('DROP TABLE manga_type');
        $this->addSql('DROP TABLE manga_user_track');
        $this->addSql('DROP TABLE status_track');
        $this->addSql('DROP TABLE user');
        $this->addSql('DROP TABLE user_news');
        $this->addSql('DROP TABLE user_track_list');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
