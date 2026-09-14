-- Module Distributor Availability — stock tous sites confondus, statut A1
-- (retrait du filtre WLOGM uniquement à la demande de PM le 2026-09-14 : le moteur
-- traite le stock comme un pool global par SKU, sans distinction de site — voir
-- normalizePool()/compute() dans distributor_availability.html.twig)
SELECT
    ITMREF_0        AS [ARTICLE],
    'A1'            AS [STATUS STOCK],
    SUM(QTYSTU_0)   AS [STOCK DISPONIBLE]
FROM X3_LCS.STOJOU
WHERE
    CAST(IPTDAT_0 AS DATE) <= CAST(GETDATE() AS DATE)
    AND STA_0 = 'A1'
GROUP BY
    ITMREF_0
