-- Remises et conditions de paiement : remise de la saison précédente (FW26) et de la saison en cours (SS27) par client.
-- Source : vue MASTER_TABLES.PRODXXX_CLIENT_INTRANE (celle de la stat Comptes Clients, remplace l'Excel manuel).
-- Une colonne par saison : à compléter à chaque nouvelle saison.
SELECT
    V.CODE_CLIENT,
    V.REMISE_2026_02_FW         AS REMISE_SAISON_PRECEDENTE,
    V.REMISE_2027_01_SS         AS REMISE_SAISON_COURANTE,
    V.LIBELLE_CONDITION_PAIEMENT AS CONDITION_PAIEMENT
FROM MASTER_TABLES.PRODXXX_CLIENT_INTRANE V
WHERE V.REMISE_2026_02_FW IS NOT NULL OR V.REMISE_2027_01_SS IS NOT NULL;
