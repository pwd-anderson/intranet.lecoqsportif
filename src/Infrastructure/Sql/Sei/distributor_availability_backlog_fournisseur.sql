-- Module Distributor Availability — backlog fournisseur
-- Le moteur JS de référence ne garde que les lignes TYPE FLUX = 'BL Fournisseur'
-- (les transferts intersites sont ignorés côté outil) : pas besoin de l'UNION
-- avec MASTER_TABLES.COMMANDES_INTERSITES utilisée par Pilotage Livraisons.
SELECT
    'BL Fournisseur'                                        AS [TYPE FLUX],
    POQ.ITMREF_0                                            AS [ARTICLE],
    POH.POHNUM_0                                            AS [N° COMMANDE],
    CONVERT(varchar(10), POQ.EXTRCPDAT_0, 23)               AS [DATE LIVRAISON],
    POQ.QTYUOM_0 - POQ.RCPQTYSTU_0                         AS [QTE A LIVRER]
FROM X3_LCS.PORDER POH
    INNER JOIN X3_LCS.PORDERQ POQ ON POH.POHNUM_0 = POQ.POHNUM_0
WHERE
    POQ.LINCLEFLG_0 = 1
    AND POH.BETFCY_0 <> 2
    AND (POQ.QTYUOM_0 - POQ.RCPQTYSTU_0) > 0
