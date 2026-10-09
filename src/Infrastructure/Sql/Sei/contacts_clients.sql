-- Stat Contacts Clients (ADV) : pour chaque client, ses contacts pour les 5 fonctions suivies
-- (codes de fonction X3 13, 14, 18, 19 et 20).
--
-- Le CROSS JOIN sur les 5 fonctions garantit UNE ligne au moins par client et par fonction, meme sans
-- contact : LIBELLE_FONCTION est alors renseigne (jointure sur FNC.CNTFNC et non sur CNT.CNTFNC_0, qui est
-- NULL sans contact) et les colonnes du contact restent vides. Le code de fonction n'est pas affiche. Un client ayant
-- plusieurs contacts pour une meme fonction ressort sur plusieurs lignes.
--
-- Tous les clients sont renvoyes (actifs ET inactifs) : la colonne STATUT_CLIENT permet de filtrer.
SELECT
    REP1.REPNAM_0        AS REPRESENTANT_1,
    REP2.REPNAM_0        AS REPRESENTANT_2,
    ATX32.TEXTE_0        AS BUSINESS_MODEL_1,
    ATX6021.TEXTE_0      AS GROUPEMENT_INDEPENDANT,

    BPC.BPCNUM_0         AS CODE_CLIENT,
    BPC.BPCNAM_0         AS NOM_CLIENT,
    CASE
        WHEN BPC.BPCSTA_0 = 2 THEN 'Actif'
        WHEN BPC.BPCSTA_0 = 1 THEN 'Inactif'
        ELSE 'Non défini'
    END AS STATUT_CLIENT,

    LST.LANMES_0         AS LIBELLE_FONCTION,

    CNT.CCNCRM_0         AS CODE_CONTACT,
    CRM.CNTLNA_0         AS NOM_CONTACT,
    CRM.CNTFNA_0         AS PRENOM_CONTACT,
    CNT.TEL_0            AS TEL_CONTACT,
    CNT.WEB_0            AS EMAIL

FROM X3_LCS.BPCUSTOMER BPC

CROSS JOIN (VALUES (13), (14), (18), (19), (20)) AS FNC(CNTFNC)

LEFT JOIN X3_LCS.CONTACT CNT
       ON CNT.BPANUM_0  = BPC.BPCNUM_0
      AND CNT.CNTFNC_0  = FNC.CNTFNC

LEFT JOIN X3_LCS.CONTACTCRM CRM ON CRM.CNTNUM_0 = CNT.CCNCRM_0
LEFT JOIN X3_LCS.SALESREP REP1  ON BPC.REP_0 = REP1.REPNUM_0
LEFT JOIN X3_LCS.SALESREP REP2  ON BPC.REP_1 = REP2.REPNUM_0

-- Alias ATEXTRA : ATX6021 = INDEPENDANT_GROUPMENT (ZGROUPIND_0, IDENT1=6021), ATX32 = business model 1
-- (TSCCOD_2, IDENT1=32). Ne pas reutiliser ces alias pour autre chose (convention du projet).
LEFT JOIN X3_LCS.ATEXTRA ATX6021
       ON ATX6021.CODFIC_0 = 'ATABDIV'
      AND ATX6021.ZONE_0   = 'LNGDES'
      AND ATX6021.LANGUE_0 = 'FRA'
      AND ATX6021.IDENT1_0 = '6021'
      AND ATX6021.IDENT2_0 = BPC.ZGROUPIND_0
LEFT JOIN X3_LCS.ATEXTRA ATX32
       ON ATX32.CODFIC_0 = 'ATABDIV'
      AND ATX32.ZONE_0   = 'LNGDES'
      AND ATX32.LANGUE_0 = 'FRA'
      AND ATX32.IDENT1_0 = '32'
      AND ATX32.IDENT2_0 = BPC.TSCCOD_2

-- Libelle de la fonction : table des menus locaux (APLSTD), chapitre 233, langue francaise.
LEFT JOIN X3_LCS.APLSTD LST
       ON LST.LANCHP_0 = 233
      AND LST.LANNUM_0 = FNC.CNTFNC
      AND LST.LAN_0    = 'FRA'
