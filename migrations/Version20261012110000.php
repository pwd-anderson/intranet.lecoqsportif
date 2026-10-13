<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Module OC : lien direct sales_web_service -> OC (colonne oc_offer_id, nulle pour les autres modules).
 * Écrite à la main (ne jamais utiliser doctrine:migrations:diff tel quel : il supprimerait les tables SOA / MDF / PP).
 */
final class Version20261012110000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Module OC : colonne oc_offer_id sur sales_web_service';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE sales_web_service ADD oc_offer_id INT DEFAULT NULL');
        $this->addSql('CREATE INDEX idx_sales_web_service_oc_offer ON sales_web_service (oc_offer_id)');
        // Flux OC déjà placés en file avant l'ajout de la colonne.
        $this->addSql('UPDATE sales_web_service sws INNER JOIN oc_offer_flow f ON f.sales_web_service_id = sws.id SET sws.oc_offer_id = f.offer_id');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX idx_sales_web_service_oc_offer ON sales_web_service');
        $this->addSql('ALTER TABLE sales_web_service DROP oc_offer_id');
    }
}
