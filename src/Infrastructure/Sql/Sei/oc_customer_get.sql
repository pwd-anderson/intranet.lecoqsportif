-- OC : relecture serveur d'UN client par son code exact, à l'enregistrement d'une OC (on ne fait jamais
-- confiance aux valeurs envoyées par le navigateur). Même critère d'activité que la recherche (BPCSTA_0 > 1).
SELECT
    B.BPCNUM_0 AS CODE_CLIENT,
    B.BPCNAM_0 AS NOM_CLIENT,
    B.CUR_0    AS DEVISE,
    B.TSCCOD_0 AS CODE_TARIF
FROM {{X3_SCHEMA}}.BPCUSTOMER B
WHERE B.BPCNUM_0 = :code
  AND B.BPCSTA_0 > 1
