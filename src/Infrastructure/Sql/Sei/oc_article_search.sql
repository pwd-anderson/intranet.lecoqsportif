-- OC : recherche article par code ou désignation (autocomplétion de la page "Nouvelle OC").
-- Articles PARENTS uniquement : chez LCS le variant (taille...) est le suffixe de ITMREF_0 après "_"
-- (<parent>_<variant>) ; une ligne sans "_" est un parent. Vérifié en direct : 10 796 parents sur
-- 131 090 lignes (3 parents n'existent que via leurs variants et ne sont donc pas proposés).
-- Valeurs liées en paramètres (:q_ref / :q_des), jamais concaténées.
SELECT TOP 20
    ITM.ITMREF_0  AS ARTICLE,
    ITM.ITMDES1_0 AS DESIGNATION
FROM X3_LCS.ITMMASTER ITM
WHERE CHARINDEX('_', ITM.ITMREF_0) = 0
  AND (UPPER(ITM.ITMREF_0) LIKE :q_ref OR UPPER(ITM.ITMDES1_0) LIKE :q_des)
ORDER BY ITM.ITMREF_0
