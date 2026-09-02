<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260902073113 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE soa_history (id INT AUTO_INCREMENT NOT NULL, soa_request_id INT NOT NULL, user VARCHAR(150) NOT NULL, statut VARCHAR(50) NOT NULL, statut_label VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, INDEX IDX_5163015B97733964 (soa_request_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE soa_history ADD CONSTRAINT FK_5163015B97733964 FOREIGN KEY (soa_request_id) REFERENCES soa_request (id) ON DELETE CASCADE');
        $this->addSql('DROP INDEX idx_soa_request_client_code ON soa_request');
        $this->addSql('DROP INDEX idx_soa_request_representant ON soa_request');
        $this->addSql('ALTER TABLE soa_request CHANGE client_devise client_devise VARCHAR(10) NOT NULL');
        $this->addSql('ALTER TABLE soa_request RENAME INDEX uniq_soa_request_numero TO UNIQ_4D4EFE8FF55AE19E');
        $this->addSql('ALTER TABLE soa_request RENAME INDEX idx_soa_request_status TO IDX_4D4EFE8F6BF700BD');
        $this->addSql('ALTER TABLE soa_request_document CHANGE type type VARCHAR(20) NOT NULL');
        $this->addSql('ALTER TABLE soa_request_document RENAME INDEX idx_soa_document_request TO IDX_256116697733964');
        $this->addSql('ALTER TABLE soa_request_product CHANGE qte_max qte_max INT NOT NULL, CHANGE montant_soa montant_soa NUMERIC(15, 2) NOT NULL, CHANGE devise devise VARCHAR(10) NOT NULL, CHANGE montant_max montant_max NUMERIC(15, 2) NOT NULL');
        $this->addSql('ALTER TABLE soa_request_product RENAME INDEX idx_soa_product_request TO IDX_8C69C4C697733964');
        $this->addSql('ALTER TABLE soa_status CHANGE color color VARCHAR(20) NOT NULL, CHANGE text_color text_color VARCHAR(20) NOT NULL, CHANGE order_index order_index INT NOT NULL');
        $this->addSql('ALTER TABLE soa_status RENAME INDEX uniq_soa_status_code TO UNIQ_7E30E55C77153098');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE soa_history DROP FOREIGN KEY FK_5163015B97733964');
        $this->addSql('DROP TABLE soa_history');
        $this->addSql('ALTER TABLE soa_request CHANGE client_devise client_devise VARCHAR(10) DEFAULT \'EUR\' NOT NULL');
        $this->addSql('CREATE INDEX idx_soa_request_client_code ON soa_request (client_code)');
        $this->addSql('CREATE INDEX idx_soa_request_representant ON soa_request (representant)');
        $this->addSql('ALTER TABLE soa_request RENAME INDEX idx_4d4efe8f6bf700bd TO idx_soa_request_status');
        $this->addSql('ALTER TABLE soa_request RENAME INDEX uniq_4d4efe8ff55ae19e TO uniq_soa_request_numero');
        $this->addSql('ALTER TABLE soa_request_document CHANGE type type VARCHAR(20) DEFAULT \'autre\' NOT NULL');
        $this->addSql('ALTER TABLE soa_request_document RENAME INDEX idx_256116697733964 TO idx_soa_document_request');
        $this->addSql('ALTER TABLE soa_request_product CHANGE qte_max qte_max INT DEFAULT 0 NOT NULL, CHANGE montant_soa montant_soa NUMERIC(15, 2) DEFAULT \'0.00\' NOT NULL, CHANGE devise devise VARCHAR(10) DEFAULT \'EUR\' NOT NULL, CHANGE montant_max montant_max NUMERIC(15, 2) DEFAULT \'0.00\' NOT NULL');
        $this->addSql('ALTER TABLE soa_request_product RENAME INDEX idx_8c69c4c697733964 TO idx_soa_product_request');
        $this->addSql('ALTER TABLE soa_status CHANGE color color VARCHAR(20) DEFAULT \'#6c757d\' NOT NULL, CHANGE text_color text_color VARCHAR(20) DEFAULT \'#ffffff\' NOT NULL, CHANGE order_index order_index INT DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE soa_status RENAME INDEX uniq_7e30e55c77153098 TO uniq_soa_status_code');
    }
}
