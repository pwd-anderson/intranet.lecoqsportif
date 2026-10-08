-- Table "Plan transport prévision" (SEI Cube) : livraisons SS27 consolidées à l'article, importées depuis
-- l'Excel SharePoint PURCHASING (onglet « SS27 LIVRAISONS - ARTICLES »).
--
-- A exécuter une fois sur le SEI Cube, en dev ET en prod : la version _DEV est celle utilisée quand
-- APP_ENV=dev (le service ajoute le suffixe automatiquement).
--
-- Alimentation : php bin/console app:import-plan-transport (remplacement complet, transactionnel).
-- Pas de clé unique : un même PO / article apparaît plusieurs fois en cas de fractionnement
-- (transports, dates et quantités différents). Données importées telles quelles, texte du transport brut.

CREATE TABLE MASTER_TABLES.PLAN_TRANSPORT_PREVISION (
    ID               BIGINT IDENTITY(1,1) NOT NULL,
    FOURNISSEUR      NVARCHAR(150) NULL,
    NUM_PO           NVARCHAR(50)  NOT NULL,
    ARTICLE_SKU      NVARCHAR(50)  NOT NULL,   -- SKU complet, ex. 2710685_40
    ARTICLE          NVARCHAR(50)  NULL,       -- parent (avant le "_"), ex. 2710685
    VARIANT          NVARCHAR(30)  NULL,       -- variant (après le "_"), ex. 40
    DESIGNATION      NVARCHAR(255) NULL,
    QTE              INT           NULL,
    MOYEN_TRANSPORT  NVARCHAR(255) NULL,       -- texte brut du fichier
    DATE_XF          DATE          NULL,       -- départ usine
    DATE_ETD         DATE          NULL,
    DATE_ETA_FRANCE  DATE          NULL,       -- ETA France (Logtex)
    STATUT_DEPART    NVARCHAR(50)  NULL,       -- PARTI / BOOKÉ — ON HOLD / vide = à planifier
    IMPORTED_AT      DATETIME      NOT NULL,
    SOURCE_FILE      NVARCHAR(255) NULL,
    SOURCE_MODIFIED  DATETIME      NULL,
    CONSTRAINT PK_PLAN_TRANSPORT_PREVISION PRIMARY KEY CLUSTERED (ID)
);

CREATE INDEX IX_PLAN_TRANSPORT_PREVISION_ARTICLE
    ON MASTER_TABLES.PLAN_TRANSPORT_PREVISION (ARTICLE, VARIANT);


CREATE TABLE MASTER_TABLES.PLAN_TRANSPORT_PREVISION_DEV (
    ID               BIGINT IDENTITY(1,1) NOT NULL,
    FOURNISSEUR      NVARCHAR(150) NULL,
    NUM_PO           NVARCHAR(50)  NOT NULL,
    ARTICLE_SKU      NVARCHAR(50)  NOT NULL,
    ARTICLE          NVARCHAR(50)  NULL,
    VARIANT          NVARCHAR(30)  NULL,
    DESIGNATION      NVARCHAR(255) NULL,
    QTE              INT           NULL,
    MOYEN_TRANSPORT  NVARCHAR(255) NULL,
    DATE_XF          DATE          NULL,
    DATE_ETD         DATE          NULL,
    DATE_ETA_FRANCE  DATE          NULL,
    STATUT_DEPART    NVARCHAR(50)  NULL,
    IMPORTED_AT      DATETIME      NOT NULL,
    SOURCE_FILE      NVARCHAR(255) NULL,
    SOURCE_MODIFIED  DATETIME      NULL,
    CONSTRAINT PK_PLAN_TRANSPORT_PREVISION_DEV PRIMARY KEY CLUSTERED (ID)
);

CREATE INDEX IX_PLAN_TRANSPORT_PREVISION_DEV_ARTICLE
    ON MASTER_TABLES.PLAN_TRANSPORT_PREVISION_DEV (ARTICLE, VARIANT);
