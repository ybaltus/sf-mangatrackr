<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Baseline PostgreSQL 18 (ADR 0012, D1) : schéma complet généré depuis le mapping sur une base vide.
 *
 * Remplace les 15 migrations MySQL archivées dans migrations-archive-mariadb/ (non chargées).
 * Hors mapping, ajoutés à la main :
 * - extension unaccent (recherche insensible aux accents, D4) ;
 * - index unique sur LOWER(email) de app_user (unicité insensible à la casse). Index d'expression
 *   invisible pour l'introspection de DBAL 3 : il ne produit pas de diff (rapport 010).
 * Index hérités corrigés par la génération : UNIQ_8A6250027B6461 (manga_tenrai_api) et index
 * composite de messenger_messages.
 */
final class Version20261018145659 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Baseline PostgreSQL 18 : schéma complet, unaccent, unicité de LOWER(email)';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform,
            'Baseline réservée à PostgreSQL (ADR 0012) ; les migrations MariaDB sont dans migrations-archive-mariadb/.'
        );

        $this->addSql('CREATE EXTENSION IF NOT EXISTS unaccent');
        $this->addSql('CREATE TABLE app_user (id SERIAL NOT NULL, username VARCHAR(50) NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, is_activated BOOLEAN NOT NULL, id_google VARCHAR(255) DEFAULT NULL, last_connected_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_88BDF3E9E7927C74 ON app_user (email)');
        $this->addSql('CREATE UNIQUE INDEX uniq_app_user_email_lower ON app_user (LOWER(email))');
        $this->addSql('CREATE TABLE editor (id SERIAL NOT NULL, name VARCHAR(150) NOT NULL, name_slug VARCHAR(150) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, is_activated BOOLEAN NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_CCF1F1BADF2B4115 ON editor (name_slug)');
        $this->addSql('CREATE TABLE fantrad (id SERIAL NOT NULL, name VARCHAR(150) NOT NULL, name_slug VARCHAR(150) NOT NULL, url VARCHAR(255) NOT NULL, language VARCHAR(10) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, is_activated BOOLEAN NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_15B1C720DF2B4115 ON fantrad (name_slug)');
        $this->addSql('CREATE TABLE manga (id SERIAL NOT NULL, manga_status_id INT DEFAULT NULL, manga_statistic_id INT NOT NULL, title VARCHAR(255) NOT NULL, title_slug VARCHAR(255) NOT NULL, title_alternative VARCHAR(255) DEFAULT NULL, description TEXT DEFAULT NULL, nb_chapters DOUBLE PRECISION DEFAULT NULL, author VARCHAR(150) NOT NULL, designer VARCHAR(150) DEFAULT NULL, is_adult BOOLEAN NOT NULL, published_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, is_activated BOOLEAN NOT NULL, url_img VARCHAR(255) DEFAULT NULL, title_english VARCHAR(255) DEFAULT NULL, title_synonym VARCHAR(255) DEFAULT NULL, nb_volumes INT DEFAULT NULL, last_released_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, category VARCHAR(100) DEFAULT \'Unknown\' NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_765A9E03D347411D ON manga (title_slug)');
        $this->addSql('CREATE INDEX IDX_765A9E0384558910 ON manga (manga_status_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_765A9E03508F0995 ON manga (manga_statistic_id)');
        $this->addSql('CREATE TABLE manga_fantrad (manga_id INT NOT NULL, fantrad_id INT NOT NULL, PRIMARY KEY(manga_id, fantrad_id))');
        $this->addSql('CREATE INDEX IDX_8B0612217B6461 ON manga_fantrad (manga_id)');
        $this->addSql('CREATE INDEX IDX_8B06122166D14156 ON manga_fantrad (fantrad_id)');
        $this->addSql('CREATE TABLE manga_editor (manga_id INT NOT NULL, editor_id INT NOT NULL, PRIMARY KEY(manga_id, editor_id))');
        $this->addSql('CREATE INDEX IDX_1FB153BD7B6461 ON manga_editor (manga_id)');
        $this->addSql('CREATE INDEX IDX_1FB153BD6995AC4C ON manga_editor (editor_id)');
        $this->addSql('CREATE TABLE manga_manga_type (manga_id INT NOT NULL, manga_type_id INT NOT NULL, PRIMARY KEY(manga_id, manga_type_id))');
        $this->addSql('CREATE INDEX IDX_7B02C3BF7B6461 ON manga_manga_type (manga_id)');
        $this->addSql('CREATE INDEX IDX_7B02C3BF5BFB5992 ON manga_manga_type (manga_type_id)');
        $this->addSql('CREATE TABLE manga_manga_updates_api (id SERIAL NOT NULL, manga_id INT NOT NULL, mu_series_id VARCHAR(255) DEFAULT NULL, mu_description TEXT DEFAULT NULL, mu_url VARCHAR(255) DEFAULT NULL, mu_img_jpg VARCHAR(255) DEFAULT NULL, mu_thumb_jpg VARCHAR(255) DEFAULT NULL, mu_year INT DEFAULT NULL, mu_genres JSON DEFAULT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_6D68FF7A7B6461 ON manga_manga_updates_api (manga_id)');
        $this->addSql('CREATE TABLE manga_release_config (id SERIAL NOT NULL, manga_id INT DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_194912A7B6461 ON manga_release_config (manga_id)');
        $this->addSql('CREATE TABLE manga_statistic (id SERIAL NOT NULL, rating DOUBLE PRECISION DEFAULT NULL, nb_track INT NOT NULL, nb_view INT NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE manga_status (id SERIAL NOT NULL, title VARCHAR(180) NOT NULL, title_slug VARCHAR(180) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, is_activated BOOLEAN NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_A840C71BD347411D ON manga_status (title_slug)');
        $this->addSql('CREATE TABLE manga_tenrai_api (id SERIAL NOT NULL, manga_id INT NOT NULL, mal_id INT DEFAULT NULL, mal_description TEXT DEFAULT NULL, mal_url VARCHAR(255) DEFAULT NULL, mal_img_jpg VARCHAR(255) DEFAULT NULL, mal_img_jpg_large VARCHAR(255) DEFAULT NULL, mal_img_webp VARCHAR(255) DEFAULT NULL, mal_img_webp_large VARCHAR(255) DEFAULT NULL, mal_chapters INT DEFAULT NULL, mal_volume INT DEFAULT NULL, mal_start_published_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, mal_end_published_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, mal_demographics JSON DEFAULT NULL, mal_genres JSON DEFAULT NULL, mal_serializations JSON DEFAULT NULL, mal_authors JSON DEFAULT NULL, mal_scored DOUBLE PRECISION DEFAULT NULL, mal_scrored_by INT DEFAULT NULL, mal_rank INT DEFAULT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8A6250027B6461 ON manga_tenrai_api (manga_id)');
        $this->addSql('CREATE TABLE manga_type (id SERIAL NOT NULL, name VARCHAR(180) NOT NULL, name_slug VARCHAR(180) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, is_activated BOOLEAN NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_D38ABE63DF2B4115 ON manga_type (name_slug)');
        $this->addSql('CREATE TABLE manga_user_track (id SERIAL NOT NULL, manga_id INT NOT NULL, user_track_list_id INT NOT NULL, status_track_id INT NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, is_activated BOOLEAN NOT NULL, nb_chapters DOUBLE PRECISION DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_9CA97E227B6461 ON manga_user_track (manga_id)');
        $this->addSql('CREATE INDEX IDX_9CA97E22ABE23C6 ON manga_user_track (user_track_list_id)');
        $this->addSql('CREATE INDEX IDX_9CA97E22C3089B9B ON manga_user_track (status_track_id)');
        $this->addSql('CREATE TABLE release_manga_updates_api (id SERIAL NOT NULL, manga_id INT NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, released_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, volume_val VARCHAR(50) DEFAULT NULL, chapter_val VARCHAR(50) NOT NULL, scanlators JSON DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_C9D845747B6461 ON release_manga_updates_api (manga_id)');
        $this->addSql('CREATE TABLE status_track (id SERIAL NOT NULL, name VARCHAR(50) NOT NULL, name_slug VARCHAR(50) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, is_activated BOOLEAN NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_21B9A956DF2B4115 ON status_track (name_slug)');
        $this->addSql('CREATE TABLE text_content_page (id SERIAL NOT NULL, name VARCHAR(15) NOT NULL, name_slug VARCHAR(15) NOT NULL, content TEXT NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, is_activated BOOLEAN NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE user_invitation_code (id SERIAL NOT NULL, user_id INT DEFAULT NULL, email VARCHAR(180) NOT NULL, code_invitation VARCHAR(255) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, expired_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, is_activated BOOLEAN NOT NULL, sending_email_status VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_A8C89043E7927C74 ON user_invitation_code (email)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_A8C89043A76ED395 ON user_invitation_code (user_id)');
        $this->addSql('CREATE TABLE user_news (id SERIAL NOT NULL, user_id INT NOT NULL, is_activated BOOLEAN NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_6752D599A76ED395 ON user_news (user_id)');
        $this->addSql('CREATE TABLE user_reset_password (id SERIAL NOT NULL, user_id INT NOT NULL, reset_code VARCHAR(255) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, expired_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, sending_email_status VARCHAR(255) NOT NULL, is_activated BOOLEAN NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_D21DE3BCA76ED395 ON user_reset_password (user_id)');
        $this->addSql('CREATE TABLE user_track_list (id SERIAL NOT NULL, user_id INT NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_7EF916F1A76ED395 ON user_track_list (user_id)');
        $this->addSql('CREATE TABLE messenger_messages (id BIGSERIAL NOT NULL, body TEXT NOT NULL, headers TEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, available_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, delivered_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 ON messenger_messages (queue_name, available_at, delivered_at, id)');
        $this->addSql('ALTER TABLE manga ADD CONSTRAINT FK_765A9E0384558910 FOREIGN KEY (manga_status_id) REFERENCES manga_status (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE manga ADD CONSTRAINT FK_765A9E03508F0995 FOREIGN KEY (manga_statistic_id) REFERENCES manga_statistic (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE manga_fantrad ADD CONSTRAINT FK_8B0612217B6461 FOREIGN KEY (manga_id) REFERENCES manga (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE manga_fantrad ADD CONSTRAINT FK_8B06122166D14156 FOREIGN KEY (fantrad_id) REFERENCES fantrad (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE manga_editor ADD CONSTRAINT FK_1FB153BD7B6461 FOREIGN KEY (manga_id) REFERENCES manga (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE manga_editor ADD CONSTRAINT FK_1FB153BD6995AC4C FOREIGN KEY (editor_id) REFERENCES editor (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE manga_manga_type ADD CONSTRAINT FK_7B02C3BF7B6461 FOREIGN KEY (manga_id) REFERENCES manga (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE manga_manga_type ADD CONSTRAINT FK_7B02C3BF5BFB5992 FOREIGN KEY (manga_type_id) REFERENCES manga_type (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE manga_manga_updates_api ADD CONSTRAINT FK_6D68FF7A7B6461 FOREIGN KEY (manga_id) REFERENCES manga (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE manga_release_config ADD CONSTRAINT FK_194912A7B6461 FOREIGN KEY (manga_id) REFERENCES manga (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE manga_tenrai_api ADD CONSTRAINT FK_8A6250027B6461 FOREIGN KEY (manga_id) REFERENCES manga (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE manga_user_track ADD CONSTRAINT FK_9CA97E227B6461 FOREIGN KEY (manga_id) REFERENCES manga (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE manga_user_track ADD CONSTRAINT FK_9CA97E22ABE23C6 FOREIGN KEY (user_track_list_id) REFERENCES user_track_list (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE manga_user_track ADD CONSTRAINT FK_9CA97E22C3089B9B FOREIGN KEY (status_track_id) REFERENCES status_track (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE release_manga_updates_api ADD CONSTRAINT FK_C9D845747B6461 FOREIGN KEY (manga_id) REFERENCES manga (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE user_invitation_code ADD CONSTRAINT FK_A8C89043A76ED395 FOREIGN KEY (user_id) REFERENCES app_user (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE user_news ADD CONSTRAINT FK_6752D599A76ED395 FOREIGN KEY (user_id) REFERENCES app_user (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE user_reset_password ADD CONSTRAINT FK_D21DE3BCA76ED395 FOREIGN KEY (user_id) REFERENCES app_user (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE user_track_list ADD CONSTRAINT FK_7EF916F1A76ED395 FOREIGN KEY (user_id) REFERENCES app_user (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform,
            'Baseline réservée à PostgreSQL (ADR 0012) ; les migrations MariaDB sont dans migrations-archive-mariadb/.'
        );

        $this->addSql('ALTER TABLE manga DROP CONSTRAINT FK_765A9E0384558910');
        $this->addSql('ALTER TABLE manga DROP CONSTRAINT FK_765A9E03508F0995');
        $this->addSql('ALTER TABLE manga_fantrad DROP CONSTRAINT FK_8B0612217B6461');
        $this->addSql('ALTER TABLE manga_fantrad DROP CONSTRAINT FK_8B06122166D14156');
        $this->addSql('ALTER TABLE manga_editor DROP CONSTRAINT FK_1FB153BD7B6461');
        $this->addSql('ALTER TABLE manga_editor DROP CONSTRAINT FK_1FB153BD6995AC4C');
        $this->addSql('ALTER TABLE manga_manga_type DROP CONSTRAINT FK_7B02C3BF7B6461');
        $this->addSql('ALTER TABLE manga_manga_type DROP CONSTRAINT FK_7B02C3BF5BFB5992');
        $this->addSql('ALTER TABLE manga_manga_updates_api DROP CONSTRAINT FK_6D68FF7A7B6461');
        $this->addSql('ALTER TABLE manga_release_config DROP CONSTRAINT FK_194912A7B6461');
        $this->addSql('ALTER TABLE manga_tenrai_api DROP CONSTRAINT FK_8A6250027B6461');
        $this->addSql('ALTER TABLE manga_user_track DROP CONSTRAINT FK_9CA97E227B6461');
        $this->addSql('ALTER TABLE manga_user_track DROP CONSTRAINT FK_9CA97E22ABE23C6');
        $this->addSql('ALTER TABLE manga_user_track DROP CONSTRAINT FK_9CA97E22C3089B9B');
        $this->addSql('ALTER TABLE release_manga_updates_api DROP CONSTRAINT FK_C9D845747B6461');
        $this->addSql('ALTER TABLE user_invitation_code DROP CONSTRAINT FK_A8C89043A76ED395');
        $this->addSql('ALTER TABLE user_news DROP CONSTRAINT FK_6752D599A76ED395');
        $this->addSql('ALTER TABLE user_reset_password DROP CONSTRAINT FK_D21DE3BCA76ED395');
        $this->addSql('ALTER TABLE user_track_list DROP CONSTRAINT FK_7EF916F1A76ED395');
        $this->addSql('DROP TABLE app_user');
        $this->addSql('DROP TABLE editor');
        $this->addSql('DROP TABLE fantrad');
        $this->addSql('DROP TABLE manga');
        $this->addSql('DROP TABLE manga_fantrad');
        $this->addSql('DROP TABLE manga_editor');
        $this->addSql('DROP TABLE manga_manga_type');
        $this->addSql('DROP TABLE manga_manga_updates_api');
        $this->addSql('DROP TABLE manga_release_config');
        $this->addSql('DROP TABLE manga_statistic');
        $this->addSql('DROP TABLE manga_status');
        $this->addSql('DROP TABLE manga_tenrai_api');
        $this->addSql('DROP TABLE manga_type');
        $this->addSql('DROP TABLE manga_user_track');
        $this->addSql('DROP TABLE release_manga_updates_api');
        $this->addSql('DROP TABLE status_track');
        $this->addSql('DROP TABLE text_content_page');
        $this->addSql('DROP TABLE user_invitation_code');
        $this->addSql('DROP TABLE user_news');
        $this->addSql('DROP TABLE user_reset_password');
        $this->addSql('DROP TABLE user_track_list');
        $this->addSql('DROP TABLE messenger_messages');
        // unaccent n'est pas supprimée : elle a pu exister avant la baseline (IF NOT EXISTS).
    }
}
