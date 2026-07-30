WITH sto_agg AS (
    SELECT
        STOFCY_0,
        ITMREF_0,
        STA_0,
        SUM(QTYSTU_0) AS STOCK_INTERNE,
        SUM(PRIVAL_0 * QTYSTU_0) AS VALORISATION_STOCK_INTERNE
    FROM X3_LCS.STOJOU
    WHERE CAST(IPTDAT_0 AS DATE) <= CAST(GETDATE() AS DATE)
    GROUP BY STOFCY_0, ITMREF_0, STA_0
),

CollectionRecente AS (
    SELECT
        YIL.ITMREF_0,
        YIL.YCOLLECT_0,
        YCO.YDATDEB_0,
        ROW_NUMBER() OVER (
            PARTITION BY YIL.ITMREF_0
            ORDER BY YCO.YDATDEB_0 DESC
        ) AS rn
    FROM X3_LCS.YITMCOLLECT YIL
    INNER JOIN X3_LCS.YCOLLECTION YCO
        ON YIL.YCOLLECT_0 = YCO.YCOLLECT_0
    LEFT JOIN X3_LCS.ZITMCOL ITC
        ON ITC.ITMREF_0 = LEFT(YIL.ITMREF_0, CHARINDEX('_', YIL.ITMREF_0 + '_') - 1)
        AND ITC.YCOLLECT_0 = YCO.YCOLLECT_0
    WHERE ISNULL(ITC.ZDROPPED_0, 0) <> 2
)

SELECT
    'https://www.lecoqsportif.com/cdn/shop/files/' + LEFT(i.ITMREF_0, CHARINDEX('_', i.ITMREF_0 + '_') - 1) + '_2.jpg' AS PHOTO,
    CR.YCOLLECT_0                                                                   AS DERNIERE_COLLECTION,
    i.TCLCOD_0                                                                      AS FAMILLE,
    LEFT(i.ITMREF_0, CHARINDEX('_', i.ITMREF_0 + '_') - 1)                         AS ARTICLE_BASE,
    i.ITMREF_0                                                                      AS ARTICLE,
    i.ITMDES1_0                                                                     AS DESCRIPTION_ARTICLE,
    SUBSTRING(i.ITMREF_0, CHARINDEX('_', i.ITMREF_0 + '_') + 1, LEN(i.ITMREF_0))  AS VARIANT,
    CASE WHEN i2.ZSHOPIFY_0 = 1 THEN 'NON' ELSE 'OUI' END AS FLAG_SHOPIFY,
    CASE WHEN i2.ZPRICETYPE_0 = 0 THEN '' ELSE
        CASE WHEN i2.ZPRICETYPE_0 = 1 THEN 'BOUTIQUE' ELSE 'OUTLET' END END AS CANAL_PRIX,
    s.STA_0                                                                         AS STATUS_STOCK,
    (s.STOCK_INTERNE - ISNULL(STK.CUMALLQTY_0, 0))
        - CASE WHEN s.STA_0 = 'A2' THEN ISNULL(ITV.GLOALL_0, 0) ELSE 0 END        AS STOCK_REEL,
    'EUR'                                                                           AS DEVISE_SRP,
    ISNULL(ITS.BASPRI_0, 0)                                                        AS PRIX_SRP,
    'EUR'                                                                           AS DEVISE_BOUTIQUE,
    ISNULL(SPLT11.PRI_0, 0)                                                        AS PRIX_BOUTIQUE,
    'EUR'                                                                           AS DEVISE_OUTLET,
    ISNULL(SPLT11FO.PRI_0, 0)                                                      AS PRIX_OUTLET
FROM sto_agg s
LEFT JOIN X3_LCS.STOCK STK
    ON s.ITMREF_0 = STK.ITMREF_0
    AND s.STOFCY_0 = STK.STOFCY_0
    AND s.STA_0 = STK.STA_0
LEFT JOIN X3_LCS.ITMMVT ITV
    ON s.ITMREF_0 = ITV.ITMREF_0
    AND s.STOFCY_0 = ITV.STOFCY_0
LEFT JOIN X3_LCS.FACILITY f
    ON s.STOFCY_0 = f.FCY_0
LEFT JOIN X3_LCS.ITMMASTER i
    ON s.ITMREF_0 = i.ITMREF_0
LEFT JOIN X3_LCS.ITMMASTER i2
    ON LEFT(s.ITMREF_0, CHARINDEX('_', s.ITMREF_0 + '_') - 1) = i2.ITMREF_0
LEFT JOIN CollectionRecente CR
    ON i.ITMREF_0 = CR.ITMREF_0
    AND CR.rn = 1
LEFT JOIN X3_LCS.ITMSALES ITS
    ON i.ITMREF_0 = ITS.ITMREF_0
LEFT JOIN X3_LCS.SPRICLIST AS SPLT11
    ON i.ZMODELCOD_0 = SPLT11.PLICRI3_0
    AND SPLT11.PLICRI2_0 = CR.YCOLLECT_0
    AND SPLT11.PLI_0 = 'T11'
    AND SPLT11.PLICRI1_0 = 'CSEU'
LEFT JOIN X3_LCS.SPRICLIST AS SPLT11FO
    ON i.ZMODELCOD_0 = SPLT11FO.PLICRI3_0
    AND SPLT11FO.PLICRI2_0 = CR.YCOLLECT_0
    AND SPLT11FO.PLI_0 = 'T11'
    AND SPLT11FO.PLICRI1_0 = 'FOEU'
WHERE s.STOFCY_0 = 'WLOGM'
  AND s.STA_0 = 'A1'
AND ((s.STOCK_INTERNE - ISNULL(STK.CUMALLQTY_0,0)) - CASE WHEN s.STA_0 = 'A2' THEN ISNULL(ITV.GLOALL_0,0) ELSE 0 END) <> 0
ORDER BY i.TCLCOD_0, CR.YCOLLECT_0, i.ITMREF_0
