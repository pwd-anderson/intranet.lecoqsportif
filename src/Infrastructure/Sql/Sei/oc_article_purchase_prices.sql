-- OC : prix d'achat (liste « 1PFCOL », par modèle et collection) pour des couples (modèle, collection) donnés.
-- Même lecture que la stat « Base Articles/Tarifs » (PPL.PRI_0 AS PRIX_ACHAT, PPL.CUR_0 : PPRICLIST + PPRICFICH, type 1PFCOL,
-- PLICRI2_0 = modèle de l'article, PLICRI1_0 = collection). Quand plusieurs lignes existent pour le même couple (cas observé,
-- à prix identique), on retient la plus récente (dates de fin puis de début, puis ligne) pour un résultat déterministe.
-- Valeurs liées en paramètres : {{PAIRS}} est remplacé par (PPL.PLICRI2_0 = :m0 AND PPL.PLICRI1_0 = :c0) OR ...
SELECT T.MODELE, T.COLLECTION, T.PRIX_ACHAT, T.DEVISE_ACHAT
FROM (
    SELECT
        PPL.PLICRI2_0 AS MODELE,
        PPL.PLICRI1_0 AS COLLECTION,
        PPL.PRI_0     AS PRIX_ACHAT,
        PPL.CUR_0     AS DEVISE_ACHAT,
        ROW_NUMBER() OVER (
            PARTITION BY PPL.PLICRI2_0, PPL.PLICRI1_0
            ORDER BY PPL.PLIENDDAT_0 DESC, PPL.PLISTRDAT_0 DESC, PPL.PLILIN_0 DESC
        ) AS rn
    FROM {{X3_SCHEMA}}.PPRICLIST PPL
    INNER JOIN {{X3_SCHEMA}}.PPRICFICH PPF
            ON PPL.PLICRD_0 = PPF.PLICRD_0 AND PPL.PLI_0 = PPF.PLI_0 AND PPF.PLI_0 = '1PFCOL'
    WHERE {{PAIRS}}
) T
WHERE T.rn = 1
