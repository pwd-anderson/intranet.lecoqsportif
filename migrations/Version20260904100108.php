<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260904100108 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute la table x3_collection (cache local des collections X3, alimentée par app:x3-collection:refresh)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE x3_collection (id INT AUTO_INCREMENT NOT NULL, series_code VARCHAR(20) NOT NULL, updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX uniq_seriescode (series_code), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE x3_collection');
    }
}
