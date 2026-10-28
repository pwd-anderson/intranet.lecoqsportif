-- OC : tarifs spéciaux (type 7TSPEC) EXISTANTS d'un client pour des articles ENFANTS (variants), encore ouverts à la date de
-- début de la nouvelle OC. Sert à décider des clôtures (flux WSUPD7TSPE, date de fin = début de l'OC - 1 jour) avant la création
-- (WSCRESPL) : le prix de l'OC la plus récente remplace l'ancien.
-- Contrairement à l'ancien module Screeneo, le type de tarif est filtré (PLI_0) : on ne clôture jamais un autre type de tarif.
-- Une fiche (PLICRD_0) = un client + un article (ligne 1000). Une fiche fermée avant le début de l'OC n'est pas renvoyée.
-- {{CLIENT}} : code client entre apostrophes (doublées) ; {{DATE_DEBUT}} : date de début de l'OC entre apostrophes (AAAA-MM-JJ) ;
-- {{VARIANTS}} : liste ('2210037_40', '2210037_41'), apostrophes doublées. Remplacés côté PHP (OcX3Lookup).
SELECT
    SPL.PLICRD_0 AS FICHE,
    SPL.PLICRI1_0 AS CLIENT,
    SPL.PLICRI2_0 AS ARTICLE,
    CONVERT(varchar(10), SPL.PLISTRDAT_0, 23) AS DATE_DEBUT,
    CONVERT(varchar(10), SPL.PLIENDDAT_0, 23) AS DATE_FIN,
    CAST(SPL.PRI_0 AS float) AS PRIX,
    SPL.CUR_0 AS DEVISE
FROM {{X3_SCHEMA}}.SPRICLIST SPL
WHERE SPL.PLI_0 = '7TSPEC'
  AND SPL.PLICRI1_0 = {{CLIENT}}
  AND SPL.PLICRI2_0 IN ({{VARIANTS}})
  AND SPL.PLIENDDAT_0 >= {{DATE_DEBUT}}
ORDER BY SPL.PLICRI2_0, SPL.PLICRD_0
