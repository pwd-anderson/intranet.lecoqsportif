-- Suivi Facturation (Ventes) : quantité facturée, montant EUR et nombre de clients
-- distincts, croisés par Commercial N+1 / Groupement / Famille / Année de
-- facturation / Type de commande. Alimente le Row Grouping AG Grid côté navigateur
-- (aucun pivot PHP nécessaire).
--
-- ATTENTION CUSTOMER_COUNT : COUNT(DISTINCT S.CUSTOMERNO) est calculé à la maille la
-- plus fine du GROUP BY (salesman + groupement + famille + année + type de commande).
-- AG Grid agrège ensuite les sous-totaux avec aggFunc: 'sum' sur cette colonne : les
-- sous-totaux ne représentent donc PAS un vrai compte de clients distincts au niveau
-- du groupe parent (un même client apparaissant dans plusieurs lignes de detail est
-- compté plusieurs fois dans la somme). Compromis assumé, hors scope de corriger ça
-- côté requête.
--
-- Filtre CUST.CUSTOMERGROUPCODE = 'INTERSPORT' volontairement conservé : cette stat
-- est scopée sur ce groupe client (décision utilisateur).

SELECT
    CUST.SALESMANNAMENPLUS1,
    CUST.INDEPENDANTGROUPMENT,
    C.ITEMFAMILYCODE,
    YEAR(S.EXPECTEDINVOICINGDATE) AS INVOICING_POSTING_DATE,
    S.SALESORDERTYPE,
    SUM(S.INV_QUANTITY)  AS INV_QUANTITY,
    SUM(S.INV_AMOUNTEUR) AS INV_AMOUNTEUR,
    COUNT(DISTINCT S.CUSTOMERNO) AS CUSTOMER_COUNT

FROM SEI_X3_LCS.LCS_X3SALESX S
LEFT JOIN SEI_X3_LCS.LCS_COLLECTION C
    ON  S.ITEMNO   = C.ITEM_ID
    AND S.SERIESNO = C.SERIESCODE

LEFT JOIN SEI_X3_LCS.LCS_CUSTOMER CUST
    ON  S.COMPANYCODE = CUST.COMPANY_ID
    AND S.CUSTOMERNO  = CUST.CUSTOMER_ID

WHERE YEAR(S.EXPECTEDINVOICINGDATE) = {{YEAR}}
--AND CUST.INDEPENDANTGROUPMENT = 'SCHIEVER'
AND C.ITEMFAMILYCODE IN ('FTW', 'HDW', 'APL')
AND CUST.CUSTOMERGROUPCODE = 'INTERSPORT'

GROUP BY
    CUST.SALESMANNAMENPLUS1,
    CUST.INDEPENDANTGROUPMENT,
    C.ITEMFAMILYCODE,
    YEAR(S.EXPECTEDINVOICINGDATE),
    S.SALESORDERTYPE
