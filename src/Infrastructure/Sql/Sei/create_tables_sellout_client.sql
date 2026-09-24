-- Tables d'agregats de la stat "Sell Out par Client" (SEI Cube).
--
-- A executer une fois sur le SEI Cube. Les quatre tables sont creees d'un coup :
-- les versions _DEV sont celles utilisees quand APP_ENV=dev (le service ajoute le
-- suffixe automatiquement, cf. pattern %kernel.environment%). Sans elles, la stat
-- ne fonctionne pas en local.
--
-- Ces tables sont INDEPENDANTES de celles du dashboard Sell Out
-- (INTRANET_SELLOUT_WEEKLY / _FAMILY / _TOP_ITEMS / _BACKLOG) : grain different,
-- le dashboard ne doit pas etre impacte.
--
-- Alimentation : php bin/console app:import-sellout-clients

-- ============================================================================
-- Vue globale : une ligne par source x client x semaine  (~23 000 lignes / an)
-- ============================================================================
CREATE TABLE MASTER_TABLES.INTRANET_SELLOUT_CLIENT_WEEK (
    sourcename     NVARCHAR(50)   NULL,
    customer_id    NVARCHAR(20)   NULL,
    customer_name  NVARCHAR(100)  NULL,
    groupe_name    NVARCHAR(100)  NULL,
    ville          NVARCHAR(100)  NULL,
    rep1           NVARCHAR(100)  NULL,
    rep2           NVARCHAR(100)  NULL,
    annee          INT            NULL,
    semaine        INT            NULL,
    salesqty       DECIMAL(18, 2) NULL
);

CREATE CLUSTERED INDEX IX_SELLOUT_CLIENT_WEEK
    ON MASTER_TABLES.INTRANET_SELLOUT_CLIENT_WEEK (annee, sourcename, customer_id, semaine);

-- ============================================================================
-- Vue detail : + collection x article  (~455 000 lignes / an)
-- L'index commence par (annee, customer_id) : la stat filtre toujours sur un client.
-- ============================================================================
CREATE TABLE MASTER_TABLES.INTRANET_SELLOUT_CLIENT_ITEM_WEEK (
    sourcename     NVARCHAR(50)   NULL,
    customer_id    NVARCHAR(20)   NULL,
    customer_name  NVARCHAR(100)  NULL,
    collection     NVARCHAR(20)   NULL,
    itemn          NVARCHAR(50)   NULL,
    itemdes        NVARCHAR(100)  NULL,
    genre          NVARCHAR(50)   NULL,
    segment_offre  NVARCHAR(100)  NULL,
    annee          INT            NULL,
    semaine        INT            NULL,
    salesqty       DECIMAL(18, 2) NULL
);

CREATE CLUSTERED INDEX IX_SELLOUT_CLIENT_ITEM_WEEK
    ON MASTER_TABLES.INTRANET_SELLOUT_CLIENT_ITEM_WEEK (annee, customer_id, semaine);


-- ############################################################################
-- VERSIONS _DEV (utilisees quand APP_ENV=dev)
-- ############################################################################

-- ============================================================================
-- Vue globale (DEV) : une ligne par source x client x semaine  (~23 000 lignes / an)
-- ============================================================================
CREATE TABLE MASTER_TABLES.INTRANET_SELLOUT_CLIENT_WEEK_DEV (
    sourcename     NVARCHAR(50)   NULL,
    customer_id    NVARCHAR(20)   NULL,
    customer_name  NVARCHAR(100)  NULL,
    groupe_name    NVARCHAR(100)  NULL,
    ville          NVARCHAR(100)  NULL,
    rep1           NVARCHAR(100)  NULL,
    rep2           NVARCHAR(100)  NULL,
    annee          INT            NULL,
    semaine        INT            NULL,
    salesqty       DECIMAL(18, 2) NULL
);

CREATE CLUSTERED INDEX IX_SELLOUT_CLIENT_WEEK_DEV
    ON MASTER_TABLES.INTRANET_SELLOUT_CLIENT_WEEK_DEV (annee, sourcename, customer_id, semaine);

-- ============================================================================
-- Vue detail (DEV) : + collection x article  (~455 000 lignes / an)
-- L'index commence par (annee, customer_id) : la stat filtre toujours sur un client.
-- ============================================================================
CREATE TABLE MASTER_TABLES.INTRANET_SELLOUT_CLIENT_ITEM_WEEK_DEV (
    sourcename     NVARCHAR(50)   NULL,
    customer_id    NVARCHAR(20)   NULL,
    customer_name  NVARCHAR(100)  NULL,
    collection     NVARCHAR(20)   NULL,
    itemn          NVARCHAR(50)   NULL,
    itemdes        NVARCHAR(100)  NULL,
    genre          NVARCHAR(50)   NULL,
    segment_offre  NVARCHAR(100)  NULL,
    annee          INT            NULL,
    semaine        INT            NULL,
    salesqty       DECIMAL(18, 2) NULL
);

CREATE CLUSTERED INDEX IX_SELLOUT_CLIENT_ITEM_WEEK_DEV
    ON MASTER_TABLES.INTRANET_SELLOUT_CLIENT_ITEM_WEEK_DEV (annee, customer_id, semaine);
