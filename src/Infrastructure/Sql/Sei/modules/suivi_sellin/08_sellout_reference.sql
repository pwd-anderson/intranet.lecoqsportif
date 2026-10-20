-- Sell-out détail par référence : ventes cumulées de la saison et stock magasin de la dernière semaine connue,
-- par enseigne, magasin et article (meilleures ventes, ruptures, couverture).
-- Source : cube SEI_X3_LCS.LCS_SELLOUT_SALES. Le détail semaine par semaine représente ~22 millions de lignes :
-- on agrège à la saison, le stock étant relevé à la dernière semaine de chaque enseigne.
SELECT
    S.SOURCENAME                                                AS ENSEIGNE,
    S.CUSTOMER_ID                                               AS CODE_CLIENT,
    S.ITEMN                                                     AS ARTICLE,
    S.SERIESNO                                                  AS SAISON,
    SUM(S.SALESQTY)                                             AS VENTES_QTE,
    SUM(S.SALESAMT)                                             AS VENTES_MONTANT,
    SUM(CASE WHEN S.WEEK_CODE = L.DERNIERE_SEMAINE THEN S.STOCKQTY ELSE 0 END) AS STOCK_QTE
FROM SEI_X3_LCS.LCS_SELLOUT_SALES S
INNER JOIN (
    SELECT SOURCENAME, MAX(WEEK_CODE) AS DERNIERE_SEMAINE
    FROM SEI_X3_LCS.LCS_SELLOUT_SALES
    WHERE SOURCENAME IN ('INTERSPORT', 'SPORT2000')
    GROUP BY SOURCENAME
) L ON L.SOURCENAME = S.SOURCENAME
WHERE S.WEEK_CODE >= 202501
GROUP BY S.SOURCENAME, S.CUSTOMER_ID, S.ITEMN, S.SERIESNO;
