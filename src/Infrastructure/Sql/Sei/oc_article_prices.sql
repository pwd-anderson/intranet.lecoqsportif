-- OC : code tarif, tarif et MSRP de UN OU PLUSIEURS articles parents pour un client donné (une ligne par article).
--  - collection = la plus récente de l'article (même règle que le reste de l'intranet : YITMCOLLECT +
--    YCOLLECTION, ligne dont ZITMCOL.ZDROPPED_0 = 2 exclue) ;
--  - CODE_TARIF = groupe tarifaire du client (BPCUSTOMER.TSCCOD_0), DEVISE = devise du client (BPCUSTOMER.CUR_0) ;
--  - TARIF = PRI_0 de SPRICLIST pour (groupe du client, collection, modèle de l'article) ;
--  - MSRP  = même lecture avec le groupe constant 'CSEU'.
-- Aucun filtre sur le type de tarif : T10 et T11 n'ont aucune clé commune (vérifié). 0,6 % des clés ont
-- plusieurs lignes à prix différents à dates identiques : le TOP 1 est rendu déterministe par un ORDER BY.
-- Valeurs liées en paramètres (:customer et les listes :a0.., :b0.. générées par le service à la place de
-- {{ARTICLES_CR}} et {{ARTICLES}}), jamais concaténées.
WITH CollectionRecente AS (
    SELECT
        YIL.ITMREF_0,
        YIL.YCOLLECT_0,
        ROW_NUMBER() OVER (
            PARTITION BY YIL.ITMREF_0
            ORDER BY YCO.YDATDEB_0 DESC
        ) AS rn
    FROM X3_LCS.YITMCOLLECT YIL
    INNER JOIN X3_LCS.YCOLLECTION YCO
            ON YIL.YCOLLECT_0 = YCO.YCOLLECT_0
    LEFT JOIN X3_LCS.ZITMCOL ITC
            ON ITC.ITMREF_0   = LEFT(YIL.ITMREF_0, CHARINDEX('_', YIL.ITMREF_0 + '_') - 1)
           AND ITC.YCOLLECT_0 = YCO.YCOLLECT_0
    WHERE YIL.ITMREF_0 IN ({{ARTICLES_CR}})
      AND ISNULL(ITC.ZDROPPED_0, 0) <> 2
)
SELECT
    ITM.ITMREF_0     AS ARTICLE,
    ITM.ITMDES1_0    AS DESIGNATION,
    ITM.ZMODELCOD_0  AS MODELE,
    CR.YCOLLECT_0    AS COLLECTION,
    BPC.TSCCOD_0     AS CODE_TARIF,
    BPC.CUR_0        AS DEVISE,
    (SELECT TOP 1 SPL3.PRI_0
       FROM X3_LCS.SPRICLIST SPL3
      WHERE SPL3.PLICRI3_0 = ITM.ZMODELCOD_0
        AND SPL3.PLICRI2_0 = CR.YCOLLECT_0
        AND SPL3.PLICRI1_0 = BPC.TSCCOD_0
      ORDER BY SPL3.PLIENDDAT_0 DESC, SPL3.PLISTRDAT_0 DESC, SPL3.PLILIN_0 DESC) AS TARIF,
    (SELECT TOP 1 SPL2.PRI_0
       FROM X3_LCS.SPRICLIST SPL2
      WHERE SPL2.PLICRI3_0 = ITM.ZMODELCOD_0
        AND SPL2.PLICRI2_0 = CR.YCOLLECT_0
        AND SPL2.PLICRI1_0 = 'CSEU'
      ORDER BY SPL2.PLIENDDAT_0 DESC, SPL2.PLISTRDAT_0 DESC, SPL2.PLILIN_0 DESC) AS MSRP
FROM X3_LCS.ITMMASTER ITM
INNER JOIN X3_LCS.BPCUSTOMER BPC ON BPC.BPCNUM_0 = :customer
LEFT JOIN CollectionRecente CR ON CR.ITMREF_0 = ITM.ITMREF_0 AND CR.rn = 1
WHERE ITM.ITMREF_0 IN ({{ARTICLES}})
