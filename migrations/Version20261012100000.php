<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Module OC : flux XML préparés pour X3 à la validation (table oc_offer_flow).
 * Écrite à la main (ne jamais utiliser doctrine:migrations:diff tel quel : il supprimerait les tables SOA / MDF / PP).
 */
final class Version20261012100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Module OC : table oc_offer_flow';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE oc_offer_flow (id INT AUTO_INCREMENT NOT NULL, offer_id INT NOT NULL, article_code VARCHAR(100) NOT NULL, webservice VARCHAR(30) NOT NULL, xml LONGTEXT NOT NULL, status VARCHAR(12) NOT NULL, sales_web_service_id INT DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX idx_oc_offer_flow_offer (offer_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE oc_offer_flow ADD CONSTRAINT FK_OC_OFFER_FLOW_OFFER FOREIGN KEY (offer_id) REFERENCES oc_offer (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE oc_offer_flow');
    }
}
