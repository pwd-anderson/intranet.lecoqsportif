-- Sell-out : ventes et stock des magasins Intersport et Sport 2000, par magasin, semaine et collection.
-- Source : cube SEI_X3_LCS.LCS_SELLOUT_SALES (même table que la stat Sell Out par Client). Contient les ventes
-- (SALESQTY / SALESAMT) et le stock magasin (STOCKQTY / STOCKAMT) : pas besoin d'une autre source pour le stock.
SELECT
    S.SOURCENAME            AS ENSEIGNE,
    S.CUSTOMER_ID           AS CODE_CLIENT,
    S.SERIESNO              AS SAISON,
    S.WEEK_CODE             AS SEMAINE,
    SUM(S.SALESQTY)         AS VENTES_QTE,
    SUM(S.SALESAMT)         AS VENTES_MONTANT,
    SUM(S.STOCKQTY)         AS STOCK_QTE,
    SUM(S.STOCKAMT)         AS STOCK_MONTANT
FROM SEI_X3_LCS.LCS_SELLOUT_SALES S
WHERE S.SOURCENAME IN ('INTERSPORT', 'SPORT2000')
  AND S.WEEK_CODE >= 202501
GROUP BY S.SOURCENAME, S.CUSTOMER_ID, S.SERIESNO, S.WEEK_CODE;
