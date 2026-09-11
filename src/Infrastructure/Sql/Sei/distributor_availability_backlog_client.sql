-- Module Distributor Availability — backlog client filtré aux 5 distributeurs suivis
-- (PACIFIC, UMNYAMA, NIALA, GETRO, SPORTS LIFE — voir RULES.KEEPCLI côté JS de référence)
-- Alias alignés sur les noms de colonnes attendus par le moteur JS (Distributor_Availability_intranet_3.html)
SELECT
    SOH.STOFCY_0                                                          AS [SITE],
    SOH.BPCORD_0                                                          AS [N° CLIENT CMDE],
    SOH.BPCNAM_0                                                          AS [NOM CLIENT CMDE],
    SOQ.SOHNUM_0                                                          AS [N° COMMANDE],
    CASE WHEN SOH.CUSORDREF_0 <> '' THEN SOH.CUSORDREF_0 ELSE SOH.ZNORIGIN_0 END AS [REFERENCE CLIENT],
    SPLIT.ARTICLE_BASE                                                    AS [ARTICLE],
    SPLIT.VARIANT_VAL                                                     AS [VARIANT],
    ITM.ITMDES1_0                                                         AS [DESCRIPTION ARTICLE],
    CONVERT(varchar(10), SOH.ORDDAT_0, 23)                               AS [DATE COMMANDE],
    CONVERT(varchar(10), SOQ.DEMDLVDAT_0, 23)                           AS [DATE DE LIVRAISON],
    SOQ.YCOLLECT_0                                                        AS [COLLECTION],
    CAST(ROUND(SOQ.QTY_0, 0) AS INT)                                    AS [QUANTITE],
    CAST(ROUND(SOQ.ALLQTY_0, 0) AS INT)                                 AS [QUANTITE ALLOUEE],
    CAST(ROUND(SOQ.SHTQTY_0, 0) AS INT)                                 AS [QUANTITE EN RUPTURE],
    CAST(ROUND(SOQ.QTY_0 - SOQ.ALLQTY_0 - SOQ.DLVQTY_0 - SOQ.ODLQTY_0, 0) AS INT) AS [RESTE A ALLOUER]
FROM X3_LCS.SORDERQ SOQ
    INNER JOIN X3_LCS.SORDER SOH ON SOQ.SOHNUM_0 = SOH.SOHNUM_0
    INNER JOIN X3_LCS.ITMMASTER ITM ON SOQ.ITMREF_0 = ITM.ITMREF_0
    INNER JOIN X3_LCS.BPCUSTOMER BPC ON SOH.BPCORD_0 = BPC.BPCNUM_0
    CROSS APPLY (
        SELECT
            CASE WHEN CHARINDEX('_', ITM.ITMREF_0) > 0
                 THEN LEFT(ITM.ITMREF_0, CHARINDEX('_', ITM.ITMREF_0) - 1)
                 ELSE ITM.ITMREF_0
            END AS ARTICLE_BASE,
            CASE WHEN CHARINDEX('_', ITM.ITMREF_0) > 0
                 THEN SUBSTRING(ITM.ITMREF_0, CHARINDEX('_', ITM.ITMREF_0) + 1, 50)
                 ELSE NULL
            END AS VARIANT_VAL
    ) AS SPLIT
WHERE
    SOQ.SOQSTA_0 <> 3
    AND SOH.ZSOHVALSTA_0 <> 3
    AND BPC.BCGCOD_0 <> 'INTER'
    AND SOH.STOFCY_0 IN ('WLOGM', 'WSFCN', 'WDTTH', 'WTAKH', 'WCOMG')
    AND (
        SOH.BPCNAM_0 LIKE '%PACIFIC%'
        OR SOH.BPCNAM_0 LIKE '%UMNYAMA%'
        OR SOH.BPCNAM_0 LIKE '%NIALA%'
        OR SOH.BPCNAM_0 LIKE '%GETRO%'
        OR SOH.BPCNAM_0 LIKE '%SPORTS LIFE%'
    )
