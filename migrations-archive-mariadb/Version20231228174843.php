<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231228174843 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create UserResetPassword entity';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE user_reset_password (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, reset_code VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', expired_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', sending_email_status VARCHAR(255) NOT NULL, is_activated TINYINT(1) NOT NULL, INDEX IDX_D21DE3BCA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE user_reset_password ADD CONSTRAINT FK_D21DE3BCA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user_reset_password DROP FOREIGN KEY FK_D21DE3BCA76ED395');
        $this->addSql('DROP TABLE user_reset_password');
    }
}
