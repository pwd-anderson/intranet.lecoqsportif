-- Requête allégée dédiée au module Pilotage Livraisons
-- Alias alignés sur les noms de colonnes attendus par le moteur JS
SELECT
    SOH.STOFCY_0                                                           AS [SITE],
    ATX.TEXTE_0                                                            AS [MAINNETWORK],
    SOH.BPCINV_0                                                           AS [CLIENT],
    BPC_INV.BPCNAM_0                                                       AS [NOM CLIENT],
    SOH.BPCORD_0                                                           AS [CODE CLIENT CMD.],
    SOH.BPCNAM_0                                                           AS [NOM CLIENT CMD.],
    SOQ.SOHNUM_0                                                           AS [NO COMMANDE],
    CASE WHEN SOH.CUSORDREF_0 <> '' THEN SOH.CUSORDREF_0 ELSE SOH.ZNORIGIN_0 END AS [REF. COMMANDE],
    SOQ.YCOLLECT_0                                                         AS [COLLECTION],
    ITM.ITMREF_0                                                           AS [SKU],
    ITM.ITMDES1_0                                                          AS [DESIGNATION],
    CASE WHEN ITC.ZDROPPED_0 = 2 THEN 'OUI' ELSE 'NON' END               AS [DROPPE],
    CONVERT(varchar(10), SOH.ORDDAT_0, 23)                                AS [DATE COMMANDE],
    CONVERT(varchar(10), SOQ.DEMDLVDAT_0, 23)                            AS [DATE LIVRAISON],
    CAST(ROUND(SOQ.QTY_0 - (SOQ.DLVQTY_0 + SOQ.ODLQTY_0), 0) AS INT)   AS [QUANTITE],
    SOP.NETPRINOT_0                                                        AS [PRIX UNITAIRE],
    SOH.CUR_0                                                              AS [DEVISE]
FROM X3_LCS.SORDERQ SOQ
    INNER JOIN X3_LCS.SORDER SOH   ON SOQ.SOHNUM_0 = SOH.SOHNUM_0
    INNER JOIN X3_LCS.SORDERP SOP  ON SOQ.SOHNUM_0 = SOP.SOHNUM_0
                                   AND SOQ.ITMREF_0 = SOP.ITMREF_0
                                   AND SOQ.SOPLIN_0 = SOP.SOPLIN_0
    INNER JOIN X3_LCS.ITMMASTER ITM ON SOQ.ITMREF_0 = ITM.ITMREF_0
    INNER JOIN X3_LCS.BPCUSTOMER BPC ON SOH.BPCORD_0 = BPC.BPCNUM_0
    LEFT  JOIN X3_LCS.BPCUSTOMER BPC_INV ON SOH.BPCINV_0 = BPC_INV.BPCNUM_0
    LEFT  JOIN X3_LCS.ATEXTRA ATX ON ATX.IDENT2_0 = BPC.TSCCOD_2
                                  AND ATX.CODFIC_0  = 'ATABDIV'
                                  AND ATX.LANGUE_0  = 'FRA'
                                  AND ATX.ZONE_0    = 'LNGDES'
                                  AND ATX.IDENT1_0  = '32'
    LEFT  JOIN X3_LCS.ZITMCOL ITC ON ITC.ITMREF_0   = LEFT(ITM.ITMREF_0, CHARINDEX('_', ITM.ITMREF_0 + '_') - 1)
                                  AND ITC.YCOLLECT_0 = SOQ.YCOLLECT_0
WHERE
    SOQ.SOQSTA_0 <> 3
    AND SOH.ZSOHVALSTA_0 <> 3
    AND BPC.BCGCOD_0 <> 'INTER'
    AND CAST(ROUND(SOQ.QTY_0 - (SOQ.DLVQTY_0 + SOQ.ODLQTY_0), 0) AS INT) > 0
