<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Module OC (Offres commerciales) : en-tête, lignes et historique.
 *
 * ATTENTION : la migration générée par doctrine:migrations:diff a été réduite à la création des trois tables OC.
 * Elle contenait aussi des DROP TABLE des modules SOA / MDF / Price Protection (tables présentes en base mais
 * dont les entités sont sur des branches non fusionnées dans dev) : ne jamais les inclure ici.
 */
final class Version20261008154551 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Module OC : tables oc_offer, oc_offer_line et oc_offer_history';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE oc_offer (id INT AUTO_INCREMENT NOT NULL, created_by_id INT DEFAULT NULL, source_offer_id INT DEFAULT NULL, number VARCHAR(40) NOT NULL, type VARCHAR(30) NOT NULL, status VARCHAR(20) NOT NULL, customer_code VARCHAR(30) NOT NULL, customer_name VARCHAR(255) NOT NULL, customer_currency VARCHAR(10) DEFAULT NULL, customer_tariff_code VARCHAR(30) DEFAULT NULL, customer_discount NUMERIC(8, 4) DEFAULT NULL, customer_discount_start DATE DEFAULT NULL COMMENT \'(DC2Type:date_immutable)\', customer_discount_end DATE DEFAULT NULL COMMENT \'(DC2Type:date_immutable)\', x3_read_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', start_date DATE NOT NULL COMMENT \'(DC2Type:date_immutable)\', end_date DATE NOT NULL COMMENT \'(DC2Type:date_immutable)\', comment LONGTEXT DEFAULT NULL, created_by_name VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', submitted_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', decided_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_C49BF107B03A8386 (created_by_id), INDEX IDX_C49BF10710E6423C (source_offer_id), INDEX idx_oc_offer_status (status), INDEX idx_oc_offer_customer (customer_code), UNIQUE INDEX uniq_oc_offer_number (number), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE oc_offer_history (id INT AUTO_INCREMENT NOT NULL, offer_id INT NOT NULL, user_id INT DEFAULT NULL, action VARCHAR(20) NOT NULL, from_status VARCHAR(20) DEFAULT NULL, to_status VARCHAR(20) NOT NULL, user_name VARCHAR(255) NOT NULL, reason LONGTEXT DEFAULT NULL, context JSON DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_F00A01D9A76ED395 (user_id), INDEX idx_oc_offer_history_offer (offer_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE oc_offer_line (id INT AUTO_INCREMENT NOT NULL, offer_id INT NOT NULL, position INT NOT NULL, article_code VARCHAR(50) NOT NULL, article_label VARCHAR(255) DEFAULT NULL, collection VARCHAR(30) DEFAULT NULL, model_code VARCHAR(50) DEFAULT NULL, x3_tariff NUMERIC(12, 4) DEFAULT NULL, x3_msrp NUMERIC(12, 4) DEFAULT NULL, x3_read_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', tariff NUMERIC(12, 4) NOT NULL, msrp NUMERIC(12, 4) DEFAULT NULL, vat_rate NUMERIC(6, 2) DEFAULT NULL, margin NUMERIC(8, 4) DEFAULT NULL, min_qty INT NOT NULL, max_qty INT NOT NULL, comment LONGTEXT DEFAULT NULL, INDEX IDX_5E1B2C6453C674EE (offer_id), INDEX idx_oc_offer_line_article (article_code), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE oc_offer ADD CONSTRAINT FK_C49BF107B03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE oc_offer ADD CONSTRAINT FK_C49BF10710E6423C FOREIGN KEY (source_offer_id) REFERENCES oc_offer (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE oc_offer_history ADD CONSTRAINT FK_F00A01D953C674EE FOREIGN KEY (offer_id) REFERENCES oc_offer (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE oc_offer_history ADD CONSTRAINT FK_F00A01D9A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE oc_offer_line ADD CONSTRAINT FK_5E1B2C6453C674EE FOREIGN KEY (offer_id) REFERENCES oc_offer (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE oc_offer DROP FOREIGN KEY FK_C49BF107B03A8386');
        $this->addSql('ALTER TABLE oc_offer DROP FOREIGN KEY FK_C49BF10710E6423C');
        $this->addSql('ALTER TABLE oc_offer_history DROP FOREIGN KEY FK_F00A01D953C674EE');
        $this->addSql('ALTER TABLE oc_offer_history DROP FOREIGN KEY FK_F00A01D9A76ED395');
        $this->addSql('ALTER TABLE oc_offer_line DROP FOREIGN KEY FK_5E1B2C6453C674EE');
        $this->addSql('DROP TABLE oc_offer_history');
        $this->addSql('DROP TABLE oc_offer_line');
        $this->addSql('DROP TABLE oc_offer');
    }
}
