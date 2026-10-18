<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260925000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Rename the manga API data table for Tenrai';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('RENAME TABLE manga_jikan_api TO manga_tenrai_api');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('RENAME TABLE manga_tenrai_api TO manga_jikan_api');
    }
}
