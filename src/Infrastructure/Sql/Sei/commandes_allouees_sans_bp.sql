/*
   Commandes ESHOP + GLOBALE + MARK qui sont allouées ou partiellement allouées
   sans Bon de Préparation (avec indication de la date de création)
*/
SELECT
    SOH.SOHNUM_0                          AS NO_COMMANDE,
    SOH.SOHTYP_0                          AS TYPE_COMMANDE,
    CONVERT(varchar(10), SOH.ORDDAT_0, 23)  AS DATE_CREATION,
    SOH.BPCORD_0                          AS CLIENT_COMMANDE,
    SOQ.ITMREF_0                          AS ARTICLE,
    SOQ.QTY_0                             AS QTE_COMMANDEE,
    SOQ.ALLQTY_0                          AS QTE_ALLOUEE,
    SOQ.DLVQTY_0                          AS QTE_LIVREE,
    SOQ.STOFCY_0                          AS SITE_EXPEDITION,
    CASE
        WHEN SOQ.ALLQTY_0 >= SOQ.QTY_0 THEN 'Totalement allouée'
        WHEN SOQ.ALLQTY_0 > 0 AND SOQ.ALLQTY_0 < SOQ.QTY_0 THEN 'Partiellement allouée'
        ELSE 'Non allouée'
    END                                   AS STATUT_ALLOCATION
FROM
    X3_LCS.SORDER SOH
    INNER JOIN X3_LCS.SORDERQ SOQ ON SOQ.SOHNUM_0 = SOH.SOHNUM_0
WHERE
    SOQ.ALLQTY_0 > 0

    AND NOT EXISTS (
        SELECT 1
        FROM X3_LCS.STOPRED prl
        WHERE prl.ORINUM_0 = SOQ.SOHNUM_0
          AND prl.ORILIN_0 = SOQ.SOPLIN_0
          AND prl.ORISEQ_0 = SOQ.SOQSEQ_0
    )

    AND SOQ.SOQSTA_0 <> 3

    AND SOH.SOHTYP_0 IN ('ESHOP','GLOBA','MARK');
