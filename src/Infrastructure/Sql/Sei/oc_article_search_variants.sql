-- OC : recherche d'un VARIANT (taille) par son code, utilisée quand la saisie contient « _ » (ex. « 2210037_T9 » ou « 2210037_ »).
-- Un variant est un article « PARENT_VARIANT » : le code contient « _ ». Ce n'est pas la recherche par défaut (articles parents
-- seulement, voir oc_article_search.sql) : une recherche par désignation ramènerait toutes les tailles de chaque article.
-- Valeur liée en paramètre (:q_ref), jamais concaténée.
SELECT TOP 30
    ITM.ITMREF_0  AS ARTICLE,
    ITM.ITMDES1_0 AS DESIGNATION
FROM {{X3_SCHEMA}}.ITMMASTER ITM
WHERE CHARINDEX('_', ITM.ITMREF_0) > 0
  AND UPPER(ITM.ITMREF_0) LIKE :q_ref
ORDER BY ITM.ITMREF_0
