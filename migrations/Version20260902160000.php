<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260902160000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'SOA : ajout soa_request_id sur sales_web_service';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE sales_web_service ADD soa_request_id INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE sales_web_service DROP COLUMN soa_request_id');
    }
}
