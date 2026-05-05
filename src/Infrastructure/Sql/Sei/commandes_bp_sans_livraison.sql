/*
   Commandes ESHOP + GLOBALE + MARK avec BP mais sans livraison
   - ESHOP + GLOBALE : date de création > 3 jours
   - MARK            : date de création > 5 jours
   Lignes non soldées uniquement
*/
SELECT
    SOH.SOHNUM_0                                        AS NO_COMMANDE,
    SOH.SOHTYP_0                                        AS TYPE_COMMANDE,
    CONVERT(varchar(10), SOH.ORDDAT_0, 23)              AS DATE_CREATION,
    DATEDIFF(DAY, SOH.ORDDAT_0, GETDATE())              AS ANCIENNETE_JOURS,
    SOH.BPCORD_0                                        AS CLIENT_COMMANDE,
    SOQ.ITMREF_0                                        AS ARTICLE,
    SOQ.QTY_0                                           AS QTE_COMMANDEE,
    SOQ.ALLQTY_0                                        AS QTE_ALLOUEE,
    SOQ.DLVQTY_0                                        AS QTE_LIVREE,
    SOQ.STOFCY_0                                        AS SITE_EXPEDITION,
    PRH.PRHNUM_0                                        AS NO_BP,
    CASE
        WHEN SOH.SOHTYP_0 IN ('ESHOP','GLOBA') THEN '> 3 jours'
        WHEN SOH.SOHTYP_0 = 'MARK'              THEN '> 5 jours'
    END                                                 AS REGLE_ANCIENNETE
FROM
    X3_LCS.SORDER SOH
    INNER JOIN X3_LCS.SORDERQ SOQ
        ON SOQ.SOHNUM_0 = SOH.SOHNUM_0
    INNER JOIN X3_LCS.STOPRED PRD
        ON  PRD.ORINUM_0 = SOQ.SOHNUM_0
        AND PRD.ORILIN_0 = SOQ.SOPLIN_0
        AND PRD.ORISEQ_0 = SOQ.SOQSEQ_0
    INNER JOIN X3_LCS.STOPREH PRH
        ON PRH.PRHNUM_0 = PRD.PRHNUM_0
WHERE
    SOQ.SOQSTA_0 <> 3

    AND SOH.SOHTYP_0 IN ('ESHOP','GLOBA','MARK')

    AND SOQ.ITMREF_0 NOT IN ('CARTECADEAU','ELT_PORT')

    AND NOT EXISTS (
        SELECT 1
        FROM X3_LCS.SDELIVERYD SDD
        WHERE SDD.SOHNUM_0 = SOQ.SOHNUM_0
          AND SDD.SOPLIN_0 = SOQ.SOPLIN_0
          AND SDD.SOQSEQ_0 = SOQ.SOQSEQ_0
    )

    AND (
        (SOH.SOHTYP_0 IN ('ESHOP','GLOBA')
            AND SOH.ORDDAT_0 < DATEADD(DAY, -3, GETDATE()))
        OR
        (SOH.SOHTYP_0 = 'MARK'
            AND SOH.ORDDAT_0 < DATEADD(DAY, -5, GETDATE()))
    );
