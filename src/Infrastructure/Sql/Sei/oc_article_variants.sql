-- OC : tous les articles ENFANTS (variants, code « PARENT_VARIANT ») de une ou plusieurs références parentes, pour le
-- flux XML vers X3 (un tarif par variant). Sans tenir compte de la collection ni du statut : tous les variants du
-- parent, chacun une seule fois. Le parent lui-même n'est jamais renvoyé (un code sans « _ » n'est pas un variant).
-- {{PARENTS}} est remplacé côté PHP par une liste de valeurs ('2210037'), ('1511960') (apostrophes doublées) ; le test
-- LEFT(...) = parent garantit l'exactitude, le LIKE [_] sert de pré-filtre sur l'index du code article.
SELECT DISTINCT
    P.PARENT   AS PARENT,
    ITM.ITMREF_0 AS ENFANT
FROM (VALUES {{PARENTS}}) AS P(PARENT)
INNER JOIN {{X3_SCHEMA}}.ITMMASTER ITM
        ON ITM.ITMREF_0 LIKE P.PARENT + '[_]%'
       AND LEFT(ITM.ITMREF_0, CHARINDEX('_', ITM.ITMREF_0) - 1) = P.PARENT
ORDER BY P.PARENT, ITM.ITMREF_0
