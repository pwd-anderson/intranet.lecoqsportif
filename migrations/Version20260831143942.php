<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260831143942 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Création du module SOA : soa_status, soa_request, soa_request_product, soa_request_document';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("
            CREATE TABLE soa_status (
                id          INT AUTO_INCREMENT NOT NULL,
                code        VARCHAR(50)  NOT NULL,
                label       VARCHAR(100) NOT NULL,
                color       VARCHAR(20)  NOT NULL DEFAULT '#6c757d',
                text_color  VARCHAR(20)  NOT NULL DEFAULT '#ffffff',
                order_index INT          NOT NULL DEFAULT 0,
                UNIQUE INDEX uniq_soa_status_code (code),
                PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB
        ");

        $this->addSql("
            CREATE TABLE soa_request (
                id              INT AUTO_INCREMENT NOT NULL,
                status_id       INT          NOT NULL,
                numero          VARCHAR(20)  NOT NULL,
                titre           VARCHAR(255) NOT NULL,
                representant    VARCHAR(150) NOT NULL,
                client_code     VARCHAR(50)  NOT NULL,
                client_nom      VARCHAR(255) NOT NULL,
                client_langue   VARCHAR(10)  NOT NULL,
                client_devise   VARCHAR(10)  NOT NULL DEFAULT 'EUR',
                client_emails   JSON         NOT NULL,
                date_debut      DATE         NOT NULL,
                date_fin        DATE         NOT NULL,
                focus_produit   LONGTEXT     DEFAULT NULL,
                commentaire     LONGTEXT     DEFAULT NULL,
                created_at      DATETIME     NOT NULL,
                updated_at      DATETIME     NOT NULL,
                UNIQUE INDEX uniq_soa_request_numero (numero),
                INDEX idx_soa_request_status (status_id),
                INDEX idx_soa_request_representant (representant),
                INDEX idx_soa_request_client_code (client_code),
                PRIMARY KEY (id),
                CONSTRAINT fk_soa_request_status FOREIGN KEY (status_id) REFERENCES soa_status (id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB
        ");

        $this->addSql("
            CREATE TABLE soa_request_product (
                id               INT AUTO_INCREMENT NOT NULL,
                soa_request_id   INT             NOT NULL,
                article_code     VARCHAR(50)     NOT NULL,
                article_nom      VARCHAR(255)    NOT NULL,
                prix_achat       DECIMAL(15,2)   DEFAULT NULL,
                qte_max          INT             NOT NULL DEFAULT 0,
                montant_soa      DECIMAL(15,2)   NOT NULL DEFAULT '0.00',
                devise           VARCHAR(10)     NOT NULL DEFAULT 'EUR',
                montant_max      DECIMAL(15,2)   NOT NULL DEFAULT '0.00',
                ca_facture_annee DECIMAL(15,2)   DEFAULT NULL,
                roi              DECIMAL(8,2)    DEFAULT NULL,
                qte_vendue       INT             DEFAULT NULL,
                INDEX idx_soa_product_request (soa_request_id),
                PRIMARY KEY (id),
                CONSTRAINT fk_soa_product_request FOREIGN KEY (soa_request_id) REFERENCES soa_request (id) ON DELETE CASCADE
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB
        ");

        $this->addSql("
            CREATE TABLE soa_request_document (
                id             INT AUTO_INCREMENT NOT NULL,
                soa_request_id INT          NOT NULL,
                type           VARCHAR(20)  NOT NULL DEFAULT 'autre',
                nom_fichier    VARCHAR(255) NOT NULL,
                chemin         VARCHAR(500) NOT NULL,
                mime_type      VARCHAR(100) DEFAULT NULL,
                taille         INT          DEFAULT NULL,
                uploaded_by    VARCHAR(150) NOT NULL,
                uploaded_at    DATETIME     NOT NULL,
                INDEX idx_soa_document_request (soa_request_id),
                PRIMARY KEY (id),
                CONSTRAINT fk_soa_document_request FOREIGN KEY (soa_request_id) REFERENCES soa_request (id) ON DELETE CASCADE
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB
        ");

        // Données initiales des 6 statuts
        $this->addSql("
            INSERT INTO soa_status (code, label, color, text_color, order_index) VALUES
            ('brouillon',          'Brouillon',                        '#6c757d', '#ffffff', 1),
            ('attente_validation', 'En attente de validation',         '#fd7e14', '#ffffff', 2),
            ('valide_direction',   'Validé par la Direction',          '#0d6efd', '#ffffff', 3),
            ('attente_preuves',    'En attente de preuves',            '#6f42c1', '#ffffff', 4),
            ('attente_val_finale', 'En attente de validation finale',  '#fd7e14', '#ffffff', 5),
            ('archive',            'Archivé',                          '#198754', '#ffffff', 6)
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS soa_request_document');
        $this->addSql('DROP TABLE IF EXISTS soa_request_product');
        $this->addSql('DROP TABLE IF EXISTS soa_request');
        $this->addSql('DROP TABLE IF EXISTS soa_status');
    }
}
