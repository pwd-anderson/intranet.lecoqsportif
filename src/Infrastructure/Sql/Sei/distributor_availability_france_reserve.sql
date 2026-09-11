-- Module Distributor Availability — réserve marché France (site WLOGM)
-- Somme du "Reste à Allouer" des clients NON suivis (tout sauf les 5 distributeurs),
-- livraison prévue avant la date de coupure ({{FR_CUT_DATE}}, calculée en PHP = aujourd'hui + 3 mois).
-- Sert uniquement à savoir combien de stock Logtex A1 est déjà réservé au marché
-- domestique avant d'allouer aux 5 distributeurs — pas de détail ligne par ligne nécessaire.
SELECT
    SPLIT.ARTICLE_BASE                                                    AS [ARTICLE],
    SPLIT.VARIANT_VAL                                                     AS [VARIANT],
    SUM(CAST(ROUND(SOQ.QTY_0 - SOQ.ALLQTY_0 - SOQ.DLVQTY_0 - SOQ.ODLQTY_0, 0) AS INT)) AS [RESTE A ALLOUER]
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
    AND SOH.STOFCY_0 = 'WLOGM'
    AND CAST(ROUND(SOQ.QTY_0 - SOQ.ALLQTY_0 - SOQ.DLVQTY_0 - SOQ.ODLQTY_0, 0) AS INT) > 0
    AND (SOQ.DEMDLVDAT_0 IS NULL OR SOQ.DEMDLVDAT_0 <= '{{FR_CUT_DATE}}')
    AND NOT (
        SOH.BPCNAM_0 LIKE '%PACIFIC%'
        OR SOH.BPCNAM_0 LIKE '%UMNYAMA%'
        OR SOH.BPCNAM_0 LIKE '%NIALA%'
        OR SOH.BPCNAM_0 LIKE '%GETRO%'
        OR SOH.BPCNAM_0 LIKE '%SPORTS LIFE%'
    )
GROUP BY
    SPLIT.ARTICLE_BASE,
    SPLIT.VARIANT_VAL
