-- Ajoute les representants a la table d'agregat de la stat "Sell-Out : Ventes par Client".
-- A executer sur le SEI Cube, puis relancer : php bin/console app:import-sellout-clients
--
-- Les representants sont stockes ici plutot que joints a la lecture : ils viennent
-- d'X3 par la meme jointure BPCUSTOMER que le magasin, le groupe et la ville, et le
-- chemin de lecture reste ainsi un simple agregat sur une seule table.
--
-- Script idempotent : le relancer ne provoque aucune erreur.

IF COL_LENGTH('MASTER_TABLES.INTRANET_SELLOUT_CLIENT_WEEK', 'rep1') IS NULL
    ALTER TABLE MASTER_TABLES.INTRANET_SELLOUT_CLIENT_WEEK ADD rep1 NVARCHAR(100) NULL;
GO

IF COL_LENGTH('MASTER_TABLES.INTRANET_SELLOUT_CLIENT_WEEK', 'rep2') IS NULL
    ALTER TABLE MASTER_TABLES.INTRANET_SELLOUT_CLIENT_WEEK ADD rep2 NVARCHAR(100) NULL;
GO

IF COL_LENGTH('MASTER_TABLES.INTRANET_SELLOUT_CLIENT_WEEK_DEV', 'rep1') IS NULL
    ALTER TABLE MASTER_TABLES.INTRANET_SELLOUT_CLIENT_WEEK_DEV ADD rep1 NVARCHAR(100) NULL;
GO

IF COL_LENGTH('MASTER_TABLES.INTRANET_SELLOUT_CLIENT_WEEK_DEV', 'rep2') IS NULL
    ALTER TABLE MASTER_TABLES.INTRANET_SELLOUT_CLIENT_WEEK_DEV ADD rep2 NVARCHAR(100) NULL;
GO
