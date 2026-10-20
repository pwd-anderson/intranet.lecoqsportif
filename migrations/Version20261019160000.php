<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261019160000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute la table suivi_sellin_note (notes de suivi client du module Suivi Sellin global)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE suivi_sellin_note (id INT AUTO_INCREMENT NOT NULL, customer_code VARCHAR(30) NOT NULL, text LONGTEXT NOT NULL, author_id INT NOT NULL, author_name VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX idx_ssn_customer (customer_code), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE suivi_sellin_note');
    }
}
