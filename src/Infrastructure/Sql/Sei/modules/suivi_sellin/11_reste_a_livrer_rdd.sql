-- Reste à livrer par date de livraison demandée (RDD) : montant et quantité restant à facturer, par saison, type de
-- commande, famille, client et article. Source : vue SEI LCS_X3SALESX (colonnes ROB_*).
-- Alimente la vue « reste à livrer » de l'onglet Facturation (retards, mois à venir, références concernées).
-- Mêmes règles de périmètre que 01_carnet_commandes.sql. Date RDD vide = pas de date de livraison demandée.
SELECT
    S.SERIESNO                                              AS SAISON,
    S.SALESORDERTYPE                                        AS TYPE_COMMANDE,
    CASE
        WHEN S.ISBOHPERIMETER IS NOT NULL THEN S.ISBOHPERIMETER
        WHEN ISNULL(L.BUSINESS_MODEL_1, '') NOT IN ('', 'E-COMMERCE', 'FRANCE OTHER', 'DOTATIONS', 'GROUP COMPANIES', 'DESTOCKEUR', 'DISCOUNTERS', 'RETAIL BUSINESS') THEN 1
        ELSE 0
    END                                                     AS PERIMETRE_BOH,
    C.ITEMFAMILYCODE                                        AS FAMILLE,
    S.CUSTOMERNO                                            AS CODE_CLIENT,
    S.ITEMNO                                                AS ARTICLE,
    MAX(C.ITEMDESC)                                         AS DESIGNATION,
    CONVERT(varchar(10), S.REQUESTED_DELIVERY_DATE, 23)     AS DATE_RDD,
    SUM(S.ROB_AMOUNTEUR)                                    AS RESTE_EUR,
    SUM(S.ROB_QUANTITY)                                     AS RESTE_QTE
FROM SEI_X3_LCS.LCS_X3SALESX S
LEFT JOIN SEI_X3_LCS.LCS_COLLECTION C
       ON C.ITEM_ID = S.ITEMNO AND C.SERIESCODE = S.SERIESNO
LEFT JOIN SEI_X3_LCS.LCS_CUSTOMER L
       ON L.CUSTOMER_ID = S.CUSTOMERNO AND L.COMPANY_ID = 'LCSI'
WHERE S.SERIESNO BETWEEN '2024-01-SS' AND '2027-02-FW'
  AND ISNULL(C.ITEMFAMILYCODE, '') <> 'POP'
  AND (S.ROB_QUANTITY <> 0 OR S.ROB_AMOUNTEUR <> 0)
GROUP BY
    S.SERIESNO, S.SALESORDERTYPE,
    CASE
        WHEN S.ISBOHPERIMETER IS NOT NULL THEN S.ISBOHPERIMETER
        WHEN ISNULL(L.BUSINESS_MODEL_1, '') NOT IN ('', 'E-COMMERCE', 'FRANCE OTHER', 'DOTATIONS', 'GROUP COMPANIES', 'DESTOCKEUR', 'DISCOUNTERS', 'RETAIL BUSINESS') THEN 1
        ELSE 0
    END,
    C.ITEMFAMILYCODE, S.CUSTOMERNO, S.ITEMNO,
    CONVERT(varchar(10), S.REQUESTED_DELIVERY_DATE, 23)
HAVING SUM(S.ROB_QUANTITY) <> 0 OR SUM(S.ROB_AMOUNTEUR) <> 0;
