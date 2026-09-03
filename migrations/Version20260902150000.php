<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260902150000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'SOA : suppression colonnes XML de soa_request + ajout soa_request_id sur sales_web_service';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE soa_request DROP COLUMN xml_payload, DROP COLUMN xml_sent_at, DROP COLUMN erp_document_id');
        $this->addSql('ALTER TABLE sales_web_service ADD soa_request_id INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE sales_web_service DROP COLUMN soa_request_id');
        $this->addSql('ALTER TABLE soa_request ADD xml_payload LONGTEXT DEFAULT NULL, ADD xml_sent_at DATETIME DEFAULT NULL, ADD erp_document_id VARCHAR(100) DEFAULT NULL');
    }
}
