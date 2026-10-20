-- Carnet de commandes (sell-in) : montant et quantité commandés par saison, type de commande, client et article.
-- Source : vue SEI LCS_X3SALESX (colonnes BOH_*). Alimente Synthèse, Vue centrale, Alertes, Magasins,
-- Portes perdues / nouvelles, Évolution saisons, Diffusion de l'offre et le cockpit Accueil.
--
-- Périmètre BOH : le drapeau ISBOHPERIMETER n'est renseigné (1 / 0) que sur l'historique Navision ; il est NULL sur
-- toutes les commandes passées dans X3 depuis avril 2026 (dont toute la saison SS27). Pour ces lignes, le client est dans
-- le périmètre si son business model (LCS_CUSTOMER) en fait partie : on exclut les business models que l'historique
-- Navision classait hors périmètre (E-COMMERCE, FRANCE OTHER, DOTATIONS, GROUP COMPANIES, DESTOCKEUR, DISCOUNTERS,
-- RETAIL BUSINESS) et les clients sans business model. Règle déduite des données (elle recoupe le rapport Power BI à moins
-- de 1 % sur la PS et sur le réassort) ; la règle officielle reste à confirmer.
-- La PLV (famille POP) est exclue.
SELECT
    S.SERIESNO                                   AS SAISON,
    S.SALESORDERTYPE                             AS TYPE_COMMANDE,
    CASE
        WHEN S.ISBOHPERIMETER IS NOT NULL THEN S.ISBOHPERIMETER
        WHEN ISNULL(L.BUSINESS_MODEL_1, '') NOT IN ('', 'E-COMMERCE', 'FRANCE OTHER', 'DOTATIONS', 'GROUP COMPANIES', 'DESTOCKEUR', 'DISCOUNTERS', 'RETAIL BUSINESS') THEN 1
        ELSE 0
    END                                          AS PERIMETRE_BOH,
    S.CUSTOMERNO                                 AS CODE_CLIENT,
    S.ITEMNO                                     AS ARTICLE,
    C.ITEMFAMILYCODE                             AS FAMILLE,
    C.AGEGROUP                                   AS AGE,
    C.GENUSCODE                                  AS GENRE,
    C.LINEPRODUCT                                AS LIGNE_PRODUIT,
    MAX(C.ITEMDESC)                              AS DESIGNATION,
    SUM(S.BOH_AMOUNTEUR)                         AS MONTANT_EUR,
    SUM(S.BOH_QUANTITY)                          AS QUANTITE
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
    S.CUSTOMERNO, S.ITEMNO,
    C.ITEMFAMILYCODE, C.AGEGROUP, C.GENUSCODE, C.LINEPRODUCT
HAVING SUM(S.BOH_QUANTITY) <> 0 OR SUM(S.BOH_AMOUNTEUR) <> 0;
