SELECT
    CASE
        WHEN [Date Création] <> '' THEN CONVERT(varchar(10), [Date Création], 23)
    ELSE NULL
END                          AS DATE_CREATION,
    [Type Commande]              AS TYPE_COMMANDE,
    [Commande]                   AS COMMANDE,
    [Ligne]                      AS LIGNE,
    [Séquence]                   AS SEQUENCE,
    [Article]                    AS ARTICLE,
    [Statut Ligne]               AS STATUT_LIGNE,
    [Reference Interne]          AS REFERENCE_INTERNE,
    [Bon de Préparation]         AS BON_DE_PREPARATION,
    [Statut Bon de Préparation]  AS STATUT_BON_DE_PREPARATION,
    [Statut PREPS]               AS STATUT_PREPS,
    [Log Envoyé]                 AS LOG_ENVOYE,
    CASE
        WHEN [DATE_RETOUR] <> '' THEN CONVERT(varchar(10), [DATE_RETOUR], 23)
        ELSE NULL
END                          AS DATE_RETOUR
FROM [SEICube].[MASTER_TABLES].[IT0005_Detaildu_TCD]
