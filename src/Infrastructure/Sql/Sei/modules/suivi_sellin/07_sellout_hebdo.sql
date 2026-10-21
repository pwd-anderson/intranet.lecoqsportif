-- Sell-out hebdomadaire : ventes et stock magasin par enseigne, saison, magasin, famille et semaine.
-- Source : cube SEI_X3_LCS.LCS_SELLOUT_SALES (ventes SALESQTY / SALESAMT et stock STOCKQTY, même table que la stat Sell Out par Client).
-- Périmètre : seuls les articles du reporting « Push Réassort » de la saison comptent (le cube contient bien plus d'articles que
-- la collection suivie) ; {{ARTICLES}} est la liste (code article, famille) de la saison et de l'enseigne, une requête par saison et enseigne.
-- Les cumuls de saison (cq, ce) sont calculés ensuite, semaine après semaine, à partir de ces lignes.
-- Paramètres remplacés à l'exécution : {{ENSEIGNE}} = INTERSPORT ou SPORT2000 ; {{SAISON}} = ex. 2026-02-FW ; {{ARTICLES}} = liste des articles de la saison
-- et de l'enseigne avec leur famille, ex. ('2320670','Textile'),('2423234','Footwear'),… ; {{SEMAINE_DEBUT}} / {{SEMAINE_FIN}} = bornes en semaines (AAAASS).
SELECT
    S.CUSTOMER_ID           AS CODE_CLIENT,
    A.FAMILLE               AS FAMILLE,
    S.WEEK_CODE             AS SEMAINE,
    CAST(SUM(S.SALESQTY) AS float)  AS VENTES_QTE,
    CAST(SUM(S.SALESAMT) AS float)  AS VENTES_MONTANT,
    CAST(SUM(S.STOCKQTY) AS float)  AS STOCK_QTE
FROM SEI_X3_LCS.LCS_SELLOUT_SALES S
INNER JOIN (VALUES {{ARTICLES}}) A (ARTICLE, FAMILLE) ON A.ARTICLE = S.ITEMN
WHERE S.SOURCENAME = '{{ENSEIGNE}}'
  AND S.SERIESNO = '{{SAISON}}'
  AND S.WEEK_CODE BETWEEN {{SEMAINE_DEBUT}} AND {{SEMAINE_FIN}}
GROUP BY S.CUSTOMER_ID, A.FAMILLE, S.WEEK_CODE;
