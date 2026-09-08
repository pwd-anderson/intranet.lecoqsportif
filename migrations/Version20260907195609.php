<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260907195609 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Création du module MDF : mdf_status, mdf_request, mdf_request_document, mdf_history + mdf_request_id sur sales_web_service';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("
            CREATE TABLE mdf_status (
                id          INT AUTO_INCREMENT NOT NULL,
                code        VARCHAR(50)  NOT NULL,
                label       VARCHAR(100) NOT NULL,
                color       VARCHAR(20)  NOT NULL DEFAULT '#6c757d',
                text_color  VARCHAR(20)  NOT NULL DEFAULT '#ffffff',
                order_index INT          NOT NULL DEFAULT 0,
                UNIQUE INDEX uniq_mdf_status_code (code),
                PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB
        ");

        $this->addSql("
            CREATE TABLE mdf_request (
                id                        INT AUTO_INCREMENT NOT NULL,
                status_id                 INT           NOT NULL,
                numero                    VARCHAR(20)   NOT NULL,
                titre                     VARCHAR(255)  NOT NULL,
                representant              VARCHAR(150)  NOT NULL,
                client_code               VARCHAR(50)   NOT NULL,
                client_nom                VARCHAR(255)  NOT NULL,
                client_langue             VARCHAR(10)   NOT NULL,
                client_devise             VARCHAR(10)   NOT NULL DEFAULT 'EUR',
                client_emails             JSON          NOT NULL,
                date_debut                DATE          NOT NULL,
                date_fin                  DATE          NOT NULL,
                type_activite             VARCHAR(100)  NOT NULL,
                montant_mdf               NUMERIC(15,2) NOT NULL DEFAULT '0.00',
                montant_facture           NUMERIC(15,2) DEFAULT NULL,
                montant_backlog_client    NUMERIC(15,2) DEFAULT NULL,
                montant_mdf_total_client  NUMERIC(15,2) DEFAULT NULL,
                roi                       NUMERIC(8,2)  DEFAULT NULL,
                roi_total                 NUMERIC(8,2)  DEFAULT NULL,
                focus_produit             LONGTEXT      DEFAULT NULL,
                audience                  LONGTEXT      DEFAULT NULL,
                commentaire               LONGTEXT      DEFAULT NULL,
                created_at                DATETIME      NOT NULL,
                updated_at                DATETIME      NOT NULL,
                UNIQUE INDEX uniq_mdf_request_numero (numero),
                INDEX idx_mdf_request_status (status_id),
                PRIMARY KEY (id),
                CONSTRAINT fk_mdf_request_status FOREIGN KEY (status_id) REFERENCES mdf_status (id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB
        ");

        $this->addSql("
            CREATE TABLE mdf_request_document (
                id             INT AUTO_INCREMENT NOT NULL,
                mdf_request_id INT          NOT NULL,
                type           VARCHAR(20)  NOT NULL DEFAULT 'autre',
                nom_fichier    VARCHAR(255) NOT NULL,
                chemin         VARCHAR(500) NOT NULL,
                mime_type      VARCHAR(100) DEFAULT NULL,
                taille         INT          DEFAULT NULL,
                uploaded_by    VARCHAR(150) NOT NULL,
                uploaded_at    DATETIME     NOT NULL,
                INDEX idx_mdf_document_request (mdf_request_id),
                PRIMARY KEY (id),
                CONSTRAINT fk_mdf_document_request FOREIGN KEY (mdf_request_id) REFERENCES mdf_request (id) ON DELETE CASCADE
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB
        ");

        $this->addSql("
            CREATE TABLE mdf_history (
                id             INT AUTO_INCREMENT NOT NULL,
                mdf_request_id INT          NOT NULL,
                user           VARCHAR(150) NOT NULL,
                statut         VARCHAR(50)  NOT NULL,
                statut_label   VARCHAR(255) NOT NULL,
                created_at     DATETIME     NOT NULL,
                INDEX idx_mdf_history_request (mdf_request_id),
                PRIMARY KEY (id),
                CONSTRAINT fk_mdf_history_request FOREIGN KEY (mdf_request_id) REFERENCES mdf_request (id) ON DELETE CASCADE
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB
        ");

        $this->addSql("
            INSERT INTO mdf_status (code, label, color, text_color, order_index) VALUES
            ('brouillon',          'Brouillon',                        '#6c757d', '#ffffff', 1),
            ('attente_validation', 'En attente de validation',         '#fd7e14', '#ffffff', 2),
            ('valide_direction',   'Validé par la Direction',          '#0d6efd', '#ffffff', 3),
            ('attente_val_finale', 'En attente de validation finale',  '#fd7e14', '#ffffff', 4),
            ('archive',            'Archivé',                          '#198754', '#ffffff', 5),
            ('refuse',             'Refusé',                           '#f8d7da', '#842029', 99)
        ");

        $this->addSql('ALTER TABLE sales_web_service ADD mdf_request_id INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE sales_web_service DROP COLUMN mdf_request_id');
        $this->addSql('DROP TABLE IF EXISTS mdf_history');
        $this->addSql('DROP TABLE IF EXISTS mdf_request_document');
        $this->addSql('DROP TABLE IF EXISTS mdf_request');
        $this->addSql('DROP TABLE IF EXISTS mdf_status');
    }
}
