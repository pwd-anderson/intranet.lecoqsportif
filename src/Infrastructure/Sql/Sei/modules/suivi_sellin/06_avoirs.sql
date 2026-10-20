-- Accompagnement commercial : avoirs (décotes, retours, défauts / SAV, régularisations, autres) accordés aux clients.
-- Source : cube SEI_X3_LCS.CONSO_INVOICES. Un avoir = DOCUMENTTYPE 'CREDITMEMO' (montants et quantités déjà négatifs :
-- on les renvoie en positif = montant rendu au client, HT en euros). Société LCSI, depuis 2024.
-- Une ligne par avoir, client, article (ou ligne de service) et motif. La catégorie (Décote / Retours / Défauts-SAV /
-- Régularisation / Autres / Interco) et l'objet de l'avoir (Articles, Affiliation, Transport…) sont déduits côté PHP de
-- REASONCODE, ISDECOTE et du code article (MOTIF_AVOIR_CODE est vide sur LCSI).
-- Les régularisations (IN-REGUL) sont compensées par une nouvelle facture : affichées, exclues des totaux.
-- Table volumineuse (~20 s de lecture) : lue une fois par la commande de rafraîchissement (app:suivi-sellin:refresh).
SELECT
    I.CUSTOMERNO                                            AS CODE_CLIENT,
    I.DOCUMENTNO                                            AS DOCUMENT,
    CONVERT(varchar(10), I.DOCUMENTPOSTINGDATE, 23)         AS DATE_AVOIR,
    ISNULL(I.REASONCODE, '')                                AS MOTIF,
    ISNULL(I.RETURNREASONCODE, '')                          AS MOTIF_RETOUR,
    ISNULL(I.ISDECOTE, 0)                                   AS EST_DECOTE,
    ISNULL(I.ISDOTATION, 0)                                 AS EST_DOTATION,
    ISNULL(I.SERIESNO, '')                                  AS SAISON,
    ISNULL(I.ITEMNO, '')                                    AS ARTICLE,
    -SUM(I.AMOUNTEURTM)                                     AS MONTANT_EUR,
    -SUM(I.QUANTITY)                                        AS QUANTITE
FROM SEI_X3_LCS.CONSO_INVOICES I
WHERE I.DOCUMENTTYPE = 'CREDITMEMO'
  AND I.COMPANYCODE = 'LCSI'
  AND I.DOCUMENTPOSTINGDATE >= '2024-01-01'
GROUP BY
    I.CUSTOMERNO, I.DOCUMENTNO, CONVERT(varchar(10), I.DOCUMENTPOSTINGDATE, 23),
    ISNULL(I.REASONCODE, ''), ISNULL(I.RETURNREASONCODE, ''), ISNULL(I.ISDECOTE, 0), ISNULL(I.ISDOTATION, 0),
    ISNULL(I.SERIESNO, ''), ISNULL(I.ITEMNO, '');
