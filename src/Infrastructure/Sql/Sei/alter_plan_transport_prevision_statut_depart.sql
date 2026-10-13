-- Plan transport prévision : la colonne « Statut départ » du fichier contient parfois un commentaire libre
-- (jusqu'à 80 caractères constatés le 2026-10-13, ex. « Nouveau PO — 34 pcs sorties du … ») : élargie de 50 à 255.
-- A exécuter une fois sur le SEI Cube, en dev ET en prod (les tables créées avant cette date ont NVARCHAR(50)).
ALTER TABLE MASTER_TABLES.PLAN_TRANSPORT_PREVISION     ALTER COLUMN STATUT_DEPART NVARCHAR(255) NULL;
ALTER TABLE MASTER_TABLES.PLAN_TRANSPORT_PREVISION_DEV ALTER COLUMN STATUT_DEPART NVARCHAR(255) NULL;
