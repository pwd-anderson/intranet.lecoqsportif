SELECT
    [Type Commande]            AS TYPE_COMMANDE,
    CASE
    WHEN [Date Création] <> '' THEN CONVERT(varchar(10), [Date Création], 23)
    ELSE NULL
END                        AS DATE_CREATION,
    [Non transmis]             AS NON_TRANSMIS,
    [A envoyer]                AS A_ENVOYER,
    [Envoyé]                   AS ENVOYE,
    [Intégration]              AS INTEGRATION,
    [Annulation]               AS ANNULATION,
    [Lancement en préparation] AS LANCEMENT_EN_PREPARATION,
    [Fin de préparation]       AS FIN_DE_PREPARATION,
    [TOTAL]                    AS TOTAL
FROM [SEICube].[MASTER_TABLES].[IT0004_TCD_commandes_non_soldees_par_date_de_creation]
