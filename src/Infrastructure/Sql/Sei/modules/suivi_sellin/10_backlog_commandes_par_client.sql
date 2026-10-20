-- Backlog client : nombre de commandes ouvertes par client (affiché sur les fiches et dans la vue Facturation).
-- Source : X3 (SORDERQ / SORDER), mêmes règles de périmètre que 09_backlog_client.sql.
SELECT
    SOH.BPCORD_0                    AS CODE_CLIENT,
    COUNT(DISTINCT SOQ.SOHNUM_0)    AS NB_COMMANDES
FROM X3_LCS.SORDERQ SOQ
INNER JOIN X3_LCS.SORDER SOH ON SOQ.SOHNUM_0 = SOH.SOHNUM_0
INNER JOIN X3_LCS.BPCUSTOMER BPC ON SOH.BPCORD_0 = BPC.BPCNUM_0
WHERE SOQ.SOQSTA_0 <> 3
  AND SOH.ZSOHVALSTA_0 <> 3
  AND BPC.BCGCOD_0 <> 'INTER'
  AND SOQ.QTY_0 - (SOQ.DLVQTY_0 + SOQ.ODLQTY_0) > 0
GROUP BY SOH.BPCORD_0;
