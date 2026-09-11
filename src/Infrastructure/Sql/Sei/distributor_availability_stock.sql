-- Module Distributor Availability — stock Logtex Moussey (WLOGM) uniquement, statut A1
-- Le moteur JS de référence n'utilise que ce site (voir USE_FR_STOCK dans le template)
SELECT
    ITMREF_0        AS [ARTICLE],
    'A1'            AS [STATUS STOCK],
    SUM(QTYSTU_0)   AS [STOCK DISPONIBLE]
FROM X3_LCS.STOJOU
WHERE
    STOFCY_0 = 'WLOGM'
    AND CAST(IPTDAT_0 AS DATE) <= CAST(GETDATE() AS DATE)
    AND STA_0 = 'A1'
GROUP BY
    ITMREF_0
