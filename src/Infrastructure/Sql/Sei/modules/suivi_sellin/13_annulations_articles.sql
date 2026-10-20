-- Annulations de commandes : références les plus annulées (par client, collection et article). Même source et mêmes règles
-- que 12_annulations.sql ; seules les lignes avec une quantité annulée sont renvoyées. Montant en devise (converti en euros
-- côté PHP) ; QUANTITE_COMMANDEE = pièces commandées de l'article chez ce client sur la collection.
SELECT
    SOH.BPCORD_0                                            AS CODE_CLIENT,
    SOQ.YCOLLECT_0                                          AS COLLECTION,
    CASE WHEN CHARINDEX('_', ITM.ITMREF_0) > 0 THEN LEFT(ITM.ITMREF_0, CHARINDEX('_', ITM.ITMREF_0) - 1) ELSE ITM.ITMREF_0 END AS ARTICLE,
    SOH.CUR_0                                               AS DEVISE,
    SUM(CASE WHEN SOQ.SOQSTA_0 = 3 AND ROUND(SOQ.QTY_0 - (SOQ.DLVQTY_0 + SOQ.ODLQTY_0), 0) > 0
             THEN ROUND(SOQ.QTY_0 - (SOQ.DLVQTY_0 + SOQ.ODLQTY_0), 0) ELSE 0 END)           AS QUANTITE_ANNULEE,
    SUM(SOP.NETPRINOT_0 * (1 - (ISNULL(SVT.DTAAMT_0, 0) / 100))
        * (CASE WHEN SOQ.SOQSTA_0 = 3 AND ROUND(SOQ.QTY_0 - (SOQ.DLVQTY_0 + SOQ.ODLQTY_0), 0) > 0
                THEN ROUND(SOQ.QTY_0 - (SOQ.DLVQTY_0 + SOQ.ODLQTY_0), 0) ELSE 0 END))       AS MONTANT_ANNULE_DEVISE,
    SUM(ROUND(SOQ.QTY_0, 0))                                                                AS QUANTITE_COMMANDEE
FROM X3_LCS.SORDERQ SOQ
INNER JOIN X3_LCS.SORDER SOH ON SOQ.SOHNUM_0 = SOH.SOHNUM_0
INNER JOIN X3_LCS.SORDERP SOP
        ON SOQ.SOHNUM_0 = SOP.SOHNUM_0
       AND SOQ.ITMREF_0 = SOP.ITMREF_0
       AND SOQ.SOPLIN_0 = SOP.SOPLIN_0
INNER JOIN X3_LCS.BPCUSTOMER BPC ON SOH.BPCORD_0 = BPC.BPCNUM_0
INNER JOIN X3_LCS.ITMMASTER ITM ON SOQ.ITMREF_0 = ITM.ITMREF_0
LEFT JOIN X3_LCS.SVCRFOOT SVT ON SOH.SOHNUM_0 = SVT.VCRNUM_0 AND SVT.DTA_0 = 1
WHERE SOH.ZSOHVALSTA_0 <> 3
  AND BPC.BCGCOD_0 <> 'INTER'
  AND ISNULL(ITM.TCLCOD_0, '') <> 'POP'
GROUP BY SOH.BPCORD_0, SOQ.YCOLLECT_0,
         CASE WHEN CHARINDEX('_', ITM.ITMREF_0) > 0 THEN LEFT(ITM.ITMREF_0, CHARINDEX('_', ITM.ITMREF_0) - 1) ELSE ITM.ITMREF_0 END,
         SOH.CUR_0
HAVING SUM(CASE WHEN SOQ.SOQSTA_0 = 3 AND ROUND(SOQ.QTY_0 - (SOQ.DLVQTY_0 + SOQ.ODLQTY_0), 0) > 0
                THEN ROUND(SOQ.QTY_0 - (SOQ.DLVQTY_0 + SOQ.ODLQTY_0), 0) ELSE 0 END) > 0;
