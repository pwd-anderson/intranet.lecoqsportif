-- Facturation : commandé (BOH), facturé (INV) et reste à facturer (ROB) par saison, type de commande, famille, client,
-- mois de facture et mois de livraison demandée (RDD). Source : vue SEI LCS_X3SALESX.
-- Alimente l'onglet Facturation (facturé, reste à livrer, prévu contre réel), la comparaison commandé / facturé de la
-- trame de rappel et le cockpit Accueil.
-- Mêmes règles de périmètre que 01_carnet_commandes.sql (drapeau Navision, sinon client avec business model ; PLV exclue).
-- Un mois vide = pas de facture (ou pas de date de livraison demandée).
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
    CONVERT(varchar(7), S.INVOICEDATE, 23)                  AS MOIS_FACTURE,
    CONVERT(varchar(7), S.REQUESTED_DELIVERY_DATE, 23)      AS MOIS_RDD,
    SUM(S.BOH_AMOUNTEUR)                                    AS COMMANDE_EUR,
    SUM(S.INV_AMOUNTEUR)                                    AS FACTURE_EUR,
    SUM(S.ROB_AMOUNTEUR)                                    AS RESTE_A_FACTURER_EUR
FROM SEI_X3_LCS.LCS_X3SALESX S
LEFT JOIN SEI_X3_LCS.LCS_COLLECTION C
       ON C.ITEM_ID = S.ITEMNO AND C.SERIESCODE = S.SERIESNO
LEFT JOIN SEI_X3_LCS.LCS_CUSTOMER L
       ON L.CUSTOMER_ID = S.CUSTOMERNO AND L.COMPANY_ID = 'LCSI'
WHERE S.SERIESNO BETWEEN '2024-01-SS' AND '2027-02-FW'
  AND ISNULL(C.ITEMFAMILYCODE, '') <> 'POP'
GROUP BY
    S.SERIESNO, S.SALESORDERTYPE,
    CASE
        WHEN S.ISBOHPERIMETER IS NOT NULL THEN S.ISBOHPERIMETER
        WHEN ISNULL(L.BUSINESS_MODEL_1, '') NOT IN ('', 'E-COMMERCE', 'FRANCE OTHER', 'DOTATIONS', 'GROUP COMPANIES', 'DESTOCKEUR', 'DISCOUNTERS', 'RETAIL BUSINESS') THEN 1
        ELSE 0
    END,
    C.ITEMFAMILYCODE, S.CUSTOMERNO,
    CONVERT(varchar(7), S.INVOICEDATE, 23),
    CONVERT(varchar(7), S.REQUESTED_DELIVERY_DATE, 23)
HAVING SUM(S.BOH_AMOUNTEUR) <> 0 OR SUM(S.INV_AMOUNTEUR) <> 0 OR SUM(S.ROB_AMOUNTEUR) <> 0;
