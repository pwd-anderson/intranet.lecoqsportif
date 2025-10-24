<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251024134645 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE soacampaign_status_history DROP FOREIGN KEY FK_E14A83F7CE9959DE');
        $this->addSql('ALTER TABLE soacampaign_request_product DROP FOREIGN KEY FK_A6A5DE769660E61E');
        $this->addSql('ALTER TABLE soacampaign_request DROP FOREIGN KEY FK_9BF14663CE9959DE');
        $this->addSql('ALTER TABLE soacampaign_document DROP FOREIGN KEY FK_6CC252889660E61E');
        $this->addSql('ALTER TABLE soacampaign_document DROP FOREIGN KEY FK_6CC25288AC4B0513');
        $this->addSql('DROP TABLE soacampaign_status_history');
        $this->addSql('DROP TABLE soacampaign_status');
        $this->addSql('DROP TABLE soacampaign_request_product');
        $this->addSql('DROP TABLE soacampaign_request');
        $this->addSql('DROP TABLE soacampaign_document_type');
        $this->addSql('DROP TABLE soacampaign_document');
        $this->addSql('ALTER TABLE exchange_rates_moyen ADD mois_taux DATE DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE soacampaign_status_history (id INT AUTO_INCREMENT NOT NULL, soa_campaign_status_id INT NOT NULL, create_time DATE NOT NULL, update_time DATE NOT NULL, user VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, INDEX IDX_E14A83F7CE9959DE (soa_campaign_status_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE soacampaign_status (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, label VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE soacampaign_request_product (id INT AUTO_INCREMENT NOT NULL, soa_campaign_request_id INT NOT NULL, create_time DATE NOT NULL, update_time DATE NOT NULL, product VARCHAR(100) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, customer_quantity INT NOT NULL, customer_price DOUBLE PRECISION NOT NULL, buying_price DOUBLE PRECISION NOT NULL, INDEX IDX_A6A5DE769660E61E (soa_campaign_request_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE soacampaign_request (id INT AUTO_INCREMENT NOT NULL, soa_campaign_status_id INT NOT NULL, create_time DATE NOT NULL, update_time DATE NOT NULL, soa_campaign_number VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, customer_code VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, customer_name VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, customer_company VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, customer_language_iso VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, customer_currency VARCHAR(10) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, title VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, product_focused LONGTEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, starting_date DATE NOT NULL, ending_date DATE NOT NULL, comment LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, comment_ceo_refused LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, is_synced TINYINT(1) NOT NULL, send_email_supplier TINYINT(1) DEFAULT NULL, create_user VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, choice_currency VARCHAR(5) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, company_code VARCHAR(50) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, brand_name VARCHAR(50) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, INDEX IDX_9BF14663CE9959DE (soa_campaign_status_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE soacampaign_document_type (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, label VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE soacampaign_document (id INT AUTO_INCREMENT NOT NULL, soa_campaign_document_type_id INT NOT NULL, soa_campaign_request_id INT DEFAULT NULL, name VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, mime_type VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, create_time DATETIME NOT NULL, update_time DATETIME DEFAULT NULL, INDEX IDX_6CC252889660E61E (soa_campaign_request_id), INDEX IDX_6CC25288AC4B0513 (soa_campaign_document_type_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE soacampaign_status_history ADD CONSTRAINT FK_E14A83F7CE9959DE FOREIGN KEY (soa_campaign_status_id) REFERENCES soacampaign_status (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE soacampaign_request_product ADD CONSTRAINT FK_A6A5DE769660E61E FOREIGN KEY (soa_campaign_request_id) REFERENCES soacampaign_request (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE soacampaign_request ADD CONSTRAINT FK_9BF14663CE9959DE FOREIGN KEY (soa_campaign_status_id) REFERENCES soacampaign_status (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE soacampaign_document ADD CONSTRAINT FK_6CC252889660E61E FOREIGN KEY (soa_campaign_request_id) REFERENCES soacampaign_request (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE soacampaign_document ADD CONSTRAINT FK_6CC25288AC4B0513 FOREIGN KEY (soa_campaign_document_type_id) REFERENCES soacampaign_document_type (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE exchange_rates_moyen DROP mois_taux');
    }
}
