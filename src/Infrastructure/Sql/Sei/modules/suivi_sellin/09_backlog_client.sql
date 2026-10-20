-- Backlog client : reste à livrer (lignes de commande clients ouvertes) avec dates de livraison et d'arrivée prévue.
-- Source : X3 (SORDERQ / SORDER / SORDERP), mêmes règles que la stat Backlog Clients (Sei/backlog_client_v2.sql) :
-- lignes non soldées, commandes non annulées, clients hors intersite ; montant net de la remise globale du pied de
-- commande. Toutes les lignes sont renvoyées regroupées par client, article, collection, classe, dates et devise ;
-- la conversion en euros (cours X3 du jour : montant / taux de la devise) est faite côté PHP, comme le Backlog Client.
-- Date d'arrivée prévue = date de l'intersite en transit, sinon ETA France du plan transport (site WLOGM seulement).
-- {{PLAN_TRANSPORT_TABLE}} est remplacé par le service (table _DEV en local, source vide si la table est absente).
-- La PLV (famille POP) est exclue, comme dans le carnet.
SELECT
    SOH.BPCORD_0                                              AS CODE_CLIENT,
    SPLIT.ARTICLE_BASE                                        AS ARTICLE,
    MAX(ITM.ITMDES1_0)                                        AS DESIGNATION,
    SOQ.YCOLLECT_0                                            AS COLLECTION,
    ITM.TCLCOD_0                                              AS FAMILLE,
    ITM.TSICOD_0                                              AS GENRE,
    SOH.ZCLASSE_0                                             AS CLASSE,
    CONVERT(varchar(10), SOQ.DEMDLVDAT_0, 23)                 AS DATE_LIVRAISON,
    COALESCE(CI.DATE_INTERSITE, PT.DATE_ETA_FRANCE)           AS DATE_ARRIVEE_PREVUE,
    SOH.CUR_0                                                 AS DEVISE,
    SUM(CAST(ROUND(SOQ.QTY_0 - (SOQ.DLVQTY_0 + SOQ.ODLQTY_0), 0) AS INT)) AS QUANTITE,
    SUM(SOP.NETPRINOT_0 * CAST(ROUND(SOQ.QTY_0 - (SOQ.DLVQTY_0 + SOQ.ODLQTY_0), 0) AS INT)
        * (1 - (ISNULL(SVT.DTAAMT_0, 0) / 100)))              AS MONTANT_DEVISE
FROM X3_LCS.SORDERQ SOQ
INNER JOIN X3_LCS.SORDER SOH ON SOQ.SOHNUM_0 = SOH.SOHNUM_0
INNER JOIN X3_LCS.SORDERP SOP
        ON SOQ.SOHNUM_0 = SOP.SOHNUM_0
       AND SOQ.ITMREF_0 = SOP.ITMREF_0
       AND SOQ.SOPLIN_0 = SOP.SOPLIN_0
INNER JOIN X3_LCS.ITMMASTER ITM ON SOQ.ITMREF_0 = ITM.ITMREF_0
CROSS APPLY (
    SELECT CASE WHEN CHARINDEX('_', ITM.ITMREF_0) > 0
                THEN LEFT(ITM.ITMREF_0, CHARINDEX('_', ITM.ITMREF_0) - 1)
                ELSE ITM.ITMREF_0
           END AS ARTICLE_BASE
) AS SPLIT
INNER JOIN X3_LCS.BPCUSTOMER BPC ON SOH.BPCORD_0 = BPC.BPCNUM_0
LEFT JOIN X3_LCS.SVCRFOOT SVT ON SOH.SOHNUM_0 = SVT.VCRNUM_0 AND SVT.DTA_0 = 1
LEFT JOIN (
    SELECT ITMREF_0, SITE_RECEPTION,
           CONVERT(varchar(10), MIN(EXTRCPDAT_0), 23) AS DATE_INTERSITE
    FROM MASTER_TABLES.COMMANDES_INTERSITES
    WHERE QTE_EN_TRANSIT > 0
    GROUP BY ITMREF_0, SITE_RECEPTION
) CI ON CI.ITMREF_0 = SOQ.ITMREF_0 AND CI.SITE_RECEPTION = SOH.STOFCY_0
LEFT JOIN (
    SELECT PTS.ARTICLE_SKU, CONVERT(varchar(10), MIN(PTS.DATE_ETA_FRANCE), 23) AS DATE_ETA_FRANCE
    FROM {{PLAN_TRANSPORT_TABLE}} PTS
    GROUP BY PTS.ARTICLE_SKU
) PT ON PT.ARTICLE_SKU = SOQ.ITMREF_0 AND SOH.STOFCY_0 = 'WLOGM'
WHERE SOQ.SOQSTA_0 <> 3
  AND SOH.ZSOHVALSTA_0 <> 3
  AND BPC.BCGCOD_0 <> 'INTER'
  AND ISNULL(ITM.TCLCOD_0, '') <> 'POP'
GROUP BY
    SOH.BPCORD_0, SPLIT.ARTICLE_BASE, SOQ.YCOLLECT_0, ITM.TCLCOD_0, ITM.TSICOD_0, SOH.ZCLASSE_0,
    CONVERT(varchar(10), SOQ.DEMDLVDAT_0, 23), COALESCE(CI.DATE_INTERSITE, PT.DATE_ETA_FRANCE), SOH.CUR_0
HAVING SUM(CAST(ROUND(SOQ.QTY_0 - (SOQ.DLVQTY_0 + SOQ.ODLQTY_0), 0) AS INT)) <> 0;
