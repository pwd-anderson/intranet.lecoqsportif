-- Table d'agregat "Facturation client par plage" (SEI Cube).
--
-- A executer une fois sur le SEI Cube, en dev ET en prod : la version _DEV est celle
-- utilisee quand APP_ENV=dev (le service ajoute le suffixe automatiquement, cf. pattern
-- %kernel.environment%). Sans elle, la stat Comptes Clients ne fonctionne pas en local.
--
-- Remplace les trois colonnes FACTURE_*_MOIS calculees dans la vue
-- MASTER_TABLES.PRODXXX_CLIENT_INTRANE, qui ne voyaient que les tables X3 brutes :
-- celles-ci ne contiennent des factures qu'a partir d'avril 2026, ce qui rendait les
-- plages 12-18 et 18-24 mois systematiquement vides. Le cube porte l'historique complet.
--
-- Alimentation : php bin/console app:import-facturation-client

CREATE TABLE MASTER_TABLES.INTRANET_FACTURATION_CLIENT (
    code_client         NVARCHAR(20) NOT NULL,
    facture_0_12_mois   NVARCHAR(3)  NULL,
    facture_12_18_mois  NVARCHAR(3)  NULL,
    facture_18_24_mois  NVARCHAR(3)  NULL,
    date_maj            DATETIME     NULL
);

CREATE CLUSTERED INDEX IX_FACTURATION_CLIENT
    ON MASTER_TABLES.INTRANET_FACTURATION_CLIENT (code_client);


CREATE TABLE MASTER_TABLES.INTRANET_FACTURATION_CLIENT_DEV (
    code_client         NVARCHAR(20) NOT NULL,
    facture_0_12_mois   NVARCHAR(3)  NULL,
    facture_12_18_mois  NVARCHAR(3)  NULL,
    facture_18_24_mois  NVARCHAR(3)  NULL,
    date_maj            DATETIME     NULL
);

CREATE CLUSTERED INDEX IX_FACTURATION_CLIENT_DEV
    ON MASTER_TABLES.INTRANET_FACTURATION_CLIENT_DEV (code_client);
