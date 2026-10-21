-- Étiquettes d'expédition : lignes d'un SHIPMENT (X3_LCS.SHIPMENTD), à la place de l'ancienne recherche par commande d'achat.
-- Une ligne par article du shipment ; la quantité est le reste à recevoir (expédié - reçu), les lignes soldées (écart nul)
-- sont ignorées. Aliases alignés sur la requête livraison (etiquette_expedition.sql), sauf le numéro (N_SHIPMENT).
-- {{NUMERO_SHIPMENT}} est remplacé côté PHP (apostrophes doublées), comme la requête livraison. CAST float : le pilote dblib
-- du PHP-FPM local tronque les décimales (5,5 -> 5) sans lui.
SELECT
    SPD.SHIPNUM_0                                          AS N_SHIPMENT,
    SPD.ITMREF_0                                           AS ARTICLE,
    ITM.ITMDES1_0                                          AS DESIGNATION,
    CAST(SPD.SHIQTYSTU_0 - SPD.RCPQTYSTU_0 AS float)       AS QUANTITE_A_LIVREE
FROM X3_LCS.SHIPMENTD SPD
LEFT JOIN X3_LCS.ITMMASTER ITM
       ON SPD.ITMREF_0 = ITM.ITMREF_0
WHERE SPD.SHIPNUM_0 = '{{NUMERO_SHIPMENT}}'
  AND (SPD.SHIQTYSTU_0 - SPD.RCPQTYSTU_0) <> 0
ORDER BY SPD.ITMREF_0
