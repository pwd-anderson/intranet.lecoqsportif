-- Pré-saison / réassort : commandes de réassort (type RE) par saison, famille, client, mois de facture et semaine de
-- livraison demandée (RDD, lundi de la semaine). Source : vue SEI LCS_X3SALESX.
-- Donne, pour la vue « Focus réassort (facturé) » : pièces commandées et facturées (BOH_QUANTITY / INV_QUANTITY), montants,
-- nombre de magasins, rythme mensuel et hebdomadaire, comparaison avec la même date de la saison précédente.
-- Mêmes règles de périmètre que 01_carnet_commandes.sql. Le RAL magasin et le « réassort conseillé » du template ne sont
-- PAS dans le cube (V1 sans). Mois ou semaine vide = pas de facture / pas de date de livraison demandée.
SELECT
    S.SERIESNO                                              AS SAISON,
    CASE
        WHEN S.ISBOHPERIMETER IS NOT NULL THEN S.ISBOHPERIMETER
        WHEN ISNULL(L.BUSINESS_MODEL_1, '') NOT IN ('', 'E-COMMERCE', 'FRANCE OTHER', 'DOTATIONS', 'GROUP COMPANIES', 'DESTOCKEUR', 'DISCOUNTERS', 'RETAIL BUSINESS') THEN 1
        ELSE 0
    END                                                     AS PERIMETRE_BOH,
    C.ITEMFAMILYCODE                                        AS FAMILLE,
    S.CUSTOMERNO                                            AS CODE_CLIENT,
    CONVERT(varchar(7), S.INVOICEDATE, 23)                  AS MOIS_FACTURE,
    CONVERT(varchar(10), DATEADD(day, -(DATEDIFF(day, '19000101', S.REQUESTED_DELIVERY_DATE) % 7), S.REQUESTED_DELIVERY_DATE), 23) AS SEMAINE_RDD,
    SUM(S.BOH_AMOUNTEUR)                                    AS COMMANDE_EUR,
    SUM(S.INV_AMOUNTEUR)                                    AS FACTURE_EUR,
    SUM(S.BOH_QUANTITY)                                     AS COMMANDE_QTE,
    SUM(S.INV_QUANTITY)                                     AS FACTURE_QTE
FROM SEI_X3_LCS.LCS_X3SALESX S
LEFT JOIN SEI_X3_LCS.LCS_COLLECTION C
       ON C.ITEM_ID = S.ITEMNO AND C.SERIESCODE = S.SERIESNO
LEFT JOIN SEI_X3_LCS.LCS_CUSTOMER L
       ON L.CUSTOMER_ID = S.CUSTOMERNO AND L.COMPANY_ID = 'LCSI'
WHERE S.SERIESNO BETWEEN '2024-01-SS' AND '2027-02-FW'
  AND S.SALESORDERTYPE = 'RE'
  AND ISNULL(C.ITEMFAMILYCODE, '') <> 'POP'
GROUP BY
    S.SERIESNO,
    CASE
        WHEN S.ISBOHPERIMETER IS NOT NULL THEN S.ISBOHPERIMETER
        WHEN ISNULL(L.BUSINESS_MODEL_1, '') NOT IN ('', 'E-COMMERCE', 'FRANCE OTHER', 'DOTATIONS', 'GROUP COMPANIES', 'DESTOCKEUR', 'DISCOUNTERS', 'RETAIL BUSINESS') THEN 1
        ELSE 0
    END,
    C.ITEMFAMILYCODE, S.CUSTOMERNO,
    CONVERT(varchar(7), S.INVOICEDATE, 23),
    CONVERT(varchar(10), DATEADD(day, -(DATEDIFF(day, '19000101', S.REQUESTED_DELIVERY_DATE) % 7), S.REQUESTED_DELIVERY_DATE), 23)
HAVING SUM(S.BOH_QUANTITY) <> 0 OR SUM(S.INV_QUANTITY) <> 0 OR SUM(S.BOH_AMOUNTEUR) <> 0 OR SUM(S.INV_AMOUNTEUR) <> 0;
