-- Suivi complet de commande (ADV) : commande -> bon de preparation -> livraison -> facture.
-- Charge uniquement a la recherche (voir Sales::getSuiviCompletCommande) : {{WHERE}} est
-- remplace cote PHP par une clause construite a partir d'une liste blanche de 4 colonnes
-- (NUM_COMMANDE, NUM_BON_PREPA, NUM_LIVRAISON, NUM_FACTURE), avec des parametres nommes
-- (jamais de valeur saisie concatenee dans le SQL).

SELECT [NUM_COMMANDE]
      ,CONVERT(varchar(10), [DATE_COMMANDE], 23)           AS [DATE_COMMANDE]
      ,[CLIENT]
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
FROM [SEICube].[MASTER_TABLES].[PRODXXX_SUIVI_COMPLET_COMMANDE]
{{WHERE}}
