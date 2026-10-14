-- Étiquettes d'expédition : lignes d'une COMMANDE D'ACHAT, lues dans les réceptions fournisseur
-- (mêmes tables que la stat « Réception fournisseur » : PRECEIPTD + ITMMASTER).
-- Une ligne par commande d'achat et par article : les quantités reçues de toutes les réceptions de la
-- commande sont additionnées. Aliases alignés sur la requête livraison (etiquette_expedition.sql), sauf le
-- numéro (N_COMMANDE_ACHAT). Le numéro saisi est reconnu tel quel (CAB25-03610/015) ou sans son suffixe « /NNN »
-- (CAB25-03610 retrouve alors CAB25-03610/014, /015…).
-- {{NUMERO_COMMANDE}} et {{NUMERO_COMMANDE_LIKE}} sont remplacés côté PHP (apostrophes doublées, et \ % _ [ neutralisés
-- pour le LIKE), comme la requête livraison. CAST float : le pilote dblib du PHP-FPM local tronque les sommes décimales
-- (5,5 -> 5) sans lui.
SELECT
    PTD.POHNUM_0                              AS N_COMMANDE_ACHAT,
    PTD.ITMREF_0                              AS ARTICLE,
    ITM.ITMDES1_0                             AS DESIGNATION,
    CAST(SUM(PTD.QTYUOM_0) AS float)          AS QUANTITE_A_LIVREE
FROM X3_LCS.PRECEIPTD PTD
INNER JOIN X3_LCS.ITMMASTER ITM
        ON ITM.ITMREF_0 = PTD.ITMREF_0
WHERE PTD.POHNUM_0 = '{{NUMERO_COMMANDE}}'
   OR PTD.POHNUM_0 LIKE '{{NUMERO_COMMANDE_LIKE}}/%' ESCAPE '\'
GROUP BY PTD.POHNUM_0, PTD.ITMREF_0, ITM.ITMDES1_0
HAVING SUM(PTD.QTYUOM_0) > 0
ORDER BY PTD.POHNUM_0, PTD.ITMREF_0
