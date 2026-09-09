-- Requête allégée dédiée au module Pilotage Livraisons (moteur V2)
-- Alias alignés sur les noms de colonnes attendus par le moteur JS
-- STOCK DISPONIBLE et DERNIERE COLLECTION repris de la logique stock_allocation.sql
WITH sto_agg AS (
    SELECT
        STOFCY_0,
        ITMREF_0,
        STA_0,
        SUM(QTYSTU_0) AS STOCK_INTERNE
    FROM X3_LCS.STOJOU
    WHERE CAST(IPTDAT_0 AS DATE) <= CAST(GETDATE() AS DATE)
      AND STA_0 = 'A1'
    GROUP BY STOFCY_0, ITMREF_0, STA_0
),

CollectionRecente AS (
    SELECT
        YIL.ITMREF_0,
        YIL.YCOLLECT_0,
        ROW_NUMBER() OVER (
            PARTITION BY YIL.ITMREF_0
            ORDER BY YCO.YDATDEB_0 DESC
        ) AS rn
    FROM X3_LCS.YITMCOLLECT YIL
        INNER JOIN X3_LCS.YCOLLECTION YCO ON YIL.YCOLLECT_0 = YCO.YCOLLECT_0
        LEFT  JOIN X3_LCS.ZITMCOL ITC ON ITC.ITMREF_0   = LEFT(YIL.ITMREF_0, CHARINDEX('_', YIL.ITMREF_0 + '_') - 1)
                                      AND ITC.YCOLLECT_0 = YCO.YCOLLECT_0
    WHERE ISNULL(ITC.ZDROPPED_0, 0) <> 2
)

SELECT
    s.STOFCY_0                                          AS [SITE],
    s.ITMREF_0                                          AS [ARTICLE],
    s.STA_0                                              AS [STATUS STOCK],
    s.STOCK_INTERNE                                      AS [STOCK INTERNE],
    s.STOCK_INTERNE - ISNULL(STK.CUMALLQTY_0, 0)        AS [STOCK DISPONIBLE],
    CR.YCOLLECT_0                                        AS [COLLECTION]
FROM sto_agg s
    LEFT JOIN X3_LCS.STOCK STK ON s.ITMREF_0 = STK.ITMREF_0
                               AND s.STOFCY_0 = STK.STOFCY_0
                               AND s.STA_0    = STK.STA_0
    LEFT JOIN CollectionRecente CR ON s.ITMREF_0 = CR.ITMREF_0
                                   AND CR.rn = 1
