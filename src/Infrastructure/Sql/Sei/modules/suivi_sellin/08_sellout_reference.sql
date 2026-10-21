-- Sell-out par référence : relevé de la semaine de référence de la saison, par enseigne, magasin et article.
-- Ventes cumulées de la saison jusqu'à la semaine de référence (pièces et euros), stock magasin de cette semaine (pièces et valeur),
-- ventes des dernières semaines ({{SEMAINES_RECENTES}} = les semaines de la fenêtre de rythme, 2 ou 4 selon la saison).
-- Source : cube SEI_X3_LCS.LCS_SELLOUT_SALES, restreint aux articles du reporting « Push Réassort » ({{ARTICLES}}).
-- Paramètres remplacés à l'exécution : {{ENSEIGNE}}, {{SAISON}} et {{ARTICLES}} comme dans la requête hebdomadaire ; {{SEMAINE_REF}} = dernière semaine de la saison ;
-- {{SEMAINES_RECENTES}} = ses 2 ou 4 dernières semaines, ex. 202639,202640,202641,202642.
SELECT
    S.CUSTOMER_ID           AS CODE_CLIENT,
    S.ITEMN                 AS ARTICLE,
    CAST(SUM(S.SALESQTY) AS float)  AS VENTES_QTE,
    CAST(SUM(S.SALESAMT) AS float)  AS VENTES_MONTANT,
    CAST(SUM(CASE WHEN S.WEEK_CODE = {{SEMAINE_REF}} THEN S.STOCKQTY ELSE 0 END) AS float) AS STOCK_QTE,
    CAST(SUM(CASE WHEN S.WEEK_CODE = {{SEMAINE_REF}} THEN S.STOCKAMT ELSE 0 END) AS float) AS STOCK_MONTANT,
    CAST(SUM(CASE WHEN S.WEEK_CODE IN ({{SEMAINES_RECENTES}}) THEN S.SALESQTY ELSE 0 END) AS float) AS VENTES_RECENTES_QTE
FROM SEI_X3_LCS.LCS_SELLOUT_SALES S
INNER JOIN (VALUES {{ARTICLES}}) A (ARTICLE, FAMILLE) ON A.ARTICLE = S.ITEMN
WHERE S.SOURCENAME = '{{ENSEIGNE}}'
  AND S.SERIESNO = '{{SAISON}}'
  AND S.WEEK_CODE <= {{SEMAINE_REF}}
GROUP BY S.CUSTOMER_ID, S.ITEMN
HAVING SUM(S.SALESQTY) <> 0 OR SUM(CASE WHEN S.WEEK_CODE = {{SEMAINE_REF}} THEN S.STOCKQTY ELSE 0 END) <> 0;
