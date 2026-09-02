<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260902120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajout statut refuse';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("INSERT IGNORE INTO soa_status (code, label, color, text_color, order_index) VALUES ('refuse', 'Refusé', '#f8d7da', '#842029', 99)");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM soa_status WHERE code = 'refuse'");
    }
}
