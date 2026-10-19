<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Module OC : environnement X3 (PROD / TEST / DEV) de l'OC et de chaque flux déposé dans sales_web_service.
 * Écrite à la main (ne jamais utiliser doctrine:migrations:diff tel quel : il supprimerait les tables SOA / MDF / PP).
 */
final class Version20261015100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Module OC : colonne x3_env sur oc_offer et sales_web_service';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE oc_offer ADD x3_env VARCHAR(10) DEFAULT 'PROD' NOT NULL");
        $this->addSql("ALTER TABLE sales_web_service ADD x3_env VARCHAR(10) DEFAULT 'PROD' NOT NULL");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE oc_offer DROP x3_env');
        $this->addSql('ALTER TABLE sales_web_service DROP x3_env');
    }
}
