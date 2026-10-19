-- OC : recherche client par code ou nom (autocomplétion de la page "Nouvelle OC").
-- Même critères que la recherche client du projet RMA ({{X3_SCHEMA}}.BPCUSTOMER / BPADDRESS, statut > 1,
-- UPPER ... LIKE '%saisie%'), avec deux différences :
--  - valeur liée en paramètre (:q_code / :q_name), jamais concaténée dans le SQL ;
--  - UNE seule adresse par client (adresse par défaut en priorité) : sans cela un client à plusieurs
--    adresses ressortait en plusieurs lignes.
SELECT TOP 20
    B.BPCNUM_0 AS CODE_CLIENT,
    B.BPCNAM_0 AS NOM_CLIENT,
    B.CUR_0    AS DEVISE,
    A.CTY_0    AS VILLE,
    A.CRY_0    AS PAYS
FROM {{X3_SCHEMA}}.BPCUSTOMER B
OUTER APPLY (
    SELECT TOP 1 ADR.CTY_0, ADR.CRY_0
    FROM {{X3_SCHEMA}}.BPADDRESS ADR
    WHERE ADR.BPANUM_0 = B.BPCNUM_0
    ORDER BY ADR.BPAADDFLG_0 DESC, ADR.BPAADD_0
) A
WHERE B.BPCSTA_0 > 1
  AND (UPPER(B.BPCNUM_0) LIKE :q_code OR UPPER(B.BPCNAM_0) LIKE :q_name)
ORDER BY B.BPCNUM_0
