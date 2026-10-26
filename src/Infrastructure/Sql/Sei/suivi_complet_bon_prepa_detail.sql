-- Detail d'un bon de preparation : fenetre du Suivi complet de commande.
-- 1) SPACKD (colis) est la table principale : colis du bon de livraison du bon de prepa, limites aux articles du bon.
-- 2) Tant que le bon n'est pas expedie, il n'a pas de colis (le n° de livraison est reserve mais SPACKD est vide) :
--    on affiche alors les lignes preparees du bon (STOPRED), sans n° de colis ni poids (N_COLIS NULL).
-- Le n° de bon est un parametre lie (deux fois, d'ou deux noms), jamais concatene. Quantite et poids castes en float :
-- le pilote dblib (poste local) tronque sinon les decimales.
SELECT
    SPD.PACNUM_0                          AS [N_COLIS],
    SPD.ITMREF_0                          AS [ARTICLE],
    SPD.ITMDES1_0                         AS [DESCRIPTION],
    CAST(SPD.QTY_0 AS float)              AS [QTE],
    CAST(SPD.NETWEI_0 AS float)           AS [POIDS]
FROM X3_LCS.SPACKD SPD
INNER JOIN X3_LCS.STOPREH PRH ON PRH.SDHNUM_0 = SPD.VCRNUM_0
WHERE PRH.PRHNUM_0 = :num_bon_prepa
  AND EXISTS (SELECT 1 FROM X3_LCS.STOPRED PRE WHERE PRE.PRHNUM_0 = PRH.PRHNUM_0 AND PRE.ITMREF_0 = SPD.ITMREF_0)

UNION ALL

SELECT
    NULL,
    PRE.ITMREF_0,
    PRE.ITMDES1_0,
    CAST(PRE.QTYSTU_0 AS float),
    NULL
FROM X3_LCS.STOPRED PRE
INNER JOIN X3_LCS.STOPREH PRH ON PRH.PRHNUM_0 = PRE.PRHNUM_0
WHERE PRH.PRHNUM_0 = :num_bon_prepa_lignes
  AND NOT EXISTS (SELECT 1 FROM X3_LCS.SPACKD S WHERE S.VCRNUM_0 = PRH.SDHNUM_0)

ORDER BY [N_COLIS], [ARTICLE]
