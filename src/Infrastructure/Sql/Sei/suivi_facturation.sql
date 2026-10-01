-- Suivi Facturation (Ventes) : quantité facturée, montant EUR et nombre de clients
-- distincts, croisés par Commercial N+1 / Groupement / Famille / Année de
-- facturation / Type de commande. Alimente le Row Grouping AG Grid côté navigateur
-- (aucun pivot PHP nécessaire).
--
-- CUSTOMER_COUNT à 5 niveaux : un COUNT(DISTINCT) brut par type de commande ne peut
-- pas être simplement additionné pour obtenir le nombre de clients distincts aux
-- niveaux supérieurs (un même client achetant sous plusieurs types de commande / familles
-- serait compté plusieurs fois — vérifié en direct : 7 par simple somme contre 3 en vrai
-- distinct sur un cas réel). Solution : marquer la première occurrence de chaque client
-- à chaque niveau via ROW_NUMBER() partitionné, puis sommer ces marqueurs en fenêtre pour
-- obtenir un vrai distinct répété sur chaque ligne du niveau (MAX() le récupère sans
-- jamais le gonfler, contrairement à SUM()). Re-vérifié en direct après implémentation :
-- CAILLEAU -> CUSTOMER_COUNT_REP_GRP = 3 sur toutes ses lignes, cohérent avec le calcul
-- manuel de référence.
--
-- Filtre CUST.CUSTOMERGROUPCODE = 'INTERSPORT' volontairement conservé : cette stat
-- est scopée sur ce groupe client (décision utilisateur).

WITH base AS (   -- grain client (CUSTOMERNO reste interne, jamais renvoyé au navigateur)
    SELECT
        CUST.SALESMANNAMENPLUS1,
        CUST.INDEPENDANTGROUPMENT,
        C.ITEMFAMILYCODE,
        YEAR(S.EXPECTEDINVOICINGDATE) AS INVOICING_POSTING_DATE,
        S.SALESORDERTYPE,
        S.CUSTOMERNO,
        SUM(S.INV_QUANTITY)  AS INV_QUANTITY,
        SUM(S.INV_AMOUNTEUR) AS INV_AMOUNTEUR
    FROM SEI_X3_LCS.LCS_X3SALESX S
    LEFT JOIN SEI_X3_LCS.LCS_COLLECTION C
        ON  S.ITEMNO   = C.ITEM_ID
        AND S.SERIESNO = C.SERIESCODE
    LEFT JOIN SEI_X3_LCS.LCS_CUSTOMER CUST
        ON  S.COMPANYCODE = CUST.COMPANY_ID
        AND S.CUSTOMERNO  = CUST.CUSTOMER_ID
    WHERE YEAR(S.EXPECTEDINVOICINGDATE) = {{YEAR}}
    --AND CUST.INDEPENDANTGROUPMENT = 'SCHIEVER'
    AND C.ITEMFAMILYCODE IN ('FTW', 'HDW', 'APL')
    AND CUST.CUSTOMERGROUPCODE = 'INTERSPORT'
    GROUP BY
        CUST.SALESMANNAMENPLUS1,
        CUST.INDEPENDANTGROUPMENT,
        C.ITEMFAMILYCODE,
        YEAR(S.EXPECTEDINVOICINGDATE),
        S.SALESORDERTYPE,
        S.CUSTOMERNO
),
rn AS (          -- 1ere occurrence de chaque client a chaque niveau
    SELECT b.*,
        ROW_NUMBER() OVER (PARTITION BY SALESMANNAMENPLUS1, CUSTOMERNO
                           ORDER BY (SELECT NULL)) AS rn1,
        ROW_NUMBER() OVER (PARTITION BY SALESMANNAMENPLUS1, INDEPENDANTGROUPMENT, CUSTOMERNO
                           ORDER BY (SELECT NULL)) AS rn2,
        ROW_NUMBER() OVER (PARTITION BY SALESMANNAMENPLUS1, INDEPENDANTGROUPMENT, ITEMFAMILYCODE, CUSTOMERNO
                           ORDER BY (SELECT NULL)) AS rn3,
        ROW_NUMBER() OVER (PARTITION BY SALESMANNAMENPLUS1, INDEPENDANTGROUPMENT, ITEMFAMILYCODE,
                                        INVOICING_POSTING_DATE, CUSTOMERNO
                           ORDER BY (SELECT NULL)) AS rn4
    FROM base b
),
cnt AS (         -- customer count des niveaux 1 a 4
    SELECT r.*,
        SUM(CASE WHEN CUSTOMERNO IS NOT NULL AND rn1 = 1 THEN 1 ELSE 0 END)
            OVER (PARTITION BY SALESMANNAMENPLUS1) AS cc_rep,
        SUM(CASE WHEN CUSTOMERNO IS NOT NULL AND rn2 = 1 THEN 1 ELSE 0 END)
            OVER (PARTITION BY SALESMANNAMENPLUS1, INDEPENDANTGROUPMENT) AS cc_rep_grp,
        SUM(CASE WHEN CUSTOMERNO IS NOT NULL AND rn3 = 1 THEN 1 ELSE 0 END)
            OVER (PARTITION BY SALESMANNAMENPLUS1, INDEPENDANTGROUPMENT, ITEMFAMILYCODE) AS cc_rep_grp_fam,
        SUM(CASE WHEN CUSTOMERNO IS NOT NULL AND rn4 = 1 THEN 1 ELSE 0 END)
            OVER (PARTITION BY SALESMANNAMENPLUS1, INDEPENDANTGROUPMENT, ITEMFAMILYCODE,
                               INVOICING_POSTING_DATE) AS cc_rep_grp_fam_annee
    FROM rn r
)
SELECT
    SALESMANNAMENPLUS1,
    INDEPENDANTGROUPMENT,
    ITEMFAMILYCODE,
    INVOICING_POSTING_DATE,
    SALESORDERTYPE,
    SUM(INV_QUANTITY)            AS INV_QUANTITY,
    SUM(INV_AMOUNTEUR)           AS INV_AMOUNTEUR,
    MAX(cc_rep)                  AS CUSTOMER_COUNT_REP,
    MAX(cc_rep_grp)              AS CUSTOMER_COUNT_REP_GRP,
    MAX(cc_rep_grp_fam)          AS CUSTOMER_COUNT_REP_GRP_FAM,
    MAX(cc_rep_grp_fam_annee)    AS CUSTOMER_COUNT_REP_GRP_FAM_ANNEE,
    COUNT(DISTINCT CUSTOMERNO)   AS CUSTOMER_COUNT_REP_GRP_FAM_ANNEE_TYPE
FROM cnt
GROUP BY
    SALESMANNAMENPLUS1,
    INDEPENDANTGROUPMENT,
    ITEMFAMILYCODE,
    INVOICING_POSTING_DATE,
    SALESORDERTYPE
