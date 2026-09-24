<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Table des demandes d'export volumineux generes en tache de fond (export_job).
 *
 * Migration nettoyee a la main : la generation automatique voulait aussi supprimer
 * les tables SOA, MDF et Price Protection, qui existent en base locale mais dont les
 * entites vivent sur d'autres branches. Ces instructions ont ete retirees.
 */
final class Version20260923163541 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute la table export_job (exports volumineux generes en tache de fond)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE export_job (
                id INT AUTO_INCREMENT NOT NULL,
                user_id INT DEFAULT NULL,
                stat_key VARCHAR(60) NOT NULL,
                payload JSON NOT NULL,
                statut VARCHAR(20) NOT NULL,
                fichier VARCHAR(255) DEFAULT NULL,
                lignes INT DEFAULT NULL,
                taille INT DEFAULT NULL,
                erreur LONGTEXT DEFAULT NULL,
                cree_le DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
                termine_le DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
                INDEX IDX_93DEA7C8A76ED395 (user_id),
                INDEX idx_export_job_statut (statut),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE export_job
                ADD CONSTRAINT FK_93DEA7C8A76ED395
                FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE SET NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE export_job DROP FOREIGN KEY FK_93DEA7C8A76ED395');
        $this->addSql('DROP TABLE export_job');
    }
}
