-- Requête ultra-allégée dédiée au module Pilotage Livraisons
-- Alias alignés sur les noms de colonnes attendus par le moteur JS
SELECT
    STOFCY_0        AS [SITE],
    ITMREF_0        AS [ARTICLE],
    'A1'            AS [STATUS STOCK],
    SUM(QTYSTU_0)   AS [STOCK INTERNE]
FROM X3_LCS.STOJOU
WHERE
    CAST(IPTDAT_0 AS DATE) <= CAST(GETDATE() AS DATE)
    AND STA_0 = 'A1'
GROUP BY
    STOFCY_0,
    ITMREF_0
