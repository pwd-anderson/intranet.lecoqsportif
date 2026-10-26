-- Suivi complet de commande (ADV) : commande -> bon de preparation -> livraison -> facture.
-- Charge uniquement a la recherche (voir Sales::getSuiviCompletCommande) : {{WHERE}} est
-- remplace cote PHP par une clause construite a partir d'une liste blanche de 4 colonnes
-- (NUM_COMMANDE, NUM_BON_PREPA, NUM_LIVRAISON, NUM_FACTURE) et du filtre de periode, avec des parametres nommes
-- (jamais de valeur saisie concatenee dans le SQL). {{LIMIT}} est un entier fixe cote PHP (plafond de lignes).
--
-- Deux sources, meme structure de 20 colonnes (UNION ALL) :
--  1) X3 : la vue MASTER_TABLES.PRODXXX_SUIVI_COMPLET_COMMANDE (commandes passees dans X3 depuis avril 2026,
--     avec les commandes reprises depuis 2025) ;
--  2) HISTORIQUE NAVISION : LCS_X3SALESX, source '0.Nav_Histo' (commandes facturees jusqu'au 2026-04-09, depuis 2023),
--     meme enchainement commande -> BP -> BL -> facture, une ligne par enchainement. Differences avec X3 :
--       - les montants de facture (HT / TTC) sont la SOMME DES LIGNES de la facture (le cube n'a pas de total d'entete) ;
--       - les etats (prepa 3 / validation BL 2 / facturation BL 2) valent « expedie » quand la ligne du BP est SHIPPED,
--         sinon ils sont vides (inconnus) ; pas de date de livraison dans l'historique ;
--       - les colonnes texte sont forcees en Latin1_General_BIN (collation de la vue X3) pour l'UNION.
--     Quelques commandes existent dans les deux sources (reprise Navision -> X3) avec des factures differentes :
--     les deux enchainements s'affichent.

SELECT TOP ({{LIMIT}}) [NUM_COMMANDE]
      ,CONVERT(varchar(10), [DATE_COMMANDE], 23)           AS [DATE_COMMANDE]
      ,[CLIENT]
      ,[CLIENT_FACTURE]
      ,[RAISON_SOCIALE]
      ,[SITE_VENTE]
      ,[NUM_BON_PREPA]
      ,[SITE_EXPEDITION]
      ,CONVERT(varchar(10), [DATE_EXPEDITION_PREVUE], 23)  AS [DATE_EXPEDITION_PREVUE]
      ,CONVERT(varchar(10), [DATE_CREATION_BP], 23)        AS [DATE_CREATION_BP]
      ,[ETAT_PREPA]
      ,[NUM_LIVRAISON]
      ,CONVERT(varchar(10), [DATE_EXPEDITION], 23)         AS [DATE_EXPEDITION]
      ,CONVERT(varchar(10), [DATE_LIVRAISON], 23)          AS [DATE_LIVRAISON]
      ,[ETAT_VALIDATION_BL]
      ,[ETAT_FACTURATION_BL]
      ,[NUM_FACTURE]
      ,CONVERT(varchar(10), [DATE_FACTURE], 23)            AS [DATE_FACTURE]
      ,[MONTANT_HT_FACTURE]
      ,[MONTANT_TTC_FACTURE]
FROM (
    -- 1) X3
    SELECT V.NUM_COMMANDE, V.DATE_COMMANDE, V.CLIENT, V.CLIENT_FACTURE, V.RAISON_SOCIALE, V.SITE_VENTE,
           V.NUM_BON_PREPA, V.SITE_EXPEDITION, V.DATE_EXPEDITION_PREVUE, V.DATE_CREATION_BP, V.ETAT_PREPA,
           V.NUM_LIVRAISON, V.DATE_EXPEDITION, V.DATE_LIVRAISON, V.ETAT_VALIDATION_BL, V.ETAT_FACTURATION_BL,
           V.NUM_FACTURE, V.DATE_FACTURE, V.MONTANT_HT_FACTURE, V.MONTANT_TTC_FACTURE
    FROM [SEICube].[MASTER_TABLES].[PRODXXX_SUIVI_COMPLET_COMMANDE] V

    UNION ALL

    -- 2) Historique Navision
    SELECT
        CAST(S.ORDERDOCUMENTNO AS nvarchar(40)) COLLATE Latin1_General_BIN                   AS NUM_COMMANDE,
        CAST(MIN(S.ORDERCREATIONDATE) AS datetime2(7))                                       AS DATE_COMMANDE,
        CAST(S.CUSTOMERNO AS nvarchar(30)) COLLATE Latin1_General_BIN                        AS CLIENT,
        CAST(MAX(BPC.BPCINV_0) AS nvarchar(30)) COLLATE Latin1_General_BIN                   AS CLIENT_FACTURE,
        CAST(MAX(BPC.BPCNAM_0) AS nvarchar(70)) COLLATE Latin1_General_BIN                   AS RAISON_SOCIALE,
        CAST(MAX(S.LOCATIONCODE) AS nvarchar(10)) COLLATE Latin1_General_BIN                 AS SITE_VENTE,
        CAST(NULLIF(S.PREPARATIONDOCUMENTNO, '') AS nvarchar(40)) COLLATE Latin1_General_BIN AS NUM_BON_PREPA,
        CAST(MAX(P.LocationCode) AS nvarchar(10)) COLLATE Latin1_General_BIN                 AS SITE_EXPEDITION,
        CAST(MAX(P.ShipmentDate) AS datetime2(7))                                            AS DATE_EXPEDITION_PREVUE,
        CAST(MAX(P.CreationDate) AS datetime2(7))                                            AS DATE_CREATION_BP,
        CAST(MAX(CASE WHEN P.LineStatus = 'SHIPPED' THEN 3 END) AS tinyint)                  AS ETAT_PREPA,
        CAST(NULLIF(S.SHIPMENTDOCUMENTNO, '') AS nvarchar(40)) COLLATE Latin1_General_BIN    AS NUM_LIVRAISON,
        CAST(MAX(P.ShipmentDate) AS datetime2(7))                                            AS DATE_EXPEDITION,
        CAST(NULL AS datetime2(7))                                                           AS DATE_LIVRAISON,
        CAST(MAX(CASE WHEN P.LineStatus = 'SHIPPED' THEN 2 END) AS tinyint)                  AS ETAT_VALIDATION_BL,
        CAST(MAX(CASE WHEN P.LineStatus = 'SHIPPED' THEN 2 END) AS tinyint)                  AS ETAT_FACTURATION_BL,
        CAST(NULLIF(S.INVOICEDOCUMENTNO, '') AS nvarchar(40)) COLLATE Latin1_General_BIN     AS NUM_FACTURE,
        CAST(MAX(S.EXPECTEDINVOICINGDATE) AS datetime2(7))                                   AS DATE_FACTURE,
        CAST(MAX(INV.HT) AS decimal(27,13))                                                  AS MONTANT_HT_FACTURE,
        CAST(MAX(INV.TTC) AS decimal(27,13))                                                 AS MONTANT_TTC_FACTURE
    FROM [SEICube].[SEI_X3_LCS].[LCS_X3SALESX] S
    LEFT JOIN X3_LCS.BPCUSTOMER BPC
           ON BPC.BPCNUM_0 = S.CUSTOMERNO
    LEFT JOIN [SEICube].[DWH_LCS].[F_Sales_Preparations] P
           ON P.PreparationDocumentNo = S.PREPARATIONDOCUMENTNO
          AND P.PreparationDocumentLineNo = S.PREPDOCUMENTLINENO
    LEFT JOIN (
        SELECT INVOICEDOCUMENTNO, SUM(INV_AMOUNTEUR) AS HT, SUM(INV_AMOUNTEURINCLVAT) AS TTC
        FROM [SEICube].[SEI_X3_LCS].[LCS_X3SALESX]
        WHERE SOURCE = '0.Nav_Histo' AND COMPANYCODE = 'LCSI'
        GROUP BY INVOICEDOCUMENTNO
    ) INV ON INV.INVOICEDOCUMENTNO = S.INVOICEDOCUMENTNO
    WHERE S.SOURCE = '0.Nav_Histo' AND S.COMPANYCODE = 'LCSI'
    GROUP BY S.ORDERDOCUMENTNO, S.CUSTOMERNO, S.PREPARATIONDOCUMENTNO, S.SHIPMENTDOCUMENTNO, S.INVOICEDOCUMENTNO
) U
{{WHERE}}
ORDER BY [DATE_COMMANDE] DESC, [NUM_COMMANDE]
