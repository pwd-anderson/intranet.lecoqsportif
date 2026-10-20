-- Annulations de commandes : pièces et montants annulés / livrés par client, collection (saison) et classe de commande.
-- Source : X3 (SORDERQ / SORDER / SORDERP), mêmes règles que la stat État des commandes clients (QUANTITE_ANNULEE,
-- MONTANT_ANNULE_DEVISE, QUANTITE_LIVREE) : lignes soldées (SOQSTA_0 = 3) dont le reliquat n'a pas été livré = annulé ;
-- montants nets de la remise globale du pied de commande, en devise (conversion en euros côté PHP, cours X3 du jour).
-- Le « commandé » de comparaison vient du carnet SEI (jeu principal), pas de X3 : X3 n'a pas les commandes d'avant avril 2026
-- (les saisons concernées sont signalées « partiellement archivées »).
SELECT
    SOH.BPCORD_0                                            AS CODE_CLIENT,
    SOQ.YCOLLECT_0                                          AS COLLECTION,
    SOH.ZCLASSE_0                                           AS CLASSE,
    SOH.CUR_0                                               AS DEVISE,
    SUM(CASE WHEN SOQ.SOQSTA_0 = 3 AND ROUND(SOQ.QTY_0 - (SOQ.DLVQTY_0 + SOQ.ODLQTY_0), 0) > 0
             THEN ROUND(SOQ.QTY_0 - (SOQ.DLVQTY_0 + SOQ.ODLQTY_0), 0) ELSE 0 END)           AS QUANTITE_ANNULEE,
    SUM(SOP.NETPRINOT_0 * (1 - (ISNULL(SVT.DTAAMT_0, 0) / 100))
        * (CASE WHEN SOQ.SOQSTA_0 = 3 AND ROUND(SOQ.QTY_0 - (SOQ.DLVQTY_0 + SOQ.ODLQTY_0), 0) > 0
                THEN ROUND(SOQ.QTY_0 - (SOQ.DLVQTY_0 + SOQ.ODLQTY_0), 0) ELSE 0 END))       AS MONTANT_ANNULE_DEVISE,
    SUM(ROUND(SOQ.DLVQTY_0 + SOQ.ODLQTY_0, 0))                                              AS QUANTITE_LIVREE,
    SUM(SOP.NETPRINOT_0 * (1 - (ISNULL(SVT.DTAAMT_0, 0) / 100)) * ROUND(SOQ.DLVQTY_0 + SOQ.ODLQTY_0, 0)) AS MONTANT_LIVRE_DEVISE
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
GROUP BY SOH.BPCORD_0, SOQ.YCOLLECT_0, SOH.ZCLASSE_0, SOH.CUR_0
HAVING SUM(ROUND(SOQ.QTY_0, 0)) <> 0;
