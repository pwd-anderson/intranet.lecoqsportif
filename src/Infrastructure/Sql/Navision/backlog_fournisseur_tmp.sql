
SELECT distinct
    PH.CompanyCode AS CODE_COMPANY
              ,PL.[Location Code] as SITE
    ,PL.[Series No_] AS COLLECTION
    ,ITEM.ItemFamilyCode AS FAMILLE
    ,PL.No_ AS ARTICLE
    ,PL.[Variant Code] as CODE_VARIANT
	,PH.[Buy-from Vendor No_] AS CODE_FOURNISSEUR
    ,V.Name AS NOM_FOURNISSEUR
    ,PH.No_ AS NO_COMMANDE
    ,PH.[Order Date] AS DATE_COMMANDE
    ,COALESCE(
    NULLIF(PH.[Confirmed Shipment date], '1753-01-01'),
    NULLIF(PH.[Expected ETD], '1753-01-01')
    ) AS DATE_EXPEDITION
    ,COALESCE(
    NULLIF(PH.[Confirmed Arrival whse date], '1753-01-01'),
    NULLIF(PH.[Expected ETA], '1753-01-01')
    ) AS DATE_LIVRAISON
             -- si date livraison < date aujourd'hui -> EN RETARD en couleur rouge sinon VIDE
    ,CASE
    WHEN COALESCE(
    NULLIF(PH.[Confirmed Arrival whse date], '1753-01-01'),
    NULLIF(PH.[Expected ETA], '1753-01-01')
    ) IS NOT NULL
    AND
    COALESCE(
    NULLIF(PH.[Confirmed Arrival whse date], '1753-01-01'),
    NULLIF(PH.[Expected ETA], '1753-01-01')
    ) < CAST(GETDATE() AS DATE)
    THEN 'EN RETARD'
    ELSE ''
END AS STATUS
    ,PH.[Your Reference] as REF_INTERNE
	,CAST(PL.[Outstanding Quantity] as float) AS QUANTITE
	,PL.[Outstanding Amount] AS MONTANT_DEVISE
	,PL.[Currency Code] AS DEVISE
    ,PL.[Outstanding Amount (LCY)] AS MONTANT_EUR
    ,PH.[Shipment Method Code] AS INCOTERM
    ,PH.[Transport Method] AS MODE_TRANSPORT -- a mapper avec le fichier d'Adrien
    ,PH.[Affect_ Sales Cust Name] AS CLIENT_CONCERNE

FROM
DB_Datalake.[nav].[Purchase Header] PH
LEFT JOIN DB_Datalake.[nav].[Purchase Line] PL ON PH.No_ = PL.[Document No_] and PH.CompanyCode = PL.CompanyCode
LEFT JOIN DB_Datalake.[nav].Vendor V ON V.CompanyCode = PH.CompanyCode AND PH.[Buy-from Vendor No_] = V.No_
LEFT JOIN BI.DWH.D_Item ITEM ON ITEM.ItemNo = PL.No_
WHERE
	PH.Status = 0
	AND PH.[Document Type] = 1
	AND PL.Type = 2
    AND PL.[Outstanding Quantity] <> 0
    AND PH.CompanyCode = 'LCSI BV'
