<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260512140500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajout des champs editable, cell_editor, cell_editor_params à aggrid_option + renommage compatator -> comparator';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE aggrid_option CHANGE compatator comparator VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE aggrid_option ADD editable TINYINT(1) DEFAULT NULL, ADD cell_editor VARCHAR(255) DEFAULT NULL, ADD cell_editor_params JSON DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE aggrid_option DROP editable, DROP cell_editor, DROP cell_editor_params');
        $this->addSql('ALTER TABLE aggrid_option CHANGE comparator compatator VARCHAR(255) DEFAULT NULL');
    }
}
