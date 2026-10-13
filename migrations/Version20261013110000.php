<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Module OC : prix d'achat X3 (liste 1PFCOL) et sa devise, figés par ligne d'OC pour la vue de validation.
 * Écrite à la main (ne jamais utiliser doctrine:migrations:diff tel quel : il supprimerait les tables SOA / MDF / PP).
 */
final class Version20261013110000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Module OC : colonnes purchase_price et purchase_currency sur oc_offer_line';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE oc_offer_line ADD purchase_price NUMERIC(12, 4) DEFAULT NULL, ADD purchase_currency VARCHAR(10) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE oc_offer_line DROP purchase_price, DROP purchase_currency');
    }
}
