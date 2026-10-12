<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Module OC : prix envoyé à X3 (calculé avec la remise client) et remise utilisée, par ligne d'OC.
 * Écrite à la main (ne jamais utiliser doctrine:migrations:diff tel quel : il supprimerait les tables SOA / MDF / PP).
 */
final class Version20261009090000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Module OC : colonnes injected_price et injected_discount sur oc_offer_line';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE oc_offer_line ADD injected_price NUMERIC(12, 4) DEFAULT NULL, ADD injected_discount NUMERIC(8, 4) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE oc_offer_line DROP injected_price, DROP injected_discount');
    }
}
