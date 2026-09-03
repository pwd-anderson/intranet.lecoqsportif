-- Requête allégée dédiée au module Pilotage Livraisons
-- UNION ALL : PO fournisseur X3 + transferts intersites
-- Alias alignés sur les noms de colonnes attendus par le moteur JS

SELECT
    'BL Fournisseur'                                        AS [TYPE FLUX],
    ''                                                      AS [SITE EXPEDITION],
    POH.YCOLLECTION_0                                       AS [COLLECTION],
    POQ.ITMREF_0                                            AS [ARTICLE],
    POH.BPRNAM_0                                            AS [NOM FOURN.],
    POH.POHNUM_0                                            AS [NO COMMANDE],
    POH.ORDREF_0                                            AS [REF. INTERNE],
    CONVERT(varchar(10), POH.ORDDAT_0, 23)                  AS [DATE DE COMMANDE],
    CONVERT(varchar(10), POQ.ZDATEDEM_0, 23)                AS [DATE EXPEDITION],
    POQ.QTYUOM_0 - POQ.RCPQTYSTU_0                         AS [QTE A LIVRER]
FROM X3_LCS.PORDER POH
    INNER JOIN X3_LCS.PORDERQ POQ ON POH.POHNUM_0 = POQ.POHNUM_0
WHERE
    POQ.LINCLEFLG_0 = 1
    AND POH.BETFCY_0 <> 2
    AND (POQ.QTYUOM_0 - POQ.RCPQTYSTU_0) > 0

UNION ALL

SELECT
    CI.TYPE_FLUX                                            AS [TYPE FLUX],
    CI.SITE_EXPEDITION                                      AS [SITE EXPEDITION],
    CI.COLLECTION                                           AS [COLLECTION],
    CI.ITMREF_0                                             AS [ARTICLE],
    CI.BPRNAM_0                                             AS [NOM FOURN.],
    CI.NO_DOCUMENT                                          AS [NO COMMANDE],
    CI.ORDREF_0                                             AS [REF. INTERNE],
    CONVERT(varchar(10), CI.ORDDAT_0, 23)                   AS [DATE DE COMMANDE],
    CONVERT(varchar(10), CI.XSHIPDAT_0, 23)                 AS [DATE EXPEDITION],
    CI.QTE_RESTANTE                                         AS [QTE A LIVRER]
FROM MASTER_TABLES.COMMANDES_INTERSITES CI
WHERE CI.QTE_RESTANTE > 0
